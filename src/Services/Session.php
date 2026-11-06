<?php

namespace Hellotext\Services;

use Hellotext\Constants;

/**
 * Session
 *
 * Reads the Hellotext.js session and handles encryption and decryption for
 * the copy stored on orders.
 *
 * @package Hellotext\Services
 */
class Session {
    /**
     * The session Hellotext.js set in the current visitor's browser.
     *
     * @return string|null
     */
    public static function current(): ?string {
        $session = isset($_COOKIE[Constants::SESSION_COOKIE_NAME])
            ? sanitize_text_field(wp_unslash($_COOKIE[Constants::SESSION_COOKIE_NAME]))
            : '';

        return '' === $session ? null : $session;
    }

    /**
     * The session stored on an order at checkout.
     *
     * @param \WC_Order $order Order instance.
     * @return string|null
     */
    public static function for_order(\WC_Order $order): ?string {
        $session = self::decrypt($order->get_meta(Constants::META_SESSION, true));

        return is_string($session) && '' !== $session ? $session : null;
    }

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
     * @param mixed $encrypted_data Encrypted session data.
     * @return string|false
     */
    public static function decrypt(mixed $encrypted_data = null): string|false {
        if (!is_string($encrypted_data) || '' === $encrypted_data) {
            return false;
        }

        $key = get_option(Constants::OPTION_BUSINESS_ID);

        // Use empty string as fallback if key is not set
        if (!$key) {
            $key = '';
        }

        $parts = explode('::', (string) base64_decode($encrypted_data), 2);

        if (2 !== count($parts)) {
            return false;
        }

        $iv_length = openssl_cipher_iv_length(Constants::ENCRYPTION_METHOD);

        if (strlen($parts[1]) !== $iv_length) {
            return false;
        }

        return openssl_decrypt($parts[0], Constants::ENCRYPTION_METHOD, $key, 0, $parts[1]);
    }
}
