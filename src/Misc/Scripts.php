<?php

use Hellotext\Constants;

add_action('wp_enqueue_scripts', 'hellotext_enqueue_script');

/**
 * Load Hellotext.js on the storefront.
 *
 * Webchat, popups and every other widget are configured from Hellotext,
 * so the store only passes its business ID.
 *
 * @return void
 */
function hellotext_enqueue_script(): void {
    $business_id = get_option(Constants::OPTION_BUSINESS_ID);

    if (!$business_id) {
        return;
    }

    // A null version keeps the URL unversioned, so jsDelivr serves the latest release.
    wp_enqueue_script('hellotext', Constants::SDK_URL, [], null, true);
    wp_add_inline_script('hellotext', sprintf('Hellotext.initialize(%s);', wp_json_encode($business_id)));
}
