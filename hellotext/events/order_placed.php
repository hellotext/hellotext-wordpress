<?php

add_action( 'woocommerce_after_order_details', 'hellotext_order_placed' );

function hellotext_order_placed ( $order ) {
    do_action('hellotext_create_profile', $order->get_user_id());

    $hellotext = new HellotextEvent();
    $parsedOrder = (new OrderAdapter($order))->get();

    $encrypted_session = Session::encrypt($_COOKIE['hello_session']);
    add_post_meta($order->get_id(), 'hellotext_session', $encrypted_session);

    $hellotext->track('order.placed', array(
        'order_parameters' => $parsedOrder,
    ));

    foreach ($parsedOrder['products'] as $product) {
        $hellotext->track('product.purchased', array(
            'product_parameters' => $product,
        ));
    }
}
