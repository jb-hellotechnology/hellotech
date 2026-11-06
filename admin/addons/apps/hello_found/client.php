<?php
	include('../../../core/inc/api.php');
	$API  = new PerchAPI(1.0, 'hello_found');
	$HTML = $API->get('HTML');   // required by Perch's sidebar layout
	$Lang = $API->get('Lang');
	require __DIR__ . '/_admin.php';
	hf_admin_subnav($CurrentUser);

	$id = (int)($_GET['id'] ?? 0);
	$account = HF_Account::find($id);
	if (!$account) { PerchUtil::redirect(hf_admin_url('index.php')); }

	$tabs = ['account' => 'Account', 'gsc' => 'Search Console', 'keywords' => 'Keywords', 'prompts' => 'AI prompts', 'fathom' => 'Fathom', 'reports' => 'Reports'];
	$tab  = isset($tabs[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'account';
	$self = fn(array $q = []) => hf_admin_url('client.php', ['id' => $id] + $q);

	/* ---------- POST actions (before any output so we can redirect) ---------- */
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		hf_require_post();
		$act = $_POST['action'] ?? '';
		$go_tab = $tab; $go_extra = [];

		switch ($act) {
			case 'add_note':
				$note = trim((string)($_POST['note'] ?? ''));
				if ($note !== '') HF_Db::insert('admin_notes', ['account_id' => $id, 'note' => $note, 'created_by' => hf_admin_name(), 'created_at' => HF_Db::now()]);
				$go_tab = 'account'; hf_flash('Note added.');
				break;

			case 'add_keyword':
				$err = HF_Keyword::add($id, (string)($_POST['keyword'] ?? ''), trim((string)($_POST['category'] ?? '')), trim((string)($_POST['target_url'] ?? '')), hf_admin_name());
				$go_tab = 'keywords'; hf_flash($err ?: 'Keyword added.', $err ? 'error' : 'success');
				break;

			case 'update_keyword':
				HF_Keyword::update((int)($_POST['keyword_id'] ?? 0), $id, trim((string)($_POST['category'] ?? '')), trim((string)($_POST['target_url'] ?? '')));
				$go_tab = 'keywords'; hf_flash('Keyword updated.');
				break;

			case 'toggle_keyword':
				$err = HF_Keyword::set_active((int)($_POST['keyword_id'] ?? 0), $id, !empty($_POST['active']));
				$go_tab = 'keywords'; hf_flash($err ?: 'Keyword updated.', $err ? 'error' : 'success');
				break;

			case 'set_location':
				$loc = trim((string)($_POST['serp_location'] ?? ''));
				$biz = trim((string)($_POST['business_name'] ?? ''));
				if ($d = HF_Account::domain($id)) HF_Db::update('domains', ['serp_location' => $loc !== '' ? substr($loc, 0, 150) : null, 'business_name' => $biz !== '' ? substr($biz, 0, 150) : null], (int)$d['id']);
				$go_tab = 'keywords'; hf_flash('Search settings saved. Re-run rankings to use them.');
				break;

			case 'add_prompt':
				$err = HF_Ai::add_prompt($id, (string)($_POST['prompt'] ?? ''), hf_admin_name());
				$go_tab = 'prompts'; hf_flash($err ?: 'Prompt added.', $err ? 'error' : 'success');
				break;

			case 'toggle_prompt':
				$err = HF_Ai::set_prompt_active((int)($_POST['prompt_id'] ?? 0), $id, !empty($_POST['active']));
				$go_tab = 'prompts'; hf_flash($err ?: 'Prompt updated.', $err ? 'error' : 'success');
				break;

			case 'disconnect_gsc':
				HF_Gsc::disconnect($id);
				$go_tab = 'gsc'; hf_flash('Search Console disconnected.');
				break;

			case 'detect_gsc':
				$site = HF_Gsc::detect_property($id);
				$go_tab = 'gsc'; hf_flash($site ? "Property found: $site" : 'No matching property found.' . (HF_Gsc::$last_error ? ' Google said: ' . HF_Gsc::$last_error : ''), $site ? 'success' : 'error');
				break;

			case 'link_fathom':
				$err = HF_Fathom::link_site($id, (string)($_POST['fathom_site_id'] ?? ''));
				$go_tab = 'fathom'; hf_flash($err ?: 'Existing Fathom site linked.', $err ? 'error' : 'success');
				break;

			case 'unlink_fathom':
				HF_Fathom::unlink_site($id);
				$go_tab = 'fathom'; hf_flash('Fathom site unlinked (nothing was deleted in Fathom).');
				break;

			case 'create_report':
				$rid = HF_Report::create_draft($id);
				$go_tab = 'reports'; $go_extra = ['report' => $rid];
				break;

			case 'save_report':
			case 'publish_report':
				$rid = (int)($_POST['report_id'] ?? 0);
				$ok = HF_Report::save_draft($rid, $id, (string)($_POST['summary_text'] ?? ''), $act === 'save_report' && !empty($_POST['refresh']));
				if ($ok && $act === 'publish_report') $ok = HF_Report::publish($rid, $id);
				$go_tab = 'reports'; $go_extra = $act === 'save_report' ? ['report' => $rid] : [];
				hf_flash($ok ? ($act === 'publish_report' ? 'Report published to the customer dashboard.' : 'Draft saved.') : 'Report not found or already published.', $ok ? 'success' : 'error');
				break;
		}
		PerchUtil::redirect($self(['tab' => $go_tab] + $go_extra));
	}

	/* ---------- Data for display ---------- */
	$member  = HF_Members::details($account['member_id']);
	$domain  = HF_Account::domain($id);
	$token   = HF_Gsc::token_row($id);
	$stripe_base = (defined('HELLOFOUND_STRIPE_SECRET') && strpos(HELLOFOUND_STRIPE_SECRET, 'sk_test') === 0) ? 'https://dashboard.stripe.com/test' : 'https://dashboard.stripe.com';

	$Perch->page_title = $Lang->get('hello.found client');
	include(PERCH_CORE . '/inc/top.php');

	// small helper: a POST form button
	$btn = function ($action, $label, array $hidden = [], $confirm = null) use ($self, $tab) {
		$h = '<form method="post" action="' . hf_e($self(['tab' => $tab])) . '" style="display:inline"' . ($confirm ? ' onsubmit="return confirm(\'' . hf_e($confirm) . '\')"' : '') . '>'
		   . hf_csrf_field() . '<input type="hidden" name="action" value="' . hf_e($action) . '">';
		foreach ($hidden as $k => $v) $h .= '<input type="hidden" name="' . hf_e($k) . '" value="' . hf_e($v) . '">';
		return $h . '<input type="submit" class="button button-simple" value="' . hf_e($label) . '"></form>';
	};
	$post_to = function ($file, $label, $confirm = null) use ($id) {
		return '<form method="post" action="' . hf_e(hf_admin_url($file)) . '" style="display:inline"' . ($confirm ? ' onsubmit="return confirm(\'' . hf_e($confirm) . '\')"' : '') . '>'
		     . hf_csrf_field() . '<input type="hidden" name="account_id" value="' . $id . '"><input type="submit" class="button button-simple" value="' . hf_e($label) . '"></form>';
	};
?>
<h1><?= hf_e($member['name'] ?? 'Client') ?> <small><?= hf_e($domain['domain'] ?? '') ?></small></h1>
<?= hf_flash_html() ?>
<p>
	<?php foreach ($tabs as $k => $label): ?>
		<a href="<?= hf_e($self(['tab' => $k])) ?>"><?= $k === $tab ? '<b>' . hf_e($label) . '</b>' : hf_e($label) ?></a>&nbsp;&nbsp;
	<?php endforeach; ?>
	&nbsp;|&nbsp; <a href="<?= hf_e(hf_admin_url('index.php')) ?>">All clients</a>
</p>

<?php if ($tab === 'account'): ?>
	<table class="d">
		<tr><th>Name</th><td><?= hf_e($member['name'] ?? '') ?></td></tr>
		<tr><th>Email</th><td><?= hf_e($member['email'] ?? '') ?></td></tr>
		<tr><th>Stripe customer</th><td><?= $account['stripe_customer_id'] ? '<a target="_blank" rel="noopener" href="' . hf_e($stripe_base . '/customers/' . $account['stripe_customer_id']) . '">' . hf_e($account['stripe_customer_id']) . '</a>' : '–' ?></td></tr>
		<tr><th>Subscription</th><td><?= hf_e(str_replace('_', ' ', $account['subscription_status'])) ?></td></tr>
		<tr><th>Plan start</th><td><?= $account['plan_start'] ? hf_e(date('j M Y', strtotime($account['plan_start']))) : '–' ?></td></tr>
		<tr><th>Plan end</th><td><?= $account['plan_end'] ? hf_e(date('j M Y', strtotime($account['plan_end']))) : '–' ?></td></tr>
		<tr><th>Onboarding</th><td><?= $account['onboarding_complete'] ? 'Complete' : 'Awaiting domain' ?></td></tr>
	</table>
	<h2>Notes</h2>
	<form method="post" action="<?= hf_e($self(['tab' => 'account'])) ?>">
		<?= hf_csrf_field() ?><input type="hidden" name="action" value="add_note">
		<textarea name="note" rows="3" style="width:100%" required></textarea><br>
		<input type="submit" class="button button-simple" value="Add note">
	</form>
	<table class="d">
		<?php foreach (HF_Account::notes($id) as $n): ?>
			<tr><td style="white-space:nowrap"><?= hf_e(date('j M Y H:i', strtotime($n['created_at']))) ?><br><small><?= hf_e($n['created_by']) ?></small></td><td><?= nl2br(hf_e($n['note'])) ?></td></tr>
		<?php endforeach; ?>
	</table>

<?php elseif ($tab === 'gsc'): ?>
	<?php if (!$domain): ?>
		<p>The customer hasn't entered their domain yet.</p>
	<?php elseif (!$token): ?>
		<p>Not connected.</p>
		<form method="post" action="<?= hf_e(hf_admin_url('gsc-start.php')) ?>"><?= hf_csrf_field() ?><input type="hidden" name="account_id" value="<?= $id ?>"><input type="submit" class="button button-simple" value="Connect Search Console"></form>
		<p><small>You'll be sent to Google. Sign in with an account that has access to <?= hf_e($domain['domain']) ?> in Search Console.</small></p>
	<?php else: ?>
		<table class="d">
			<tr><th>Status</th><td><?= $token['is_valid'] ? 'Connected' : '<b>Connection invalid - reconnect</b>' ?></td></tr>
			<tr><th>Property</th><td><?= $domain['gsc_site_url'] ? hf_e($domain['gsc_site_url']) : '<em>none matched</em>' ?></td></tr>
			<tr><th>Token expires</th><td><?= hf_e($token['token_expires']) ?> UTC (auto-refreshes)</td></tr>
			<tr><th>Connected</th><td><?= hf_e(date('j M Y H:i', strtotime($token['connected_at']))) ?> by <?= hf_e($token['connected_by']) ?></td></tr>
		</table>
		<p>
			<?= $post_to('_get/pull-gsc.php', 'Pull data now') ?>
			<?= $btn('detect_gsc', 'Re-detect property') ?>
			<form method="post" action="<?= hf_e(hf_admin_url('gsc-start.php')) ?>" style="display:inline"><?= hf_csrf_field() ?><input type="hidden" name="account_id" value="<?= $id ?>"><input type="submit" class="button button-simple" value="Reconnect"></form>
			<?= $btn('disconnect_gsc', 'Disconnect', [], 'Disconnect Search Console for this client?') ?>
		</p>
	<?php endif; ?>

<?php elseif ($tab === 'keywords'):
	$kws = HF_Keyword::for_account($id);
	$ranks = []; foreach (HF_GscData::keyword_table($id) as $r) $ranks[$r['keyword']] = $r;
	$rank_diag = []; foreach (HF_Db::rows('SELECT r.keyword_id, r.organic_count, r.top_domains FROM ' . HF_Db::t('rank_checks') . ' r JOIN (SELECT keyword_id, MAX(id) mid FROM ' . HF_Db::t('rank_checks') . ' WHERE account_id=' . (int)$id . ' GROUP BY keyword_id) l ON l.mid=r.id') as $r) $rank_diag[(int)$r['keyword_id']] = $r;
	$loc_now = HF_Rank::location_for($domain ?: []); ?>
	<p><?= HF_Keyword::active_count($id) ?> of <?= (int)HELLOFOUND_MAX_KEYWORDS ?> active keywords.</p>
	<table class="d">
		<thead><tr><th>Keyword</th><th>UK rank</th><th>Local pack</th><th>Change</th><th>Checked</th><th>Category</th><th>Target URL</th><th></th></tr></thead>
		<tbody>
		<?php foreach ($kws as $k): ?>
			<tr<?= $k['is_active'] ? '' : ' style="opacity:.55"' ?>>
				<td><?= hf_e($k['keyword']) ?></td>
				<?php $rk = $ranks[$k['keyword']] ?? null; ?>
				<?php $dg = $rank_diag[(int)$k['id']] ?? null; ?>
				<td<?= $dg ? ' title="' . hf_e('Scanned ' . (int)$dg['organic_count'] . ' organic results. Top results: ' . $dg['top_domains']) . '"' : '' ?>><?php if (!$rk || !$k['is_active']): ?>–<?php elseif ($rk['source'] === 'rank'): ?><?= $rk['position'] !== null ? '#' . (int)$rk['position'] : (!empty($rk['local']['position']) ? '–' : 'outside top ' . HF_Rank::depth()) ?><?= $rk['ai_overview'] && $rk['ai_overview']['cited'] ? ' <small>(cited in AI Overview)</small>' : '' ?><?php else: ?><small>GSC avg <?= $rk['position'] !== null ? number_format($rk['position'], 1) : '–' ?></small><?php endif; ?></td>
				<td><?php if (!$rk || !$k['is_active'] || !$rk['local']): ?>–<?php elseif ($rk['local']['position']): ?><b>#<?= (int)$rk['local']['position'] ?></b> in pack<?php elseif ($rk['local']['present']): ?>Not in pack<?php else: ?><small>no local pack</small><?php endif; ?></td>
				<td><?= ($rk && $rk['change'] !== null) ? ($rk['change'] > 0 ? '▲ ' . $rk['change'] : ($rk['change'] < 0 ? '▼ ' . abs($rk['change']) : '=')) : '–' ?></td>
				<td><?= ($rk && $rk['checked_on']) ? hf_e(date('j M', strtotime($rk['checked_on']))) : '–' ?></td>
				<td><input form="kwf<?= (int)$k['id'] ?>" type="text" name="category" value="<?= hf_e($k['category']) ?>" size="10" list="hf-cats"></td>
				<td><input form="kwf<?= (int)$k['id'] ?>" type="text" name="target_url" value="<?= hf_e($k['target_url']) ?>" size="30"></td>
				<td>
					<form id="kwf<?= (int)$k['id'] ?>" method="post" action="<?= hf_e($self(['tab' => 'keywords'])) ?>" style="display:inline">
						<?= hf_csrf_field() ?><input type="hidden" name="action" value="update_keyword"><input type="hidden" name="keyword_id" value="<?= (int)$k['id'] ?>">
						<input type="submit" class="button button-simple" value="Save">
					</form>
					<?= $btn('toggle_keyword', $k['is_active'] ? 'Pause' : 'Resume', ['keyword_id' => $k['id'], 'active' => $k['is_active'] ? '' : '1']) ?>
				</td>
			</tr>
		<?php endforeach; if (!$kws): ?><tr><td colspan="8">No keywords yet.</td></tr><?php endif; ?>
		</tbody>
	</table>
	<?php $run_items = []; foreach ($kws as $k) if ($k['is_active']) $run_items[] = ['keyword_id' => (int)$k['id'], 'label' => $k['keyword']]; ?>
	<p>
		<?php if (!HF_Dfs::configured()): ?><em>DataForSEO credentials aren't set, so ranks can't be checked (see Settings).</em>
		<?php elseif (!$domain): ?><em>The customer hasn't entered a domain yet.</em>
		<?php elseif ($run_items): ?>
		<button type="button" class="button button-simple" data-hf-run='<?= hf_e(json_encode(['type' => 'ranks', 'items' => $run_items])) ?>' data-hf-confirm="Check UK Google rankings for <?= count($run_items) ?> keyword(s) now? About $<?= number_format(count($run_items) * ceil(HF_Rank::depth() / 10) * 0.002, 2) ?>.">Check rankings now</button>
		<span class="hf-run-status"></span> <small>Rankings also refresh automatically about weekly.</small>
		<?php endif; ?>
	</p>
	<?php if ($domain): ?>
	<form method="post" action="<?= hf_e($self(['tab' => 'keywords'])) ?>">
		<?= hf_csrf_field() ?><input type="hidden" name="action" value="set_location">
		Search location: <input type="text" name="serp_location" value="<?= hf_e($domain['serp_location'] ?? '') ?>" placeholder="<?= hf_e(HF_Rank::default_location()) ?>" size="40">
		<br>Business name: <input type="text" name="business_name" value="<?= hf_e($domain['business_name'] ?? '') ?>" placeholder="<?= hf_e(ucfirst(explode('.', $domain['domain'])[0])) ?>" size="40">
		<input type="submit" class="button button-simple" value="Save">
		<br><small>Business name is how the client appears in Google's local map pack (e.g. <code>Hello Technology</code>). It is matched loosely against the business names Google lists, so an exact match isn't needed; leave blank to use the domain name.</small>
		<br><small>Currently checking from <b><?= hf_e($loc_now) ?></b>. For a local business, a town or city gives results closer to what local customers see, e.g. <code>Whitby,England,United Kingdom</code> (must match a DataForSEO location name).</small>
	</form>
	<?php endif; ?>
	<datalist id="hf-cats"><option>local</option><option>product</option><option>service</option><option>aeo</option></datalist>
	<h2>Add keyword</h2>
	<form method="post" action="<?= hf_e($self(['tab' => 'keywords'])) ?>">
		<?= hf_csrf_field() ?><input type="hidden" name="action" value="add_keyword">
		<input type="text" name="keyword" placeholder="keyword" required>
		<input type="text" name="category" placeholder="category" list="hf-cats">
		<input type="text" name="target_url" placeholder="target URL (optional)">
		<input type="submit" class="button button-simple" value="Add">
	</form>

<?php elseif ($tab === 'prompts'):
	$prompts = HF_Ai::prompts($id); $matrix = HF_Ai::latest_matrix($id); $engines = HF_Ai::engines(); ?>
	<p>Questions this client's customers might ask an AI assistant. Each is sent to <?= hf_e(implode(', ', array_column($engines, 'label'))) ?> about weekly, with web search on, and we record whether the client's website was cited. <?= HF_Ai::active_count($id) ?> of <?= HF_Ai::max_prompts() ?> active prompts.</p>
	<p><small>API answers approximate the consumer apps but are not identical. Gemini can't be restricted to the UK; the others search as GB.</small></p>
	<table class="d">
		<thead><tr><th>Prompt</th><?php foreach ($engines as $e): ?><th><?= hf_e($e['label']) ?></th><?php endforeach; ?><th></th></tr></thead>
		<tbody>
		<?php foreach ($prompts as $p): ?>
			<tr<?= $p['is_active'] ? '' : ' style="opacity:.55"' ?>>
				<td><?= hf_e($p['prompt']) ?></td>
				<?php foreach (array_keys($engines) as $ek): $c = $matrix[(int)$p['id']][$ek] ?? null; ?>
					<td title="<?= $c ? hf_e('Checked ' . $c['checked_at'] . ($c['sources'] ? ' - sources: ' . implode(', ', array_slice(json_decode($c['sources'], true) ?: [], 0, 8)) : '')) : '' ?>">
						<?= !$c ? '–' : ($c['cited'] ? '<b>Cited</b>' : ($c['mentioned'] ? 'Named' : 'No')) ?><?= ($c && !$c['web_search']) ? ' <span title="The engine did not use web search for this answer, so it reflects the model\'s built-in knowledge only." style="color:#b3261e">⚠</span>' : '' ?>
					</td>
				<?php endforeach; ?>
				<td><?= $btn('toggle_prompt', $p['is_active'] ? 'Pause' : 'Resume', ['prompt_id' => $p['id'], 'active' => $p['is_active'] ? '' : '1']) ?></td>
			</tr>
		<?php endforeach; if (!$prompts): ?><tr><td colspan="<?= count($engines) + 2 ?>">No prompts yet.</td></tr><?php endif; ?>
		</tbody>
	</table>
	<h2>Add prompt</h2>
	<form method="post" action="<?= hf_e($self(['tab' => 'prompts'])) ?>">
		<?= hf_csrf_field() ?><input type="hidden" name="action" value="add_prompt">
		<input type="text" name="prompt" maxlength="500" size="70" placeholder="e.g. Who is the best accountant in Whitby?" required>
		<input type="submit" class="button button-simple" value="Add">
	</form>
	<?php $run_items = []; foreach ($prompts as $p) if ($p['is_active']) foreach (array_keys($engines) as $ek) $run_items[] = ['prompt_id' => (int)$p['id'], 'engine' => $ek, 'label' => $engines[$ek]['label'] . ': ' . mb_strimwidth($p['prompt'], 0, 40, '…')]; ?>
	<p>
		<?php if (!HF_Dfs::configured()): ?><em>DataForSEO credentials aren't set (see Settings).</em>
		<?php elseif (!$domain): ?><em>The customer hasn't entered a domain yet.</em>
		<?php elseif ($run_items): ?>
		<button type="button" class="button button-simple" data-hf-run='<?= hf_e(json_encode(['type' => 'ai', 'items' => $run_items])) ?>' data-hf-confirm="Run <?= count($run_items) ?> AI checks now? This uses DataForSEO credit and takes a few minutes. Keep this page open.">Run AI checks now (<?= count($run_items) ?> calls)</button>
		<span class="hf-run-status"></span>
		<?php endif; ?>
	</p>

<?php elseif ($tab === 'fathom'): ?>
	<?php if (!$domain): ?>
		<p>The customer hasn't entered their domain yet.</p>
	<?php elseif (!$domain['fathom_site_id']): ?>
		<p>No Fathom site linked yet.</p>
		<h2>Link an existing site</h2>
		<p>If the client already has Fathom, paste their site ID (shown in Fathom under Sites, or in the tracking snippet as <code>data-site</code>).</p>
		<form method="post" action="<?= hf_e($self(['tab' => 'fathom'])) ?>">
			<?= hf_csrf_field() ?><input type="hidden" name="action" value="link_fathom">
			<input type="text" name="fathom_site_id" placeholder="e.g. ABCDEFGH" maxlength="20" required>
			<input type="submit" class="button button-simple" value="Link site">
		</form>
		<h2>Or create a new one</h2>
		<?= $post_to('_get/create-fathom.php', 'Create Fathom site') ?>
	<?php else: ?>
		<p>Fathom site ID: <b><?= hf_e($domain['fathom_site_id']) ?></b></p>
		<textarea readonly rows="3" style="width:100%;font-family:monospace" onclick="this.select()"><?= hf_e($domain['fathom_tracking_code']) ?></textarea>
		<p><small>You need to add this script to the client's website <code>&lt;head&gt;</code> manually (skip if the client's site already has Fathom installed).</small></p>
		<?= $btn('unlink_fathom', 'Unlink site', [], 'Unlink this Fathom site from the client? Nothing is deleted in Fathom.') ?>
	<?php endif; ?>

