<?php

use Hellotext\Constants;

add_action('wp_enqueue_scripts', 'hellotext_enqueue_script');

/**
 * Load Hellotext.js on the storefront.
 *
 * Webchat, popups and every other widget are configured from Hellotext. The
 * plugin's storefront script starts it with the business ID and the Push
 * service worker, reports the page section and page view, shows the Smart
 * Alert for the section, and adds the Webchat's products to the cart.
 *
 * @return void
 */
function hellotext_enqueue_script(): void {
    $business_id = (string) get_option(Constants::OPTION_BUSINESS_ID);

    // The page checks below are WooCommerce's, missing while it is inactive.
    if (!$business_id || !function_exists('WC')) {
        return;
    }

    $plugin_file = dirname(__DIR__, 2) . '/hellotext.php';
    $storefront = dirname(__DIR__, 2) . '/assets/storefront.js';

    // A null version keeps the URL unversioned, so jsDelivr serves the latest release.
    wp_enqueue_script('hellotext', Constants::SDK_URL, [], null, true);
    wp_enqueue_script(
        'hellotext-storefront',
        plugins_url('assets/storefront.js', $plugin_file),
        ['hellotext'],
        (string) filemtime($storefront),
        true
    );
    wp_add_inline_script(
        'hellotext-storefront',
        'window.hellotextWoo = ' . wp_json_encode(hellotext_storefront_config($business_id, $plugin_file)) . ';',
        'before'
    );
}

/**
 * Settings for the storefront script.
 *
 * @param string $business_id Hellotext business ID.
 * @param string $plugin_file Main plugin file.
 * @return array
 */
function hellotext_storefront_config(string $business_id, string $plugin_file): array {
    return [
        'businessId' => $business_id,
        'options' => [
            'push' => [
                // A same-origin path, as Push requires. Registered under the plugin's
                // folder, the worker never replaces one the store runs at the root.
                'serviceWorkerUrl' => wp_make_link_relative(plugins_url('assets/hellotext-service-worker.js', $plugin_file)),
            ],
        ],
        'section' => hellotext_page_section(),
        // Product pages report product.viewed and the checkout checkout.started.
        'trackPageView' => !is_product() && !is_checkout(),
        'addToCartUrl' => WC_AJAX::get_endpoint('add_to_cart'),
        'cartUrl' => wc_get_cart_url(),
        'redirectToCart' => 'yes' === get_option('woocommerce_cart_redirect_after_add'),
    ];
}

/**
 * The Hellotext section of the current storefront page.
 *
 * A shop page set as the front page counts as the homepage.
 *
 * @return string|null 'homepage', 'product_collection', 'product_details', or null for other pages.
 */
function hellotext_page_section(): ?string {
    if (is_front_page()) {
        return 'homepage';
    }

    if (is_product()) {
        return 'product_details';
    }

    if (is_shop() || is_product_taxonomy()) {
        return 'product_collection';
    }

    return null;
}
