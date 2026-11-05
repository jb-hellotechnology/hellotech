<?php
	include('../../../core/inc/api.php');
	$API  = new PerchAPI(1.0, 'hello_found');
	$HTML = $API->get('HTML');
	$Lang = $API->get('Lang');
	require __DIR__ . '/_admin.php';
	hf_admin_subnav($CurrentUser);

	$Perch->page_title = $Lang->get('hello.found clients');
	$filter = $_GET['status'] ?? '';
	$clients = HF_Account::all_for_admin($filter);

	include(PERCH_CORE . '/inc/top.php');
?>
<h1>hello.found clients</h1>
<?= hf_flash_html() ?>
<p>
	Filter:
	<?php foreach (['' => 'All', 'active' => 'Active', 'past_due' => 'Past due', 'canceled' => 'Canceled'] as $k => $label): ?>
		<a href="<?= hf_e(hf_admin_url('index.php', $k ? ['status' => $k] : [])) ?>"><?= $filter === $k ? '<b>' . hf_e($label) . '</b>' : hf_e($label) ?></a>&nbsp;
	<?php endforeach; ?>
</p>
<table class="d">
	<thead><tr><th>Customer</th><th>Domain</th><th>Status</th><th>GSC</th><th>Fathom</th><th>Last report</th><th>Joined</th></tr></thead>
	<tbody>
	<?php foreach ($clients as $c): $link = hf_e(hf_admin_url('client.php', ['id' => $c['id']])); ?>
		<tr>
			<td><a href="<?= $link ?>"><?= hf_e($c['name']) ?></a><br><small><?= hf_e($c['email']) ?></small></td>
			<td><?= $c['domain'] ? hf_e($c['domain']) : '<em>awaiting domain</em>' ?></td>
			<td><?= hf_e(str_replace('_', ' ', $c['subscription_status'])) ?></td>
			<td><?= $c['gsc_connected'] ? 'Yes' : 'No' ?></td>
			<td><?= $c['fathom_site_id'] ? 'Yes' : 'No' ?></td>
			<td><?= $c['last_report'] ? hf_e(date('M Y', strtotime($c['last_report']))) : '–' ?></td>
			<td><?= hf_e(date('j M Y', strtotime($c['created_at']))) ?></td>
		</tr>
	<?php endforeach; if (!$clients): ?>
		<tr><td colspan="7">No clients yet.</td></tr>
	<?php endif; ?>
	</tbody>
</table>
<?php include(PERCH_CORE . '/inc/btm.php');
