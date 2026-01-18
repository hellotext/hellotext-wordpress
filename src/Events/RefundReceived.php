<?php

use Hellotext\Adapters\RefundAdapter;
use Hellotext\Api\Event;
use Hellotext\Constants;
use Hellotext\Services\Session;

add_action('woocommerce_order_refunded', 'hellotext_refund_created', 10, 2);

/**
 * Track refund creation.
 *
 * @param int $order_id Order ID.
 * @param int $refund_id Refund ID.
 * @return void
 */
function hellotext_refund_created(int $order_id, int $refund_id): void {
    $order = wc_get_order($order_id);
    $refund = new WC_Order_Refund($refund_id);

    do_action('hellotext_create_profile', $order->get_user_id());

    $encrypted_session = get_post_meta($order_id, Constants::META_SESSION, true);
    $session = Session::decrypt($encrypted_session);

    (new Event($session))->track(Constants::EVENT_REFUND_RECEIVED, [
        'object_parameters' => (new RefundAdapter($refund, $order))->get(),
    ]);
}
