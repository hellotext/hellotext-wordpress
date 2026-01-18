<?php

use Hellotext\Adapters\ProductAdapter;
use Hellotext\Api\Event;
use Hellotext\Constants;

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

function hellotext_cart_updated() {
    wc_load_cart();

    $changes = array(
       'added' => array(),
       'removed' => array()
    );

    // Set previous cart items and current cart items
    $previous_cart_items = isset($_SESSION[Constants::SESSION_CART_ITEMS])
       ? json_decode(sanitize_text_field($_SESSION[Constants::SESSION_CART_ITEMS]), true)
       : array();

    $current_cart_items = WC()->cart->get_cart();
    $cart_items = array();

    foreach ($current_cart_items as $key => $cart_item) {
        $product = $cart_item['data']; // WC_Product object

        $cart_items[] = [
            'product' => (new ProductAdapter($product))->get(),
            'quantity' => $cart_item['quantity'],
        ];
    }

    // Save current cart items to session
    $_SESSION[Constants::SESSION_CART_ITEMS] = json_encode($cart_items);

    // Calculate total cart value
    $cart_total = WC()->cart->get_cart_contents_total();
    $currency = get_woocommerce_currency();

    // Get current page URL
    $current_url = home_url(add_query_arg(array(), $GLOBALS['wp']->request));

    foreach ($cart_items as $cart_item) {
       $match = array_filter(
          $previous_cart_items,
          fn($item) => $item['product']['reference'] == $cart_item['product']['reference']
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
          fn($item) => $item['product']['reference'] == $previous_item['product']['reference']
       );

       $cart_item = count($match) > 0 ? array_shift($match) : null;

       if (!$cart_item || $previous_item['quantity'] > $cart_item['quantity']) {
          $changes['removed'][] = $previous_item; // Use previous_item here as it's the removed item
       }
    }

    // Trigger events, one for added and one for removed items
    $event_map = [
       'added' => Constants::EVENT_CART_ADDED,
       'removed' => Constants::EVENT_CART_REMOVED,
    ];

    foreach ($changes as $event => $items) {
       if (0 == count($items)) {
          continue;
       }

       if (!isset($event_map[$event])) {
          continue;
       }

       $event_data = array(
          'amount' => $cart_total,
          'currency' => $currency,
          'url' => $current_url,
          'object_parameters' => array(
             'items' => $items
          )
       );

       (new Event())->track($event_map[$event], $event_data);
    }
}
