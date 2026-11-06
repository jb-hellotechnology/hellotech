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

// DataForSEO: API login + API PASSWORD from https://app.dataforseo.com/api-access
// (the API password is generated there; it is NOT your account login password).
define('HELLOFOUND_DFS_LOGIN',             '...');
define('HELLOFOUND_DFS_PASSWORD',          '...');

// Optional overrides (defaults shown in bootstrap.php)
// define('HELLOFOUND_SITE_URL',   'https://hellotechnology.co.uk');
// define('HELLOFOUND_LOGIN_URL',  '/members/login');
// define('HELLOFOUND_RESET_URL',  '/members/reset');
// define('HELLOFOUND_DFS_BASE',    'https://sandbox.dataforseo.com');   // free test mode: no real data, no spend
// define('HELLOFOUND_SERP_DEPTH',  100);                                // results checked per keyword (10 per SERP credit)
// define('HELLOFOUND_MAX_PROMPTS', 10);                                 // active AI prompts per client
// define('HELLOFOUND_AI_ENGINES',  ['chat_gpt', 'gemini', 'perplexity']);
// define('HELLOFOUND_AI_MODELS',   ['chat_gpt' => 'gpt-4.1', 'gemini' => 'gemini-2.5-flash', 'perplexity' => 'sonar']);
// define('HELLOFOUND_AI_FORCE_WEB', true);   // ChatGPT: require a web search (set false if your chosen model rejects it)
