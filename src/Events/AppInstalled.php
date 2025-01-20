<?php

use Hellotext\Api\Client;
use Hellotext\Api\Event;

function hellotext_activate () {
	$hellotext_business_id = get_option('hellotext_business_id');
	if (!$hellotext_business_id) {
		return;
	}

	do_action('hellotext_create_integration', $hellotext_business_id);
}

add_action('hellotext_create_integration', function ($business_id) {
   if(!$business_id) {
     $business_id = get_option('hellotext_business_id');
   }

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
				'name' => get_bloginfo('name'),
				'url' => get_bloginfo('url'),
				'email' => get_bloginfo('admin_email'),
				'consumer_key' => $api_keys->consumer_key,
				'consumer_secret' => $api_keys->consumer_secret,
			]
		]);
});

function after_business_id_save($old_value, $new_value) {
    if ($old_value !== $new_value) {
        maybe_trigger_integration($new_value);
    }
}

function after_business_id_set($value) {
    maybe_trigger_integration($value);
}

function maybe_trigger_integration($business_id) {
    $hellotext_access_token = get_option('hellotext_access_token');
    if ($hellotext_access_token && $business_id) {
        do_action('hellotext_create_integration');
    } else {
        add_action('shutdown', function () {
            $hellotext_access_token = get_option('hellotext_access_token');
            if ($hellotext_access_token) {
                do_action('hellotext_create_integration');
            }
        });
    }
}

add_action('add_option_hellotext_business_id', 'after_business_id_set', 10, 1);
add_action('add_option_hellotext_access_token', 'maybe_trigger_integration', 10, 1);
add_action('update_option_hellotext_business_id', 'after_business_id_save', 10, 2);
add_action('update_option_hellotext_access_token', 'maybe_trigger_integration', 10, 2);
