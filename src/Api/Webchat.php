<?php

namespace Hellotext\Api;

use Hellotext\Constants;

/**
 * Webchat
 *
 * Retrieves webchat configuration from the Hellotext API.
 *
 * @package Hellotext\Api
 */
class Webchat {
    /**
     * Fetch webchat IDs for the current business.
     *
     * @return array
     */
    public static function index(): array {
        $hellotext_access_token = get_option(Constants::OPTION_ACCESS_TOKEN);

        if (!$hellotext_access_token) {
            return [];
        }

        $body = Client::with_sufix()->get(Constants::API_ENDPOINT_WEBCHATS);
        return is_array($body['body']) && isset($body['body']['ids']) ? $body['body']['ids'] : [];
    }
}
