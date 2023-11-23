<?php

add_action( 'woocommerce_order_refunded', 'hellotext_refund_created', 10, 2 );

function hellotext_refund_created ($order_id, $refund_id) {
    $order = wc_get_order($order_id);
    $refund = new WC_Order_Refund($refund_id);
    $refundAdapter = new RefundAdapter($refund, $order);

    $encrypted_session = get_post_meta($order_id, 'hellotext_session', true);
    $session = Session::decrypt($encrypted_session);

    $hellotext = new HellotextEvent($session);

    $hellotext->track('refund.received', array(
        'refund_parameters' => $refundAdapter->get(),
    ));
}
