<?php

use Hellotext\Adapters\OrderAdapter;
use Hellotext\Services\Session;
use Hellotext\Api\Event;

add_action( 'woocommerce_after_order_details', 'hellotext_order_placed' );

function hellotext_order_placed ( $order ) {
	do_action('hellotext_create_profile', $order->get_user_id());

	$event = new Event();
	$parsedOrder = (new OrderAdapter($order))->get();

	$encrypted_session = Session::encrypt($_COOKIE['hello_session']);
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
