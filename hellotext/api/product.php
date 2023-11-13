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
    $productAdapter = new ProductAdapter( $request->get_param( 'id' ) );
    $product = $productAdapter->get();

    wp_send_json( $product );
}
