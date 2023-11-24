<?php

add_action('woocommerce_after_single_product', 'hellotext_product_viewed');

function hellotext_product_viewed() {
    global $product;

    do_action('hellotext_create_profile');

    (new HellotextEvent())->track('product.viewed', array(
        'product_parameters' => (new ProductAdapter($product))->get()
    ));
}
