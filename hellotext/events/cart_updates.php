<?php

use Hellotext\Adapters\ProductAdapter;

// We could listen for woocommerce_cart_updated event but this event is
// triggered too many times per cart update. Instead, we listen for the
// cart page load and trigger our own event.
add_action( 'woocommerce_after_cart', 'hellotext_trigger_cart_updated' );

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
    $previousCartItems = isset($_SESSION['hellotext_cart_items'])
        ? $_SESSION['hellotext_cart_items']
        : array();
    $currentCartItems = WC()->cart->get_cart();
    $cartItems = array();

    // Parse current cart items with the ProductAdapter
    foreach ( $currentCartItems as $key => $cartItem ) {
        $product = new ProductAdapter( $cartItem['product_id'], $cartItem);
        $cartItems[] = $product->get();
    }

    // Save current cart items to session
    $_SESSION['hellotext_cart_items'] = $cartItems;

    // Add items that were added to the cart
    foreach ($cartItems as $cartItem) {
        $match = array_filter(
            $previousCartItems,
            fn($item) => $item['reference'] == $cartItem['reference']
        );

        $previousItem = count($match) > 0 ? array_shift($match) : null;

        if (!$previousItem || $previousItem['quantity'] < $cartItem['quantity']) {
            $changes['added'][] = $cartItem;
        }
    }

    // Add items that were removed from the cart
    foreach ($previousCartItems as $previousItem) {
        $match = array_filter(
            $cartItems,
            fn($item) => $item['reference'] == $previousItem['reference']
        );

        $cartItem = count($match) > 0 ? array_shift($match) : null;

        if (!$cartItem || $previousItem['quantity'] > $cartItem['quantity']) {
            $changes['removed'][] = $cartItem;
        }
    }

    // Trigger events, one for added and one for removed items
    foreach ($changes as $event => $items) {
        if (count($items) > 0) {
            (new HellotextEvent())->track("cart.{$event}", array(
                'products' => $items
            ));
        }
    }
}
