<?php
/** Minimal cURL client. Returns ['status'=>int, 'json'=>array|null, 'raw'=>string]. */
class HF_Http
{
	public static function request($method, $url, array $headers = [], $body = null, $timeout = 20)
	{
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_CUSTOMREQUEST  => $method,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => $timeout,
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_SSL_VERIFYPEER => true,
		]);
		if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		$raw    = curl_exec($ch);
		$status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		if ($raw === false) { hf_log('HTTP error: ' . curl_error($ch)); $raw = ''; }
		curl_close($ch);
		return ['status' => $status, 'json' => json_decode($raw, true), 'raw' => $raw];
	}
}
