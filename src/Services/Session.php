<?php

namespace Hellotext\Services;

use Hellotext\Constants;

/**
 * Session
 *
 * Handles encryption and decryption for session identifiers.
 *
 * @package Hellotext\Services
 */
class Session {
    /**
     * Encrypt a session identifier.
     *
     * @param string|null $session Session identifier.
     * @return string
     */
    public static function encrypt(?string $session = null): string {
        $key = get_option(Constants::OPTION_BUSINESS_ID);

        // Use empty string as fallback if key is not set
        if (!$key) {
            $key = '';
        }

        // Generate an initialization vector (IV)
        $iv_length = openssl_cipher_iv_length(Constants::ENCRYPTION_METHOD);
        $iv = openssl_random_pseudo_bytes($iv_length);

        $encrypted = openssl_encrypt($session ?? '', Constants::ENCRYPTION_METHOD, $key, 0, $iv);

        return base64_encode($encrypted . '::' . $iv);
    }

    /**
     * Decrypt an encrypted session identifier.
     *
     * @param string|null $encrypted_data Encrypted session data.
     * @return string|false
     */
    public static function decrypt(?string $encrypted_data = null): string|false {
        $key = get_option(Constants::OPTION_BUSINESS_ID);

        // Use empty string as fallback if key is not set
        if (!$key) {
            $key = '';
        }

        $parts = explode('::', base64_decode($encrypted_data ?? ''));

        return openssl_decrypt($parts[0], Constants::ENCRYPTION_METHOD, $key, 0, $parts[1]);
    }
}
