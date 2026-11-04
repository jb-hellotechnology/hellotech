<?php require_once __DIR__ . '/schema-lib.php'; ?>
<?php /* Shared <head> assets — fonts, stylesheet, icon library.
   Included from inside the <head> of each master page template. */ ?>
<?php
  // Canonical URL: always the non-www https URL, no query string.
  // Skipped on error pages (non-200) so 404s etc. don't canonicalise to themselves.
  if (http_response_code() === 200):
    $canon_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $canon_path = preg_replace('#/index\.php$#', '/', $canon_path);
    if ($canon_path !== '/' && substr($canon_path, -1) === '/') $canon_path = rtrim($canon_path, '/');
?>
<link rel="canonical" href="https://hellotechnology.co.uk<?php echo htmlspecialchars($canon_path, ENT_QUOTES, 'UTF-8'); ?>">
<?php endif; ?>
<link rel="icon" href="/assets/icon.ico" type="image/svg+xml">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php $fonts_url = 'https://fonts.googleapis.com/css2?family=Work+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=IBM+Plex+Mono:wght@400;500;600&family=Bitter:ital,wght@0,400;0,500;1,400&display=swap'; ?>
<?php /* Load fonts without blocking first paint (swap to rel=stylesheet once loaded) */ ?>
<link rel="stylesheet" href="<?php echo $fonts_url; ?>" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="<?php echo $fonts_url; ?>"></noscript>
<link rel="stylesheet" href="/styles.css">
<script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js" defer></script>
