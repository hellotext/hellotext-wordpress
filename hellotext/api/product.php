<?php

add_action( 'rest_api_init', 'register_get_product_endpoint' );
function register_get_product_endpoint () {
    // hellotext/v1/product/1
    register_rest_route( 'hellotext/v1', '/product/(?P<id>\d+)', array(
        'methods'  => WP_REST_Server::READABLE,
        'callback' => 'get_product_data',
    ) );
}

function get_product_data (WP_REST_Request $request) {
    $product = wc_get_product( $request->get_param( 'id' ) );

    wp_send_json(array(
        'reference' => $product->id,
        'type' => 'product',
        'name' => $product->name,
        'categories' => wp_get_post_terms( $product->id, 'product_cat', array( 'fields' => 'names' ) ),
        'currency' => get_woocommerce_currency(),
        'price' => $product->get_price(),
        'tags' => wp_get_post_terms( $product->id, 'product_tag', array( 'fields' => 'names' ) ),
        'image_url' => wp_get_attachment_url( $product->get_image_id() ),
    ));
}
