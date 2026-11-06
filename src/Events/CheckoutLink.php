<?php

use Hellotext\Constants;

add_action('template_redirect', 'hellotext_checkout_link');

/**
 * Open the checkout with the cart of a Hellotext checkout link.
 *
 * WooCommerce 10.0 and later build carts from their own /checkout-link/.
 * Hellotext sends older stores ?hellotext_checkout=1&products=id:quantity,...
 * instead, and this does the same: it replaces the shopper's cart with the
 * products and opens the checkout, keeping the link's other parameters, such
 * as its UTMs.
 *
 * @return void
 */
function hellotext_checkout_link(): void {
    if (!isset($_GET[Constants::CHECKOUT_LINK_PARAM]) || !function_exists('WC') || !WC()->cart) {
        return;
    }

    wp_safe_redirect(hellotext_fill_checkout_link_cart());
    exit;
}

/**
 * Replace the cart with the link's products.
 *
 * @return string The checkout; the page of a product whose options the
 *                shopper has to pick, as WooCommerce's own link does; or the
 *                cart, with WooCommerce's notices, when nothing could be added.
 */
function hellotext_fill_checkout_link_cart(): string {
    $products = wp_unslash($_GET['products'] ?? '');
    $lines = is_string($products) ? hellotext_checkout_link_lines($products) : [];

    if (empty($lines)) {
        return wc_get_cart_url();
    }

    WC()->cart->empty_cart();
    $options_url = null;

    foreach ($lines as $product_id => $quantity) {
        try {
            $added = WC()->cart->add_to_cart($product_id, $quantity);
        } catch (\Exception $e) {
            $added = false;
        }

        if ($added || $options_url) {
            continue;
        }

        $product = wc_get_product($product_id);

        if ($product && $product->is_type(['variable', 'variation'])) {
            $options_url = $product->get_permalink();
        }
    }

    if ($options_url) {
        return $options_url;
    }

    if (WC()->cart->is_empty()) {
        return wc_get_cart_url();
    }

    $query = wp_unslash($_GET);
    unset($query[Constants::CHECKOUT_LINK_PARAM], $query['products']);

    return add_query_arg(urlencode_deep($query), wc_get_checkout_url());
}

/**
 * The quantity of each product of a link's `products`, which lists
 * id:quantity separated by commas. A product listed twice adds up, and a
 * line without a valid ID is left out.
 *
 * @param string $value The `products` parameter.
 * @return array<int, int> Quantities by product ID.
 */
function hellotext_checkout_link_lines(string $value): array {
    $lines = [];

    foreach (explode(',', $value) as $line) {
        [$id, $quantity] = array_pad(explode(':', trim($line), 2), 2, '1');
        $id = absint($id);

        if ($id > 0) {
            $lines[$id] = ($lines[$id] ?? 0) + max(1, absint($quantity));
        }
    }

    return $lines;
}
