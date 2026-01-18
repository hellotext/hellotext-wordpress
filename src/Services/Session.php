<?php

namespace Hellotext\Services;

use Hellotext\Constants;

class Session {

	public static function encrypt ($session = null) {
		$key = get_option(Constants::OPTION_BUSINESS_ID);

		// Generate an initialization vector (IV)
		$iv_length = openssl_cipher_iv_length(Constants::ENCRYPTION_METHOD);
		$iv = openssl_random_pseudo_bytes($iv_length);

		$encrypted = openssl_encrypt($session, Constants::ENCRYPTION_METHOD, $key, 0, $iv);

		return base64_encode($encrypted . '::' . $iv);
	}

	public static function decrypt ($encrypted_data = null) {
		$key = get_option(Constants::OPTION_BUSINESS_ID);
		$parts = explode('::', base64_decode($encrypted_data));

		return openssl_decrypt($parts[0], Constants::ENCRYPTION_METHOD, $key, 0, $parts[1]);
	}
}
