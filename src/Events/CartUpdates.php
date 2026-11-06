<?php

use Hellotext\Adapters\ProductAdapter;
use Hellotext\Api\Event;
use Hellotext\Constants;
use Hellotext\Services\Session;

// Cart hooks fire several times per change and before WooCommerce
// recalculates the totals, so they only schedule one comparison at the end
// of the request. Viewing the cart page also compares, to catch changes made
// without these hooks.
add_action('woocommerce_after_cart', 'hellotext_trigger_cart_updated');
add_action('woocommerce_add_to_cart', 'hellotext_trigger_cart_updated');
add_action('woocommerce_cart_item_removed', 'hellotext_trigger_cart_updated');
add_action('woocommerce_after_cart_item_quantity_update', 'hellotext_trigger_cart_updated');
add_action('woocommerce_cart_emptied', 'hellotext_reset_cart_snapshot');

/**
 * Forget the cart snapshot when WooCommerce empties the cart, such as after
 * checkout, so the purchased items are not reported as removed.
 *
 * @return void
 */
function hellotext_reset_cart_snapshot(): void {
    if (WC()->session) {
        WC()->session->set(Constants::SESSION_CART_ITEMS, []);
    }
}

/**
 * Schedule cart change tracking for the end of the request.
 *
 * Runs before WooCommerce saves its session on shutdown, so the stored cart
 * snapshot persists.
 *
 * @return void
 */
function hellotext_trigger_cart_updated(): void {
    if (!has_action('shutdown', 'hellotext_cart_updated')) {
        add_action('shutdown', 'hellotext_cart_updated', 5);
    }
}

/**
 * Track cart item add/remove changes.
 *
 * @return void
 */
function hellotext_cart_updated(): void {
    $woocommerce = WC();

    if (!$woocommerce->session || !$woocommerce->cart) {
        return;
    }

    $previous_cart_items = $woocommerce->session->get(Constants::SESSION_CART_ITEMS);
    $previous_cart_items = is_array($previous_cart_items) ? $previous_cart_items : [];

    $cart_items = [];

    foreach ($woocommerce->cart->get_cart() as $cart_item) {
        $product = (new ProductAdapter($cart_item['data']))->get();
        $reference = (string) $product['reference'];

        if (isset($cart_items[$reference])) {
            $cart_items[$reference]['quantity'] += $cart_item['quantity'];
            continue;
        }

        $cart_items[$reference] = [
            'product' => $product,
            'quantity' => $cart_item['quantity'],
        ];
    }

    $woocommerce->session->set(Constants::SESSION_CART_ITEMS, $cart_items);

    $changes = [
        Constants::EVENT_CART_ADDED => [],
        Constants::EVENT_CART_REMOVED => [],
    ];

    foreach ($cart_items as $reference => $cart_item) {
        $added = $cart_item['quantity'] - ($previous_cart_items[$reference]['quantity'] ?? 0);

        if ($added > 0) {
            $changes[Constants::EVENT_CART_ADDED][] = ['product' => $cart_item['product'], 'quantity' => $added];
        }
    }

    foreach ($previous_cart_items as $reference => $previous_item) {
        $removed = $previous_item['quantity'] - ($cart_items[$reference]['quantity'] ?? 0);

        if ($removed > 0) {
            $changes[Constants::EVENT_CART_REMOVED][] = ['product' => $previous_item['product'], 'quantity' => $removed];
        }
    }

    if (empty($changes[Constants::EVENT_CART_ADDED]) && empty($changes[Constants::EVENT_CART_REMOVED])) {
        return;
    }

    do_action('hellotext_create_profile');

    $event = new Event(Session::current());

    foreach ($changes as $action => $items) {
        if (empty($items)) {
            continue;
        }

        $event->track($action, [
            'amount' => $woocommerce->cart->get_cart_contents_total(),
            'currency' => get_woocommerce_currency(),
            'url' => hellotext_current_url(),
            'object_parameters' => [
                'items' => $items,
            ],
        ]);
    }
}

/**
 * The storefront page the shopper is on.
 *
 * Add to cart buttons post to wc-ajax or the Store API, so those requests
 * report the page they came from.
 *
 * @return string
 */
function hellotext_current_url(): string {
    $background_request = wp_doing_ajax()
        || (defined('REST_REQUEST') && REST_REQUEST)
        || isset($_GET['wc-ajax']);

    if ($background_request) {
        $referer = wp_get_referer();

        if ($referer) {
            return $referer;
        }
    }

    return home_url(add_query_arg([], $GLOBALS['wp']->request ?? ''));
}
