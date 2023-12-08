<?php

use Hellotext\Api\Client;
use Hellotext\Api\Event;

function hellotext_activate () {
	$hellotext_business_id = get_option('hellotext_business_id');
	if (!$hellotext_business_id) { return; }

	do_action('hellotext_create_profile');
	do_action('hellotext_create_integration', $hellotext_business_id);

	// Disbaled for now
	// $store_image_id = get_option('woocommerce_email_header_image_id');
	// $store_image_url = wp_get_attachment_image_url($store_image_id, 'full');

	// (new Event())->track('app.installed', array(
	//     'app_parameters' => array(
	//         'type' => 'app',
	//         'name' => get_bloginfo('name'),
	//         'image_url' => $store_image_url,
	//     )
	// ));
}

add_action('hellotext_create_integration', function ($business_id) {
	Client::with_sufix()
		->post('/integrations/woo', [
			'shop' => [
				'business_id' => $business_id,
				'name' => get_bloginfo('name'),
				'url' => get_bloginfo('url'),
				'email' => get_bloginfo('admin_email'),
			]
		]);
});

