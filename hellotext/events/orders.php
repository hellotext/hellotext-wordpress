<?php

add_action('woocommerce_order_status_changed', 'track_order_status', 10, 4);

function track_order_status ($order_id, $old_status, $new_status, $order) {
    $encrypted_session = get_post_meta($order_id, 'hellotext_session', true);
    $session = Session::decrypt($encrypted_session);

    $orderAdapter = new OrderAdapter($order);
    $hellotext = new HellotextEvent($session);

    do_action('hellotext_create_profile', $order->get_user_id());

    switch ($new_status) {
        case 'processing':
            $hellotext->track('order.confirmed', array(
                'order_parameters' => $orderAdapter->get(),
            ));
            break;

        case 'cancelled':
            $hellotext->track('order.cancelled', array(
                'order_parameters' => $orderAdapter->get(),
            ));
            break;

        case 'completed':
            $hellotext->track('order.delivered', array(
                'order_parameters' => $orderAdapter->get(),
            ));
            break;
    }
}
