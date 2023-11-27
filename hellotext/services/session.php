<?php

namespace Hellotext\Services;

class Session {
    const METHOD = 'aes-256-cbc';

    public static function encrypt ($session = null) {
        $key = get_option('hellotext_bussiness_id');

        // Generate an initialization vector (IV)
        $iv_length = openssl_cipher_iv_length(self::METHOD);
        $iv = openssl_random_pseudo_bytes($iv_length);

        $encrypted = openssl_encrypt($session, self::METHOD, $key, 0, $iv);

        return base64_encode($encrypted . '::' . $iv);
    }

    public static function decrypt ($encryptedData = null) {
        $key = get_option('hellotext_bussiness_id');
        $parts = explode('::', base64_decode($encryptedData));

        return openssl_decrypt($parts[0], self::METHOD, $key, 0, $parts[1]);
    }
}
