<?php

use Hellotext\Api\Client;
use Hellotext\Api\Event;

function hellotext_activate () {
	$hellotext_business_id = get_option('hellotext_business_id');
	if (!$hellotext_business_id) {
		return;
	}

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
	global $wpdb;
	$api_keys_table = $wpdb->prefix . 'woocommerce_api_keys';
	$api_keys = $wpdb->get_row("SELECT * FROM $api_keys_table WHERE description = 'Hellotext'");
	if (!$api_keys) {
		// Create a new API key
		$api_keys = (object) [
			'consumer_key' => 'ck_' . wc_rand_hash(),
			'consumer_secret' => 'cs_' . wc_rand_hash(),
		];

		// wc_api_hash will hash the $api_keys->consumer_key in place.
		$conusmer_key = $api_keys->consumer_key;

		$wpdb->insert($api_keys_table, [
			'user_id' => get_current_user_id(),
			'description' => 'Hellotext',
			'permissions' => 'read',
			'consumer_key' => wc_api_hash($conusmer_key),
			'consumer_secret' => $api_keys->consumer_secret,
			'truncated_key' => substr($api_keys->consumer_key, -7),
		]);
	}

	Client::with_sufix()
		->post('/integrations/woo', [
			'shop' => [
				'business_id' => $business_id,
				'name' => get_bloginfo('name'),
				'url' => get_bloginfo('url'),
				'email' => get_bloginfo('admin_email'),
				'consumer_key' => $api_keys->consumer_key,
				'consumer_secret' => $api_keys->consumer_secret,
			]
		]);
});

