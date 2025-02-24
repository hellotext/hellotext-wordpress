<?php

use Hellotext\Adapters\RefundAdapter;
use Hellotext\Services\Session;
use Hellotext\Api\Event;

add_action( 'woocommerce_order_refunded', 'hellotext_refund_created', 10, 2 );

function hellotext_refund_created ($order_id, $refund_id) {
	$order = wc_get_order($order_id);
	$refund = new WC_Order_Refund($refund_id);

	do_action('hellotext_create_profile', $order->get_user_id());

	$encrypted_session = get_post_meta($order_id, 'hellotext_session', true);
	$session = Session::decrypt($encrypted_session);

	( new Event($session) )->track('refund.received', array(
		'object_parameters' => ( new RefundAdapter($refund, $order) )->get(),
	));
}
