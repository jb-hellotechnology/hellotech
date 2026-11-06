<?php
/** Shared bootstrap + layout for the hello.found customer pages. */
include(__DIR__ . '/../admin/runtime.php');   // boots Perch (DB, sessions, Members)
require_once __DIR__ . '/../admin/addons/apps/hello_found/bootstrap.php';
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

function hf_page_start($title)
{ ?><!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= hf_e($title) ?> · hello.found</title>
<link rel="icon" href="/assets/logo-primary.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Work+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=IBM+Plex+Mono:wght@400;500;600&family=Bitter:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/styles.css">
<style>
.hf-wrap{max-width:960px;margin:0 auto;padding:2.5rem 1rem 4rem}
.hf-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;flex-wrap:wrap;gap:1rem}
.hf-top nav a{margin-left:1rem}
.hf-card{background:#fff;border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,.08)}
.hf-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem}
.hf-stat b{display:block;font-size:1.8rem;line-height:1.2}
.hf-stat small{color:#666}
.hf-up{color:#1a7f4b}.hf-down{color:#b3261e}.hf-flat{color:#666}
.hf-badge{display:inline-block;padding:.15rem .6rem;border-radius:999px;font-size:.85rem;font-weight:600;background:#eee}
.hf-badge.active,.hf-badge.trialing{background:#d9f2e3;color:#145c37}
.hf-badge.past_due,.hf-badge.incomplete{background:#fdebc8;color:#7a4b00}
.hf-badge.canceled{background:#f7d4d1;color:#8a1c14}
.hf-table{width:100%;border-collapse:collapse}
.hf-table th,.hf-table td{text-align:left;padding:.55rem .5rem;border-bottom:1px solid #eee}
.hf-notice{padding:.8rem 1rem;border-radius:8px;background:#fdebc8;margin-bottom:1.5rem}
.hf-form input[type=text],.hf-form input[type=password]{width:100%;padding:.7rem;border:1px solid #bbb;border-radius:8px;font:inherit;margin:.4rem 0 1rem}
.hf-err{color:#b3261e}
</style>
</head>
<body>
<main id="main" class="hf-wrap">
<div class="hf-top">
  <a href="/"><img src="/assets/logo-primary.svg" alt="hellotechnology" width="105" height="44"></a>
  <nav><?php if (HF_Members::logged_in()): ?><a href="<?= hf_e(hf_url('dashboard/')) ?>">Dashboard</a><a href="<?= hf_e(hf_url('account/')) ?>">Subscription</a><a href="<?= hf_e(hf_url('signout/')) ?>">Sign out</a><?php endif; ?></nav>
</div>
<?php }

function hf_page_end()
{ ?></main></body></html><?php }

function hf_n($v) { return number_format((int)$v); }
function hf_pct($v) { return number_format($v * 100, 1) . '%'; }

/** "+12%" style change with colour class. */
function hf_delta($cur, $prev)
{
	if ($prev === null || $prev == 0) return '';
	$d = ($cur - $prev) / $prev * 100;
	$cls = $d > 0.5 ? 'hf-up' : ($d < -0.5 ? 'hf-down' : 'hf-flat');
	return '<small class="' . $cls . '">' . ($d >= 0 ? '+' : '') . round($d) . '% vs prior 28 days</small>';
}
