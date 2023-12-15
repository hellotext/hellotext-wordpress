<?php

use Hellotext\Adapters\ProductAdapter;
use Hellotext\Api\Event;

// We could listen for woocommerce_cart_updated event but this event is
// triggered too many times per cart update. Instead, we listen for the
// cart page load and trigger our own event.
add_action( 'woocommerce_after_cart', 'hellotext_trigger_cart_updated' );
add_action( 'woocommerce_add_to_cart', 'hellotext_trigger_cart_updated' );
add_action( 'woocommerce_cart_item_removed', 'hellotext_trigger_cart_updated' );
add_action( 'woocommerce_after_cart_item_quantity_update', 'hellotext_trigger_cart_updated' );

function hellotext_trigger_cart_updated () {
	do_action('hellotext_create_profile');
	do_action('hellotext_woocommerce_cart_updated');
}

add_action('hellotext_woocommerce_cart_updated', 'hellotext_cart_updated');

function hellotext_cart_updated () {
	session_start();
	wc_load_cart();

	$changes = array(
		'added' => array(),
		'removed' => array()
	);

	// Set previous cart items and current cart items
	$previous_cart_items = isset($_SESSION['hellotext_cart_items'])
		? sanitize_text_field($_SESSION['hellotext_cart_items'])
		: array();
	$current_cart_items = WC()->cart->get_cart();
	$cart_items = array();

	// Parse current cart items with the ProductAdapter
	foreach ( $current_cart_items as $key => $cart_item ) {
		$cart_items[] = ( new ProductAdapter( $cart_item['product_id'], $cart_item) )->get();
	}

	// Save current cart items to session
	$_SESSION['hellotext_cart_items'] = $cart_items;

	// Add items that were added to the cart
	foreach ($cart_items as $cart_item) {
		$match = array_filter(
			$previous_cart_items,
			fn($item) => $item['reference'] == $cart_item['reference']
		);

		$previous_item = count($match) > 0 ? array_shift($match) : null;

		if (!$previous_item || $previous_item['quantity'] < $cart_item['quantity']) {
			$changes['added'][] = $cart_item;
		}
	}

	// Add items that were removed from the cart
	foreach ($previous_cart_items as $previous_item) {
		$match = array_filter(
			$cart_items,
			fn($item) => $item['reference'] == $previous_item['reference']
		);

		$cart_item = count($match) > 0 ? array_shift($match) : null;

		if (!$cart_item || $previous_item['quantity'] > $cart_item['quantity']) {
			$changes['removed'][] = $cart_item;
		}
	}

	// Trigger events, one for added and one for removed items
	foreach ($changes as $event => $items) {
		if (0 == count($changes[$event])) {
			continue;
		}

		( new Event() )->track("cart.{$event}", array(
			'products' => $items
		));
	}
}