<?php elseif ($tab === 'reports'):
	$reports = HF_Report::for_account($id);
	$open = isset($_GET['report']) ? HF_Report::find((int)$_GET['report'], $id) : null; ?>
	<?= $btn('create_report', 'Create report for ' . date('F Y')) ?>
	<table class="d">
		<thead><tr><th>Month</th><th>Status</th><th>Clicks</th><th>Impressions</th><th></th></tr></thead>
		<tbody>
		<?php foreach ($reports as $r): $m = json_decode($r['metrics_snapshot'] ?? '', true)['site'] ?? null; ?>
			<tr>
				<td><?= hf_e(date('F Y', strtotime($r['report_month']))) ?></td><td><?= hf_e($r['status']) ?></td>
				<td><?= $m ? number_format($m['clicks']) : '–' ?></td><td><?= $m ? number_format($m['impressions']) : '–' ?></td>
				<td><a href="<?= hf_e($self(['tab' => 'reports', 'report' => $r['id']])) ?>"><?= $r['status'] === 'draft' ? 'Edit' : 'View' ?></a></td>
			</tr>
		<?php endforeach; if (!$reports): ?><tr><td colspan="5">No reports yet.</td></tr><?php endif; ?>
		</tbody>
	</table>
	<?php if ($open): $snap = json_decode($open['metrics_snapshot'] ?? '', true) ?: []; $s = $snap['site'] ?? null; $draft = $open['status'] === 'draft'; ?>
		<h2><?= hf_e(date('F Y', strtotime($open['report_month']))) ?> report (<?= hf_e($open['status']) ?>)</h2>
		<?php if ($s): ?><p>28 days to <?= hf_e($s['window_end']) ?>: <b><?= number_format($s['clicks']) ?></b> clicks, <b><?= number_format($s['impressions']) ?></b> impressions, avg position <b><?= number_format($s['avg_position'], 1) ?></b>.</p>
		<?php else: ?><p><em>No Search Console data captured yet.</em></p><?php endif; ?>
		<?php foreach ($snap['ai'] ?? [] as $ai): ?>
			<p><?= hf_e($ai['label']) ?>: cited in <b><?= (int)$ai['cited'] ?></b> of <?= (int)$ai['prompts'] ?> prompts</p>
		<?php endforeach; ?>
		<?php if (!empty($snap['keywords'])): ?>
		<table class="d"><thead><tr><th>Keyword</th><th>Position</th><th>Local pack</th><th>Change</th><th>Clicks</th></tr></thead><tbody>
			<?php foreach ($snap['keywords'] as $k): ?>
				<tr><td><?= hf_e($k['keyword']) ?></td><td><?= $k['position'] !== null ? number_format($k['position'], 1) : '–' ?></td><td><?= !empty($k['local']['position']) ? '#' . (int)$k['local']['position'] : '–' ?></td><td><?= $k['change'] !== null ? ($k['change'] > 0 ? '+' : '') . $k['change'] : '–' ?></td><td><?= (int)$k['clicks'] ?></td></tr>
			<?php endforeach; ?></tbody></table>
		<?php endif; ?>
		<?php if ($draft): ?>
		<form method="post" action="<?= hf_e($self(['tab' => 'reports'])) ?>">
			<?= hf_csrf_field() ?><input type="hidden" name="report_id" value="<?= (int)$open['id'] ?>">
			<textarea name="summary_text" rows="8" style="width:100%" placeholder="Plain-English summary for the customer"><?= hf_e($open['summary_text']) ?></textarea>
			<p><label><input type="checkbox" name="refresh" value="1"> Refresh the numbers from the latest data when saving</label></p>
			<button class="button button-simple" name="action" value="save_report">Save draft</button>
			<button class="button button-simple" name="action" value="publish_report" onclick="return confirm('Publish to the customer dashboard?')">Publish</button>
		</form>
		<?php else: ?>
			<p><?= nl2br(hf_e($open['summary_text'])) ?></p>
		<?php endif; ?>
	<?php endif; ?>
