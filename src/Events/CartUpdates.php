<?php

use Hellotext\Adapters\PriceAdapter;
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
add_action('template_redirect', 'hellotext_cart_viewed');

/**
 * Forget the cart snapshot and the tracked checkout when WooCommerce empties
 * the cart, such as after checkout, so the purchased items are not reported
 * as removed and the next checkout is reported again.
 *
 * @return void
 */
function hellotext_reset_cart_snapshot(): void {
    if (WC()->session) {
        WC()->session->set(Constants::SESSION_CART_ITEMS, []);
        WC()->session->set(Constants::SESSION_CHECKOUT_STARTED, null);
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
 * Hellotext sets each cart line to the quantity an addition carries, so
 * cart.added reports the product's whole quantity in the cart. cart.removed
 * reports the quantity removed.
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
    $cart_items = hellotext_cart_items();

    $woocommerce->session->set(Constants::SESSION_CART_ITEMS, $cart_items);

    $changes = [
        Constants::EVENT_CART_ADDED => [],
        Constants::EVENT_CART_REMOVED => [],
    ];

    foreach ($cart_items as $reference => $cart_item) {
        if ($cart_item['quantity'] > ($previous_cart_items[$reference]['quantity'] ?? 0)) {
            $changes[Constants::EVENT_CART_ADDED][] = $cart_item;
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

        $event->track($action, array_filter([
            'amount' => $woocommerce->cart->get_cart_contents_total(),
            'currency' => get_woocommerce_currency(),
            'url' => hellotext_current_url(),
            'source' => Constants::EVENT_CART_ADDED === $action ? hellotext_cart_event_source() : null,
            'object_parameters' => [
                'items' => $items,
            ],
        ], fn ($value) => null !== $value));
    }
}

/**
 * Track the cart page.
 *
 * @return void
 */
function hellotext_cart_viewed(): void {
    if (!function_exists('WC') || !is_cart() || !WC()->cart || WC()->cart->is_empty()) {
        return;
    }

    do_action('hellotext_create_profile');

    (new Event(Session::current()))->track(Constants::EVENT_CART_VIEWED, [
        'object_parameters' => hellotext_cart_object_parameters() + ['url' => hellotext_current_url()],
    ]);
}

/**
 * The cart's lines, one per product or variation, keyed by reference.
 *
 * @return array
 */
function hellotext_cart_items(): array {
    $cart_items = [];

    foreach (WC()->cart->get_cart() as $cart_item) {
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

    return $cart_items;
}

/**
 * The cart as the object of cart.viewed and checkout.started.
 *
 * @return array
 */
function hellotext_cart_object_parameters(): array {
    return [
        'source' => 'woo',
        'items' => array_values(hellotext_cart_items()),
        'total' => (new PriceAdapter(WC()->cart->get_total('edit')))->get(),
        'total_quantity' => WC()->cart->get_cart_contents_count(),
    ];
}

/**
 * The Webchat message whose product card added to the cart in this request.
 *
 * The storefront script posts it with the product, and Hellotext credits the
 * addition to that message.
 *
 * @return array|null
 */
function hellotext_cart_event_source(): ?array {
    if (!isset($_POST['hellotext_source']) || !is_string($_POST['hellotext_source'])) {
        return null;
    }

    $source = json_decode(wp_unslash($_POST['hellotext_source']), true);

    if (!is_array($source)) {
        return null;
    }

    $source = array_intersect_key($source, array_flip(['kind', 'message_id', 'button_id']));
    $source = array_map(fn ($value) => is_scalar($value) ? sanitize_text_field((string) $value) : '', $source);
    $source = array_filter($source, fn ($value) => '' !== $value);

    return $source ?: null;
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
