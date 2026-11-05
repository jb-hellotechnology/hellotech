<?php
/**
 * Copy to config.php (gitignored) and fill in. Never commit config.php.
 * Use Stripe TEST keys on dev/staging.
 */
define('HELLOFOUND_STRIPE_SECRET',         'sk_test_...');
define('HELLOFOUND_STRIPE_WEBHOOK_SECRET', 'whsec_...');
define('HELLOFOUND_STRIPE_PRICE_ID',       'price_...');   // the £99/month price
define('HELLOFOUND_GSC_CLIENT_ID',         '...apps.googleusercontent.com');
define('HELLOFOUND_GSC_CLIENT_SECRET',     '...');
define('HELLOFOUND_FATHOM_API_KEY',        '...');
// 64 hex chars (32 bytes). Generate: php -r "echo bin2hex(random_bytes(32));"
// Ideally define this in a file ABOVE the web root and require it here.
define('HELLOFOUND_ENCRYPTION_KEY',        '');
define('HELLOFOUND_NOTIFY_EMAIL',          'jack@hellotechnology.co.uk');
define('HELLOFOUND_MAX_KEYWORDS',          20);

// Optional overrides (defaults shown in bootstrap.php)
// define('HELLOFOUND_SITE_URL',   'https://hellotechnology.co.uk');
// define('HELLOFOUND_LOGIN_URL',  '/members/login');
// define('HELLOFOUND_RESET_URL',  '/members/reset');
