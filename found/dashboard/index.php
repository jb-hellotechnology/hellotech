<?php
require __DIR__ . '/../_hf.php';

$account = HF_Account::require_active();
if (!$account['onboarding_complete']) hf_redirect(hf_url('onboarding/'));

$domain   = HF_Account::domain($account['id']);
$gsc_on   = (bool)HF_Gsc::token_row($account['id']);
$overview = $gsc_on ? HF_GscData::site_overview($account['id']) : null;
$keywords = $gsc_on ? HF_GscData::keyword_table($account['id']) : [];
$report   = HF_Report::latest_published($account['id']);
$fathom   = ($domain && $domain['fathom_site_id']) ? HF_Fathom::stats($domain['fathom_site_id']) : null;

hf_page_start('Dashboard');
?>
<h1 class="h2"><?= hf_e($domain['domain'] ?? 'Your dashboard') ?></h1>

<?php if ($report): ?>
<div class="hf-card">
  <span class="eyebrow">Latest report · <?= hf_e(date('F Y', strtotime($report['report_month']))) ?></span>
  <?php $s = $report['metrics']['site'] ?? null; if ($s): ?>
  <div class="hf-grid" style="margin:1rem 0">
    <div class="hf-stat"><b><?= hf_n($s['clicks']) ?></b><small>Clicks</small></div>
    <div class="hf-stat"><b><?= hf_n($s['impressions']) ?></b><small>Impressions</small></div>
    <div class="hf-stat"><b><?= hf_pct($s['ctr']) ?></b><small>Click-through rate</small></div>
    <div class="hf-stat"><b><?= number_format($s['avg_position'], 1) ?></b><small>Average position</small></div>
  </div>
  <?php endif; ?>
  <?= nl2br(hf_e($report['summary_text'])) ?>
</div>
<?php endif; ?>

<div class="hf-card">
  <h2 class="h3">Search overview <small>(last 28 days)</small></h2>
<?php if (!$gsc_on || !$overview): ?>
  <p>Your Search Console data will appear here once your account is fully activated.</p>
<?php else: $c = $overview['current']; $p = $overview['prior']; ?>
  <div class="hf-grid">
    <div class="hf-stat"><b><?= hf_n($c['clicks']) ?></b><small>Clicks</small><br><?= hf_delta($c['clicks'], $p['clicks'] ?? null) ?></div>
    <div class="hf-stat"><b><?= hf_n($c['impressions']) ?></b><small>Impressions</small><br><?= hf_delta($c['impressions'], $p['impressions'] ?? null) ?></div>
    <div class="hf-stat"><b><?= hf_pct($c['ctr']) ?></b><small>Click-through rate</small></div>
  </div>
<?php endif; ?>
</div>

<?php if ($gsc_on && $keywords): ?>
<div class="hf-card">
  <h2 class="h3">Keyword rankings</h2>
  <div style="overflow-x:auto"><table class="hf-table">
    <thead><tr><th>Keyword</th><th>Position</th><th>Change</th><th>Clicks</th></tr></thead>
    <tbody>
    <?php foreach ($keywords as $k):
      $ch = $k['change'];
      $cls = $ch === null ? 'hf-flat' : ($ch > 0 ? 'hf-up' : ($ch < 0 ? 'hf-down' : 'hf-flat')); ?>
      <tr>
        <td><?= hf_e($k['keyword']) ?></td>
        <td><?= $k['position'] !== null ? number_format($k['position'], 1) : '<span class="hf-flat">not yet ranking</span>' ?></td>
        <td class="<?= $cls ?>"><?= $ch === null ? '–' : ($ch > 0 ? '▲ ' . $ch : ($ch < 0 ? '▼ ' . abs($ch) : '=')) ?></td>
        <td><?= hf_n($k['clicks']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>

<?php if ($fathom): ?>
<div class="hf-card">
  <h2 class="h3">Website visitors <small>(last 30 days)</small></h2>
  <div class="hf-grid">
    <div class="hf-stat"><b><?= hf_n($fathom['visits'] ?? 0) ?></b><small>Visits</small></div>
    <div class="hf-stat"><b><?= hf_n($fathom['pageviews'] ?? 0) ?></b><small>Page views</small></div>
    <div class="hf-stat"><b><?= number_format((float)($fathom['bounce_rate'] ?? 0) * (($fathom['bounce_rate'] ?? 0) <= 1 ? 100 : 1), 0) ?>%</b><small>Bounce rate</small></div>
    <div class="hf-stat"><b><?= gmdate('i:s', (int)($fathom['avg_duration'] ?? 0)) ?></b><small>Avg. time on site</small></div>
  </div>
</div>
<?php endif; ?>

<div class="hf-card">
  <h2 class="h3">Your account</h2>
  <p><span class="hf-badge <?= hf_e($account['subscription_status']) ?>"><?= hf_e(str_replace('_', ' ', $account['subscription_status'])) ?></span>
     · <a href="<?= hf_e(hf_url('account/')) ?>">Manage subscription</a></p>
</div>
<?php hf_page_end();
