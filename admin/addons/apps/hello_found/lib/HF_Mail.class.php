<?php
/** Plain-text email via PHP mail(), using Perch's configured sender. */
class HF_Mail
{
	public static function send($to, $subject, $body)
	{
		$from_email = defined('PERCH_EMAIL_FROM') ? PERCH_EMAIL_FROM : HELLOFOUND_NOTIFY_EMAIL;
		$from_name  = defined('PERCH_EMAIL_FROM_NAME') ? PERCH_EMAIL_FROM_NAME : 'hello.found';
		// Strip CR/LF to prevent header injection
		$clean = function ($s) { return str_replace(["\r", "\n"], ' ', $s); };
		$headers = 'From: ' . $clean($from_name) . ' <' . $clean($from_email) . ">\r\n"
		         . "Content-Type: text/plain; charset=UTF-8\r\n";
		$ok = @mail($clean($to), '=?UTF-8?B?' . base64_encode($clean($subject)) . '?=', $body, $headers);
		if (!$ok) hf_log('mail() failed for subject: ' . $subject);
		return $ok;
	}

	public static function admin($subject, $body) { return self::send(HELLOFOUND_NOTIFY_EMAIL, $subject, $body); }
}
