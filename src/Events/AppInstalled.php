<?php

use Hellotext\Api\Client;
use Hellotext\Api\Event;
use Hellotext\Constants;

/**
 * Trigger integration creation on plugin activation.
 *
 * @return void
 */
function hellotext_activate (): void {
	$hellotext_business_id = get_option(Constants::OPTION_BUSINESS_ID);
	if (!$hellotext_business_id) {
		return;
	}

	do_action('hellotext_create_integration', $hellotext_business_id);
}

/**
 * Create WooCommerce integration in Hellotext.
 *
 * @param mixed $business_id Business ID token.
 * @return void
 */
add_action('hellotext_create_integration', function (mixed $business_id): void {
   if(!$business_id) {
     $business_id = get_option(Constants::OPTION_BUSINESS_ID);
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
			'permissions' => 'read_write',
			'consumer_key' => wc_api_hash($conusmer_key),
			'consumer_secret' => $api_keys->consumer_secret,
			'truncated_key' => substr($api_keys->consumer_key, -7),
		]);

        Client::with_sufix()
            ->post(Constants::API_ENDPOINT_INTEGRATIONS_WOO, [
                'shop' => [
                    'name' => get_bloginfo('name'),
                    'url' => get_bloginfo('url'),
                    'email' => get_bloginfo('admin_email'),
                    'consumer_key' => $api_keys->consumer_key,
                    'consumer_secret' => $api_keys->consumer_secret,
                    'currency' => get_woocommerce_currency(),
                ]
            ]);

        delete_transient('hellotext_integration_triggered');
	}
});

/**
 * Handle business ID update.
 *
 * @param mixed $old_value Previous value.
 * @param mixed $new_value New value.
 * @return void
 */
function after_business_id_save(mixed $old_value, mixed $new_value): void {
    if ($old_value !== $new_value) {
        maybe_trigger_integration($new_value);
    }
}

/**
 * Handle business ID creation.
 *
 * @param mixed $value Business ID value.
 * @return void
 */
function after_business_id_set(mixed $value): void {
    maybe_trigger_integration($value);
}

/**
 * Trigger integration creation when possible.
 *
 * @param mixed $business_id Business ID value.
 * @return void
 */
function maybe_trigger_integration(mixed $business_id): void {
    $integration_flag = get_transient('hellotext_integration_triggered');

    if ($integration_flag) {
        return;
    }

    $hellotext_access_token = get_option(Constants::OPTION_ACCESS_TOKEN);
    if ($hellotext_access_token && $business_id) {
        set_transient('hellotext_integration_triggered', true, 10);
        do_action('hellotext_create_integration');
    } else {
                /**
         * Attempt integration creation during shutdown.
         *
         * @return void
         */
        add_action('shutdown', function (): void {
            $integration_flag = get_transient('hellotext_integration_triggered');
            $hellotext_access_token = get_option(Constants::OPTION_ACCESS_TOKEN);

            if ($hellotext_access_token && !$integration_flag) {
                set_transient('hellotext_integration_triggered', true, 10); // Expires in 10 seconds.
                do_action('hellotext_create_integration');
            }
        });
    }
}

add_action('add_option_' . Constants::OPTION_BUSINESS_ID, 'after_business_id_set', 10, 1);
add_action('add_option_' . Constants::OPTION_ACCESS_TOKEN, 'maybe_trigger_integration', 10, 3);
add_action('update_option_' . Constants::OPTION_BUSINESS_ID, 'after_business_id_save', 10, 3);
add_action('update_option_' . Constants::OPTION_ACCESS_TOKEN, 'maybe_trigger_integration', 10, 4);
