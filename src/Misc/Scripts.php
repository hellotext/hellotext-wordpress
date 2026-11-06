<?php

use Hellotext\Constants;

add_action('wp_enqueue_scripts', 'hellotext_enqueue_script');

/**
 * Load Hellotext.js on the storefront.
 *
 * Webchat, popups and every other widget are configured from Hellotext. The
 * store passes its business ID and the Push service worker, and tells
 * Hellotext.js which section the page is, so events and Webchat messages
 * report it and the Smart Alert shows the matching section.
 *
 * @return void
 */
function hellotext_enqueue_script(): void {
    $business_id = get_option(Constants::OPTION_BUSINESS_ID);

    if (!$business_id) {
        return;
    }

    $business_id = wp_json_encode($business_id);
    $config = wp_json_encode([
        'push' => [
            // A same-origin path, as Push requires. Registered under the plugin's
            // folder, the worker never replaces one the store runs at the root.
            'serviceWorkerUrl' => wp_make_link_relative(
                plugins_url('assets/hellotext-service-worker.js', dirname(__DIR__, 2) . '/hellotext.php')
            ),
        ],
    ]);
    $section = wp_json_encode(hellotext_page_section());

    // A null version keeps the URL unversioned, so jsDelivr serves the latest release.
    wp_enqueue_script('hellotext', Constants::SDK_URL, [], null, true);
    wp_add_inline_script('hellotext', <<<JS
        window.Hellotext && Hellotext.initialize({$business_id}, {$config}).then(function () {
            var section = {$section};

            // Initializing builds a new page, so its section is set afterwards.
            if (Hellotext.page) Hellotext.page.section = section;
            if (section && Hellotext.alert) Hellotext.alert.show(section);
        }).catch(function (error) {
            console.warn('Hellotext initialization failed:', error);
        });
        JS);
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
