<?php

use Hellotext\Adapters\ProductAdapter;
use Hellotext\Api\Event;
use Hellotext\Constants;
use Hellotext\Services\Session;

add_action('woocommerce_after_single_product', 'hellotext_product_viewed');

/**
 * Track product view event.
 *
 * @return void
 */
function hellotext_product_viewed(): void {
    global $product;

    if (!$product instanceof \WC_Product) {
        return;
    }

    do_action('hellotext_create_profile');

    (new Event(Session::current()))->track(Constants::EVENT_PRODUCT_VIEWED, [
        'object_parameters' => (new ProductAdapter($product))->get(),
    ]);
}
