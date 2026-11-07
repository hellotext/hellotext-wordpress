<?php

use Hellotext\Adapters\OrderAdapter;
use Hellotext\Api\Event;
use Hellotext\Constants;
use Hellotext\Services\Session;

add_action('woocommerce_order_status_changed', 'hellotext_order_status_changed', 10, 4);

/**
 * Track order status transitions.
 *
 * Runs from wp-admin and payment callbacks too, so it only uses the session
 * stored on the order and never creates profiles.
 *
 * @param int $order_id Order ID.
 * @param string $old_status Previous status.
 * @param string $new_status New status.
 * @param \WC_Order $order WooCommerce order instance.
 * @return void
 */
function hellotext_order_status_changed(int $order_id, string $old_status, string $new_status, \WC_Order $order): void {
    if (in_array($new_status, ['processing', 'on-hold', 'completed'], true)) {
        hellotext_order_placed($order);
    }

    $actions = [
        'processing' => Constants::EVENT_ORDER_CONFIRMED,
        'cancelled' => Constants::EVENT_ORDER_CANCELLED,
        'completed' => Constants::EVENT_ORDER_DELIVERED,
    ];
    $action = $actions[$new_status] ?? null;

    if (!$action) {
        return;
    }

    (new Event(Session::for_order($order)))->track($action, [
        'object_parameters' => (new OrderAdapter($order))->get(),
    ]);
}
