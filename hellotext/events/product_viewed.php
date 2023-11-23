<?php

add_action('woocommerce_after_single_product', 'hellotext_product_viewed');

function hellotext_product_viewed() {
    global $product;
    $user = wp_get_current_user();

    $hellotext = new HellotextEvent();
    $parsedProduct = (new ProductAdapter($product))->get();

    if (get_user_meta($user->ID, 'hellotext_profile_id', true) == null) {
        (new CreateProfile($user->ID))->process();
    }

    $hellotext->track('product.viewed', array(
        'product_parameters' => $parsedProduct
    ));
}
