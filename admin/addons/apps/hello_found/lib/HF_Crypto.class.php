<?php
/**
 * AES-256-CBC with encrypt-then-MAC (HMAC-SHA256).
 * Key: HELLOFOUND_ENCRYPTION_KEY, 64 hex chars (32 bytes). Payload is base64(iv|mac|ciphertext).
 */
class HF_Crypto
{
	private static function keys()
	{
		$k = defined('HELLOFOUND_ENCRYPTION_KEY') ? HELLOFOUND_ENCRYPTION_KEY : '';
		if (!preg_match('/^[0-9a-f]{64}$/i', $k)) {
			throw new RuntimeException('HELLOFOUND_ENCRYPTION_KEY must be 64 hex characters.');
		}
		$master = hex2bin($k);
		return [hash_hmac('sha256', 'enc', $master, true), hash_hmac('sha256', 'mac', $master, true)];
	}

	public static function encrypt($plain)
	{
		[$ek, $mk] = self::keys();
		$iv  = random_bytes(16);
		$ct  = openssl_encrypt($plain, 'aes-256-cbc', $ek, OPENSSL_RAW_DATA, $iv);
		$mac = hash_hmac('sha256', $iv . $ct, $mk, true);
		return base64_encode($iv . $mac . $ct);
	}

	/** @return string|false false if tampered/invalid */
	public static function decrypt($blob)
	{
		[$ek, $mk] = self::keys();
		$raw = base64_decode($blob, true);
		if ($raw === false || strlen($raw) < 49) return false;
		$iv = substr($raw, 0, 16); $mac = substr($raw, 16, 32); $ct = substr($raw, 48);
		if (!hash_equals(hash_hmac('sha256', $iv . $ct, $mk, true), $mac)) return false;
		return openssl_decrypt($ct, 'aes-256-cbc', $ek, OPENSSL_RAW_DATA, $iv);
	}
}
