<?php
/*
 * WP_REST_Server::READABLE  -> methods: GET
 * WP_REST_Server::EDITABLE  -> methods: POST, PUT, PATCH
 * WP_REST_Server::DELETABLE -> methods: DELETE
*/

add_action( 'rest_api_init', 'register_get_cart_endpoint' );
function register_get_cart_endpoint () {
    register_rest_route( 'hellotext/v1', '/cart/', array(
        'methods'  => WP_REST_Server::READABLE,
        'callback' => 'get_cart_contents',
    ) );
}

function get_cart_contents () {
    wc_load_cart();

    $cart_items = array();

    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        $product_id = $cart_item['product_id'];
        $product = wc_get_product( $product_id );

        $cart_items[] = array(
            'id' => $product_id,
            'type' => 'product',
            'name' => $product->get_name(),
            'categories' => wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) ),
            'created_at' => $product->get_date_created()->date( 'Y-m-d H:i:s' ),
            'currency' => get_woocommerce_currency(),
            'price' => $product->get_price(),
            'quantity' => $cart_item['quantity'],
            'tags' => wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'names' ) ),
            'image_url' => wp_get_attachment_url( $product->get_image_id() ),
        );
    }

    wp_send_json( $cart_items );
}
