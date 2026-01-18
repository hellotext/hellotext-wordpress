<?php

use Hellotext\Adapters\OrderAdapter;
use Hellotext\Services\Session;
use Hellotext\Api\Event;
use Hellotext\Constants;

add_action( 'woocommerce_after_order_details', 'hellotext_order_placed' );

function hellotext_order_placed ( \WC_Order $order ): void {
	$userId = $order->get_user_id();
	$userId = $userId > 0 ? $userId : $order->data['billing'];

	do_action('hellotext_create_profile', $userId ?? $order->data['billing']);

	$event = new Event();
	$parsedOrder = ( new OrderAdapter($order) )->get();

	$session = isset($_COOKIE[Constants::SESSION_COOKIE_NAME])
		? sanitize_text_field($_COOKIE[Constants::SESSION_COOKIE_NAME])
		: null;
	$encrypted_session = Session::encrypt($session);
	add_post_meta($order->get_id(), Constants::META_SESSION, $encrypted_session);

	$event->track(Constants::EVENT_ORDER_PLACED, array(
		'object_parameters' => $parsedOrder,
	));
}
