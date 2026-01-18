<?php

use Hellotext\Adapters\ProductAdapter;
use Hellotext\Api\Event;
use Hellotext\Constants;

add_action('woocommerce_after_single_product', 'hellotext_product_viewed');

/**
 * Track product view event.
 *
 * @return void
 */
function hellotext_product_viewed(): void {
    global $product;

    do_action('hellotext_create_profile');

    (new Event())->track(Constants::EVENT_PRODUCT_VIEWED, [
        'object_parameters' => (new ProductAdapter($product))->get(),
    ]);
}
