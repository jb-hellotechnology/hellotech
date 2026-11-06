<?php
require __DIR__ . '/../_hf.php';

$account = HF_Account::require_active();
if (!$account['onboarding_complete']) hf_redirect(hf_url('onboarding/'));

$domain   = HF_Account::domain($account['id']);
$gsc_on   = (bool)HF_Gsc::token_row($account['id']);
$overview = $gsc_on ? HF_GscData::site_overview($account['id']) : null;
$keywords = HF_GscData::keyword_table($account['id']);
$report   = HF_Report::latest_published($account['id']);
$ai       = HF_Ai::summary($account['id']);
$local_present = array_values(array_filter($keywords, function ($k) { return $k['local'] && $k['local']['present']; }));
$local_hits    = array_values(array_filter($local_present, function ($k) { return $k['local']['position']; }));
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

<?php if ($local_present): ?>
<div class="hf-card hf-local-card">
  <span class="eyebrow">Local map results</span>
  <?php if ($local_hits): ?>
  <h2 class="h3">You're in Google's local map pack for <?= count($local_hits) ?> of <?= count($local_present) ?> local search<?= count($local_present) === 1 ? '' : 'es' ?></h2>
  <p>
    <?php foreach ($local_hits as $k): ?>
      <span class="hf-pill">#<?= (int)$k['local']['position'] ?> · <?= hf_e($k['keyword']) ?></span>
    <?php endforeach; ?>
  </p>
  <p>The local pack is the map with three businesses at the top of local searches. People who see it tend to call, get directions or visit, so these placements are among the most valuable results you can have.</p>
  <?php else: ?>
  <h2 class="h3">Google shows a local map pack for <?= count($local_present) ?> of your searches, but you're not in it yet</h2>
  <p>The local pack is the map with three businesses at the top of local searches, and a strong source of calls and visits. Getting into it is part of what we're working on.</p>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($keywords): ?>
<div class="hf-card">
  <h2 class="h3">Google rankings <small>(UK)</small></h2>
  <div style="overflow-x:auto"><table class="hf-table">
    <thead><tr><th>Keyword</th><th>Position</th><th>Local map pack</th><th>Change</th><th>Clicks <small>(28 days)</small></th></tr></thead>
    <tbody>
    <?php foreach ($keywords as $k):
      $ch = $k['change'];
      $cls = $ch === null ? 'hf-flat' : ($ch > 0 ? 'hf-up' : ($ch < 0 ? 'hf-down' : 'hf-flat')); ?>
      <tr<?= ($k['local'] && $k['local']['position']) ? ' class="hf-local-row"' : '' ?>>
        <td><?= hf_e($k['keyword']) ?></td>
        <td>
          <?php if ($k['position'] !== null): ?>
            <?= $k['source'] === 'rank' ? '#' . (int)$k['position'] : number_format($k['position'], 1) ?>
          <?php elseif ($k['local'] && $k['local']['position']): ?>
            <span class="hf-flat">–</span>
          <?php elseif ($k['source'] === 'rank'): ?>
            <span class="hf-flat">Not in top <?= (int)HF_Rank::depth() ?></span>
          <?php else: ?>
            <span class="hf-flat">not yet ranking</span>
          <?php endif; ?>
          <?php if ($k['ai_overview'] && $k['ai_overview']['cited']): ?><br><small class="hf-up">Cited in Google's AI Overview</small><?php endif; ?>
        </td>
        <td>
          <?php if ($k['local'] && $k['local']['position']): ?><span class="hf-pill">#<?= (int)$k['local']['position'] ?> in map pack</span>
          <?php elseif ($k['local'] && $k['local']['present']): ?><span class="hf-pill none">Not in pack</span>
          <?php else: ?><span class="hf-flat">–</span><?php endif; ?>
        </td>
        <td class="<?= $cls ?>"><?= $ch === null ? '–' : ($ch > 0 ? '▲ ' . $ch : ($ch < 0 ? '▼ ' . abs($ch) : '=')) ?></td>
        <td><?= hf_n($k['clicks']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <p><small>Positions are checked weekly from Google UK desktop results.</small></p>
</div>
<?php endif; ?>

<?php if ($ai): ?>
<div class="hf-card">
  <h2 class="h3">AI search visibility</h2>
  <p>We ask AI assistants the questions your customers might ask and check whether your website is cited as a source.</p>
  <div class="hf-grid">
  <?php foreach ($ai as $a): ?>
    <div class="hf-stat"><b><?= (int)$a['cited'] ?> / <?= (int)$a['prompts'] ?></b><small><?= hf_e($a['label']) ?>: questions where you were cited</small></div>
  <?php endforeach; ?>
  </div>
  <p style="margin-top:1rem"><small>Based on the latest weekly check. AI answers vary from one ask to the next, so treat this as a trend, not an exact count.</small></p>
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
