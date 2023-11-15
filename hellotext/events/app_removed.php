<?php

function hellotext_deactivate () {
    $hellotext_business_id = get_option('hellotext_business_id');
    if (!$hellotext_business_id) return;

    $store_image_id = get_option('woocommerce_email_header_image_id');
    $store_image_url = wp_get_attachment_image_url($store_image_id, 'full');

    $hellotext = new Hellotext();
    $hellotext->track('app.removed', array(
        'app_parameters' => array(
            'type' => 'app',
            'name' => get_bloginfo('name'),
            'image_url' => $store_image_url,
        )
    ));
}
