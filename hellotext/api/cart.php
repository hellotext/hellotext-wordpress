<?php
/*
 * WP_REST_Server::READABLE  -> methods: GET
 * WP_REST_Server::EDITABLE  -> methods: POST, PUT, PATCH
 * WP_REST_Server::DELETABLE -> methods: DELETE
*/

add_action( 'rest_api_init', 'register_get_cart_endpoint' );
function register_get_cart_endpoint () {
    register_rest_route( 'hellotext/v1', '/cart', array(
        'methods'  => WP_REST_Server::READABLE,
        'callback' => 'get_cart_contents',
    ) );
}

function get_cart_contents () {
    wc_load_cart();

    $cart_items = array();

    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        $product = new ProductAdapter( $cart_item['product_id'], $cart_item);
        $cart_items[] = $product->get();
    }

    wp_send_json( $cart_items );
}
