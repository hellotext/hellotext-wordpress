<?php

namespace Hellotext\Api;

use Hellotext\Api\Client;

class Webchat {
    public static function index() {
        $hellotext_access_token = get_option('hellotext_access_token');

        if(!$hellotext_access_token) {
            return [];
        }

        $body = Client::with_sufix()->get('/v1/wordpress/webchats');
        return is_array($body['body']) && isset($body['body']['ids']) ? $body['body']['ids'] : [];
    }
}
