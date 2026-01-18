<?php

use Hellotext\Api\Client;
use Hellotext\Constants;

/**
 * Trigger integration removal on plugin deactivation.
 *
 * @param string|null $hellotext_business_id Business ID token.
 * @return void
 */
function hellotext_deactivate(?string $hellotext_business_id = null): void {
    if (!$hellotext_business_id) {
        $hellotext_business_id = get_option(Constants::OPTION_BUSINESS_ID);
    }

    if (!$hellotext_business_id) {
        return;
    }

    do_action('hellotext_remove_integration', $hellotext_business_id);
}

/**
 * Remove WooCommerce integration from Hellotext.
 *
 * @param mixed $business_id Business ID token.
 * @return void
 */
add_action('hellotext_remove_integration', function (mixed $business_id): void {
    Client::with_sufix()
        ->delete(Constants::API_ENDPOINT_INTEGRATIONS_WOO, [
        'shop' => [
            'business_id' => $business_id,
            ],
        ]);
});
