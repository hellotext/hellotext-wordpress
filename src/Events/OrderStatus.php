<?php

use Hellotext\Adapters\OrderAdapter;
use Hellotext\Services\Session;
use Hellotext\Api\Event;
use Hellotext\Constants;

add_action('woocommerce_order_status_changed', 'track_order_status', 10, 4);

function track_order_status ($order_id, $old_status, $new_status, $order) {
	$encrypted_session = get_post_meta($order_id, Constants::META_SESSION, true);
	$session = Session::decrypt($encrypted_session);

	$orderAdapter = new OrderAdapter($order);
	$event = new Event($session);

	do_action('hellotext_create_profile', $order->get_user_id());

	switch ($new_status) {
		case 'processing':
			$event->track(Constants::EVENT_ORDER_CONFIRMED, array(
				'object_parameters' => $orderAdapter->get(),
				));
			break;

		case 'cancelled':
			$event->track(Constants::EVENT_ORDER_CANCELLED, array(
				'object_parameters' => $orderAdapter->get(),
				));
			break;

		case 'completed':
			$event->track(Constants::EVENT_ORDER_DELIVERED, array(
				'object_parameters' => $orderAdapter->get(),
				));
			break;
	}
}
