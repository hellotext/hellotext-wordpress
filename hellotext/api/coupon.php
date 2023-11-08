<?php
/*
 * WP_REST_Server::READABLE  -> methods: GET
 * WP_REST_Server::EDITABLE  -> methods: POST, PUT, PATCH
 * WP_REST_Server::DELETABLE -> methods: DELETE
*/

add_action( 'rest_api_init', 'register_get_coupon_endpoint' );
function register_get_coupon_endpoint () {
    register_rest_route( 'hellotext/v1', '/coupon/(?P<coupon_code>\w+)', array(
        'methods'  => WP_REST_Server::READABLE,
        'callback' => 'get_coupon_validation',
    ) );
}

function get_coupon_validation (WP_REST_Request $request) {
    $coupon_code = $request->get_param( 'coupon_code' );
    $coupon = new \WC_Coupon( $coupon_code );
    $discounts = new \WC_Discounts();

    $valid = $discounts->is_coupon_valid( $coupon );

    wp_send_json([
        'code' => $coupon_code,
        'valid' => ! is_wp_error( $valid ) ,
    ]);
}
