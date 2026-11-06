<?php

use Hellotext\Api\Client;
use Hellotext\Constants;

/**
 * Disconnect the store when the plugin is deactivated.
 *
 * @return void
 */
function hellotext_deactivate(): void {
    hellotext_disconnect();
}

/**
 * Remove the integration from Hellotext and revoke its WooCommerce API key.
 *
 * @param string|null $business_id Business to disconnect, the saved one by default.
 * @return void
 */
function hellotext_disconnect(?string $business_id = null): void {
    $business_id ??= get_option(Constants::OPTION_BUSINESS_ID);

    if ($business_id && get_option(Constants::OPTION_ACCESS_TOKEN)) {
        Client::delete(Constants::API_ENDPOINT_INTEGRATIONS_WOO, [
            'shop' => [
                'business_id' => $business_id,
            ],
        ]);
    }

    hellotext_delete_api_keys();
}
