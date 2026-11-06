<?php

use Hellotext\Adapters\RefundAdapter;
use Hellotext\Api\Event;
use Hellotext\Constants;
use Hellotext\Services\Session;

add_action('woocommerce_order_refunded', 'hellotext_refund_created', 10, 2);

/**
 * Track refund creation.
 *
 * Refunds are issued from wp-admin or payment callbacks, so this only uses
 * the session stored on the order.
 *
 * @param int $order_id Order ID.
 * @param int $refund_id Refund ID.
 * @return void
 */
function hellotext_refund_created(int $order_id, int $refund_id): void {
    $order = wc_get_order($order_id);

    if (!$order instanceof \WC_Order) {
        return;
    }

    $refund = new \WC_Order_Refund($refund_id);

    (new Event(Session::for_order($order)))->track(Constants::EVENT_REFUND_RECEIVED, [
        'object_parameters' => (new RefundAdapter($refund, $order))->get(),
    ]);
}
