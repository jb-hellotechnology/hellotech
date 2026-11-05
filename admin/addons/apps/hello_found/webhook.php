<?php
/**
 * Stripe webhook receiver. Public URL:
 *   https://hellotechnology.co.uk/admin/addons/apps/hello_found/webhook.php
 * Register it in Stripe for: checkout.session.completed, customer.subscription.updated,
 * customer.subscription.deleted, invoice.payment_succeeded, invoice.payment_failed.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

include(__DIR__ . '/../../../runtime.php');   // boots Perch (DB, Members) + loads hello_found
require_once __DIR__ . '/bootstrap.php';

if (!defined('HELLOFOUND_STRIPE_WEBHOOK_SECRET')) { hf_log('webhook secret not configured'); http_response_code(500); exit; }

$payload = file_get_contents('php://input');
$event = HF_Stripe::verify_webhook($payload, $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '', HELLOFOUND_STRIPE_WEBHOOK_SECRET);
if (!$event) { http_response_code(400); exit('Invalid signature'); }

try {
	HF_Stripe::handle_event($event);
} catch (Throwable $e) {
	// Log without payload/PII. Return 500 so Stripe retries.
	hf_log('webhook handler error on ' . ($event['type'] ?? '?') . ': ' . $e->getMessage());
	http_response_code(500);
	exit;
}
http_response_code(200);
echo 'ok';
