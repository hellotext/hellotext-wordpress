<?php

use Hellotext\Adapters\OrderAdapter;
use Hellotext\Api\Event;
use Hellotext\Constants;
use Hellotext\Services\Session;

// Checkout runs in the customer's browser request, so it is where the
// customer's session and profile are captured. order.placed is sent later,
// once the order is paid or on hold: from the thank-you page, or from the
// status change for block themes and customers who never return from the
// payment page.
add_action('woocommerce_checkout_order_processed', 'hellotext_checkout_order_processed', 10, 3);
add_action('woocommerce_store_api_checkout_order_processed', 'hellotext_capture_checkout', 10, 1);
add_action('woocommerce_thankyou', 'hellotext_thankyou', 10, 1);

/**
 * Capture a classic checkout order.
 *
 * @param int $order_id Order ID.
 * @param array $posted_data Checkout form data.
 * @param \WC_Order $order Order instance.
 * @return void
 */
function hellotext_checkout_order_processed(int $order_id, array $posted_data, \WC_Order $order): void {
    hellotext_capture_checkout($order);
}

/**
 * Store the customer's session on the order and create their profile.
 *
 * Must only run in the customer's own request.
 *
 * @param \WC_Order $order Order instance.
 * @return void
 */
function hellotext_capture_checkout(\WC_Order $order): void {
    $customer_id = $order->get_user_id();
    do_action('hellotext_create_profile', $customer_id > 0 ? $customer_id : $order->get_address('billing'));

    $order->update_meta_data(Constants::META_SESSION, Session::encrypt(Session::current()));

    if (!$order->get_meta(Constants::META_ORDER_PLACED, true)) {
        $order->update_meta_data(Constants::META_ORDER_PLACED, Constants::ORDER_PLACED_PENDING);
    }

    $order->save_meta_data();
}

/**
 * Track the order from the thank-you page.
 *
 * Themes and gateways fire this hook too, sometimes with a string ID, so it
 * accepts any value rather than risk breaking the page.
 *
 * @param mixed $order_id Order ID.
 * @return void
 */
function hellotext_thankyou(mixed $order_id): void {
    $order = wc_get_order($order_id);

    if (!$order) {
        return;
    }

    // Orders from checkout flows that skip the checkout hooks, such as some
    // express payment buttons. Orders tracked by earlier versions of the
    // plugin already carry a session and are left alone.
    if (!$order->get_meta(Constants::META_ORDER_PLACED, true) && !$order->get_meta(Constants::META_SESSION, true)) {
        hellotext_capture_checkout($order);
    }

    hellotext_order_placed($order);
}

/**
 * Track order placement once per order, when it is paid or on hold.
 *
 * @param \WC_Order $order WooCommerce order instance.
 * @return void
 */
function hellotext_order_placed(\WC_Order $order): void {
    if (Constants::ORDER_PLACED_PENDING !== $order->get_meta(Constants::META_ORDER_PLACED, true)) {
        return;
    }

    if (!$order->is_paid() && !$order->has_status('on-hold')) {
        return;
    }

    $order->update_meta_data(Constants::META_ORDER_PLACED, Constants::ORDER_PLACED_TRACKED);
    $order->save_meta_data();

    (new Event(Session::for_order($order)))->track(Constants::EVENT_ORDER_PLACED, [
        'object_parameters' => (new OrderAdapter($order))->get(),
    ]);
}