<?php endif; ?>
<script>
(function () {
	var token = <?= json_encode(hf_csrf_token()) ?>, endpoint = <?= json_encode(hf_admin_url('_get/run-checks.php')) ?>, account = <?= (int)$id ?>;
	document.querySelectorAll('[data-hf-run]').forEach(function (btn) {
		btn.addEventListener('click', async function () {
			var cfg = JSON.parse(btn.getAttribute('data-hf-run')), status = btn.parentNode.querySelector('.hf-run-status');
			if (!confirm(btn.getAttribute('data-hf-confirm'))) return;
			btn.disabled = true; var errors = [], done = 0;
			for (var i = 0; i < cfg.items.length; i++) {
				var it = cfg.items[i];
				status.textContent = ' Checking ' + (i + 1) + ' of ' + cfg.items.length + ': ' + it.label + '…';
				var fd = new FormData();
				fd.append('hf_token', token); fd.append('account_id', account); fd.append('type', cfg.type);
				if (it.keyword_id) fd.append('keyword_id', it.keyword_id);
				if (it.prompt_id) { fd.append('prompt_id', it.prompt_id); fd.append('engine', it.engine); }
				try {
					var r = await fetch(endpoint, { method: 'POST', body: fd, credentials: 'same-origin' });
					var j = await r.json();
					if (j.ok) done++; else errors.push(it.label + ' - ' + j.error);
					if (!j.ok && /credentials|rejected|Session expired/.test(j.error || '')) break;
				} catch (e) { errors.push(it.label + ' - request failed or timed out'); }
			}
			if (errors.length) {
				status.innerHTML = ' ' + done + ' done, ' + errors.length + ' problem(s): ' + errors.map(function (m) { return m.replace(/</g, '&lt;'); }).join(' | ') + ' <a href="">Reload</a>';
				btn.disabled = false;
			} else { status.textContent = ' ' + done + ' done. Reloading…'; location.reload(); }
		});
	});
})();
</script>
<?php include(PERCH_CORE . '/inc/btm.php');
