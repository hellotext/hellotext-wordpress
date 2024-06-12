<?php

use Hellotext\Adapters\OrderAdapter;
use Hellotext\Services\Session;
use Hellotext\Api\Event;

add_action( 'woocommerce_after_order_details', 'hellotext_order_placed' );

function hellotext_order_placed ( $order ) {
	$userId = $order->get_user_id();
	$userId = $userId > 0 ? $userId : $order->data['billing'];

	do_action('hellotext_create_profile', $userId ?? $order->data['billing']);

	$event = new Event();
	$parsedOrder = ( new OrderAdapter($order) )->get();

	$session = isset($_COOKIE['hello_session'])
		? sanitize_text_field($_COOKIE['hello_session'])
		: null;
	$encrypted_session = Session::encrypt($session);
	add_post_meta($order->get_id(), 'hellotext_session', $encrypted_session);

	$event->track('order.placed', array(
		'order_parameters' => $parsedOrder,
	));

	foreach ($parsedOrder['products'] as $product) {
		$event->track('product.purchased', array(
			'product_parameters' => $product,
		));
	}
}
