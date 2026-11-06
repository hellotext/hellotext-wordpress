<?php

use Hellotext\Api\Event;
use Hellotext\Constants;
use Hellotext\Services\Session;

add_action('woocommerce_applied_coupon', 'hellotext_coupon_redeemed', 10, 1);

/**
 * Track coupon redemption.
 *
 * @param string $code Coupon code.
 * @return void
 */
function hellotext_coupon_redeemed(string $code): void {
    $coupon = new \WC_Coupon($code);
    $discounts = new \WC_Discounts(WC()->cart);

    if (true !== $discounts->is_coupon_valid($coupon)) {
        return;
    }

    do_action('hellotext_create_profile');

    (new Event(Session::current()))->track(Constants::EVENT_COUPON_REDEEMED, [
        'object_parameters' => [
            'type' => 'coupon',
            'reference' => $coupon->get_id(),
            'code' => $code,
            'description' => $coupon->get_description(),
            'destination_url' => wc_get_cart_url(),
        ],
    ]);
}
