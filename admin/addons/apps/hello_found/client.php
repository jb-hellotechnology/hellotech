<?php
	include('../../../core/inc/api.php');
	$API  = new PerchAPI(1.0, 'hello_found');
	$Lang = $API->get('Lang');
	require __DIR__ . '/_admin.php';
	hf_admin_subnav($CurrentUser);

	$id = (int)($_GET['id'] ?? 0);
	$account = HF_Account::find($id);
	if (!$account) { PerchUtil::redirect(hf_admin_url('index.php')); }

	$tabs = ['account' => 'Account', 'gsc' => 'Search Console', 'keywords' => 'Keywords', 'fathom' => 'Fathom', 'reports' => 'Reports'];
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

			case 'disconnect_gsc':
				HF_Gsc::disconnect($id);
				$go_tab = 'gsc'; hf_flash('Search Console disconnected.');
				break;

			case 'detect_gsc':
				$site = HF_Gsc::detect_property($id);
				$go_tab = 'gsc'; hf_flash($site ? "Property found: $site" : 'No matching property found.', $site ? 'success' : 'error');
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
	$kws = HF_Keyword::for_account($id); ?>
	<p><?= HF_Keyword::active_count($id) ?> of <?= (int)HELLOFOUND_MAX_KEYWORDS ?> active keywords.</p>
	<table class="d">
		<thead><tr><th>Keyword</th><th>Category</th><th>Target URL</th><th></th></tr></thead>
		<tbody>
		<?php foreach ($kws as $k): ?>
			<tr<?= $k['is_active'] ? '' : ' style="opacity:.55"' ?>>
				<td><?= hf_e($k['keyword']) ?></td>
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
		<?php endforeach; if (!$kws): ?><tr><td colspan="4">No keywords yet.</td></tr><?php endif; ?>
		</tbody>
	</table>
	<datalist id="hf-cats"><option>local</option><option>product</option><option>service</option><option>aeo</option></datalist>
	<h2>Add keyword</h2>
	<form method="post" action="<?= hf_e($self(['tab' => 'keywords'])) ?>">
		<?= hf_csrf_field() ?><input type="hidden" name="action" value="add_keyword">
		<input type="text" name="keyword" placeholder="keyword" required>
		<input type="text" name="category" placeholder="category" list="hf-cats">
		<input type="text" name="target_url" placeholder="target URL (optional)">
		<input type="submit" class="button button-simple" value="Add">
	</form>

<?php elseif ($tab === 'fathom'): ?>
	<?php if (!$domain): ?>
		<p>The customer hasn't entered their domain yet.</p>
	<?php elseif (!$domain['fathom_site_id']): ?>
		<p>No Fathom site yet.</p>
		<?= $post_to('_get/create-fathom.php', 'Create Fathom site') ?>
	<?php else: ?>
		<p>Fathom site ID: <b><?= hf_e($domain['fathom_site_id']) ?></b></p>
		<textarea readonly rows="3" style="width:100%;font-family:monospace" onclick="this.select()"><?= hf_e($domain['fathom_tracking_code']) ?></textarea>
		<p><small>You need to add this script to the client's website <code>&lt;head&gt;</code> manually.</small></p>
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
		<?php if (!empty($snap['keywords'])): ?>
		<table class="d"><thead><tr><th>Keyword</th><th>Position</th><th>Change</th><th>Clicks</th></tr></thead><tbody>
			<?php foreach ($snap['keywords'] as $k): ?>
				<tr><td><?= hf_e($k['keyword']) ?></td><td><?= $k['position'] !== null ? number_format($k['position'], 1) : '–' ?></td><td><?= $k['change'] !== null ? ($k['change'] > 0 ? '+' : '') . $k['change'] : '–' ?></td><td><?= (int)$k['clicks'] ?></td></tr>
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
<?php include(PERCH_CORE . '/inc/btm.php');
