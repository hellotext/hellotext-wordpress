<?php

use Hellotext\Api\Event;
use Hellotext\Constants;
use Hellotext\Services\Session;

add_action('template_redirect', 'hellotext_checkout_started');

/**
 * Track the checkout page, once per cart.
 *
 * Reloading the checkout with the same cart is the same checkout. A changed
 * cart starts a new one.
 *
 * @return void
 */
function hellotext_checkout_started(): void {
    if (!function_exists('WC') || !is_checkout() || is_wc_endpoint_url('order-received') || is_wc_endpoint_url('order-pay')) {
        return;
    }

    $woocommerce = WC();

    if (!$woocommerce->session || !$woocommerce->cart || $woocommerce->cart->is_empty()) {
        return;
    }

    $cart_hash = $woocommerce->cart->get_cart_hash();

    if ($woocommerce->session->get(Constants::SESSION_CHECKOUT_STARTED) === $cart_hash) {
        return;
    }

    $woocommerce->session->set(Constants::SESSION_CHECKOUT_STARTED, $cart_hash);

    do_action('hellotext_create_profile');

    (new Event(Session::current()))->track(Constants::EVENT_CHECKOUT_STARTED, [
        'object_parameters' => hellotext_cart_object_parameters(),
    ]);
}
