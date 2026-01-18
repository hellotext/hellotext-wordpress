<?php

namespace Hellotext\Api;

use Hellotext\Api\Client;
use Hellotext\Constants;

class Webchat {
    public static function index() {
        $hellotext_access_token = get_option(Constants::OPTION_ACCESS_TOKEN);

        if(!$hellotext_access_token) {
            return [];
        }

        $body = Client::with_sufix()->get(Constants::API_ENDPOINT_WEBCHATS);
        return is_array($body['body']) && isset($body['body']['ids']) ? $body['body']['ids'] : [];
    }
}
