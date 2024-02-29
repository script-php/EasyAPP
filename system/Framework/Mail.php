<?php

/**
* @package      Mail
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class Mail {

	public function send(array $data = []) {

		$data_to = !empty($data['to']) ? $data['to'] : NULL;
		$data_from = !empty($data['from']) ? $data['from'] : NULL;
		$data_sender = !empty($data['sender']) ? $data['sender'] : NULL;
		$data_reply_to = !empty($data['reply_to']) ? $data['reply_to'] : NULL;
		$data_subject = !empty($data['subject']) ? $data['subject'] : NULL;
		$data_text = !empty($data['text']) ? $data['text'] : NULL;
		$data_html = !empty($data['html']) ? $data['html'] : NULL;
		$data_attachments = !empty($data['attachments']) ? $data['attachments'] : NULL;

		if (is_array($data_to)) {
			$to = implode(',', $data_to);
		} else {
			$to = $data_to;
		}

		if (version_compare(phpversion(), '8.0', '>=') || substr(PHP_OS, 0, 3) == 'WIN') {
			$eol = "\r\n";
		} else {
			$eol = PHP_EOL;
		}

		$boundary = '----=_NextPart_' . md5(time());
		$header  = 'MIME-Version: 1.0' . $eol;
		$header .= 'Date: ' . date('D, d M Y H:i:s O') . $eol;
		$header .= 'From: =?UTF-8?B?' . base64_encode($data_sender) . '?= <' . $data_from . '>' . $eol;
		if (!$data_reply_to) {
			$header .= 'Reply-To: =?UTF-8?B?' . base64_encode($data_sender) . '?= <' . $data_from . '>' . $eol;
		} else {
			$header .= 'Reply-To: =?UTF-8?B?' . base64_encode($data_reply_to) . '?= <' . $data_reply_to . '>' . $eol;
		}
		$header .= 'Return-Path: ' . $data_from . $eol;
		$header .= 'X-Mailer: PHP/' . phpversion() . $eol;
		$header .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . $eol . $eol;
		if (!$data_html) {
			$message  = '--' . $boundary . $eol;
			$message .= 'Content-Type: text/plain; charset="utf-8"' . $eol;
			$message .= 'Content-Transfer-Encoding: base64' . $eol . $eol;
			$message .= base64_encode($data_text) . $eol;
		} else {
			$message  = '--' . $boundary . $eol;
			$message .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '_alt"' . $eol . $eol;
			$message .= '--' . $boundary . '_alt' . $eol;
			$message .= 'Content-Type: text/plain; charset="utf-8"' . $eol;
			$message .= 'Content-Transfer-Encoding: base64' . $eol . $eol;
			if ($data_text) {
				$message .= base64_encode($data_text) . $eol;
			} else {
				$message .= base64_encode('This is a HTML email and your email client software does not support HTML email!') . $eol;
			}
			$message .= '--' . $boundary . '_alt' . $eol;
			$message .= 'Content-Type: text/html; charset="utf-8"' . $eol;
			$message .= 'Content-Transfer-Encoding: base64' . $eol . $eol;
			$message .= base64_encode($data_html) . $eol;
			$message .= '--' . $boundary . '_alt--' . $eol;
		}
		if(!empty($data_attachments)) {
			foreach ($data_attachments as $attachment) {
				if (is_file($attachment)) {
					$handle = fopen($attachment, 'r');
					$content = fread($handle, filesize($attachment));
					fclose($handle);
					$message .= '--' . $boundary . $eol;
					$message .= 'Content-Type: application/octet-stream; name="' . basename($attachment) . '"' . $eol;
					$message .= 'Content-Transfer-Encoding: base64' . $eol;
					$message .= 'Content-Disposition: attachment; filename="' . basename($attachment) . '"' . $eol;
					$message .= 'Content-ID: <' . urlencode(basename($attachment)) . '>' . $eol;
					$message .= 'X-Attachment-Id: ' . urlencode(basename($attachment)) . $eol . $eol;
					$message .= chunk_split(base64_encode($content));
				}
			}
		}
		$message .= '--' . $boundary . '--' . $eol;
		ini_set('sendmail_from', $data_from);
		return mail($to, '=?UTF-8?B?' . base64_encode($data_subject) . '?=', $message, $header);
	}

}