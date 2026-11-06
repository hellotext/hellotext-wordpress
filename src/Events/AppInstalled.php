<?php

use Hellotext\Api\Client;
use Hellotext\Constants;

/**
 * Connect the store when the plugin is activated.
 *
 * @return void
 */
function hellotext_activate(): void {
    hellotext_connect();
}

/**
 * Connect the store to Hellotext.
 *
 * Every connection issues a new WooCommerce API key for Hellotext and
 * replaces the previous one, so reconnecting after a deactivation or a
 * credential change works. Hellotext reads products and creates coupons and
 * webhooks through this key, which needs read_write access.
 *
 * @return void
 */
function hellotext_connect(): void {
    global $wpdb;

    $business_id = get_option(Constants::OPTION_BUSINESS_ID);
    $access_token = get_option(Constants::OPTION_ACCESS_TOKEN);
    // The key acts as this user, so it must be a real shop manager.
    $user_id = get_current_user_id();

    if (!$business_id || !$access_token || !$user_id || !function_exists('wc_rand_hash')) {
        return;
    }

    $consumer_key = 'ck_' . wc_rand_hash();
    $consumer_secret = 'cs_' . wc_rand_hash();

    hellotext_delete_api_keys();

    $wpdb->insert($wpdb->prefix . 'woocommerce_api_keys', [
        'user_id' => $user_id,
        'description' => Constants::API_KEY_DESCRIPTION,
        'permissions' => 'read_write',
        'consumer_key' => wc_api_hash($consumer_key),
        'consumer_secret' => $consumer_secret,
        'truncated_key' => substr($consumer_key, -7),
    ]);

    Client::post(Constants::API_ENDPOINT_INTEGRATIONS_WOO, [
        'shop' => [
            'name' => get_bloginfo('name'),
            'url' => get_bloginfo('url'),
            'email' => get_bloginfo('admin_email'),
            'consumer_key' => $consumer_key,
            'consumer_secret' => $consumer_secret,
            'currency' => get_woocommerce_currency(),
        ],
    ]);
}

/**
 * Delete the WooCommerce API keys issued to Hellotext.
 *
 * @return void
 */
function hellotext_delete_api_keys(): void {
    global $wpdb;

    $api_keys_table = $wpdb->prefix . 'woocommerce_api_keys';

    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $api_keys_table)) === $api_keys_table) {
        $wpdb->delete($api_keys_table, ['description' => Constants::API_KEY_DESCRIPTION]);
    }
}

/**
 * Connect once both settings are saved.
 *
 * The settings form saves the business ID before the access token, so the
 * connection waits for the end of the request to use both new values.
 *
 * @return void
 */
function hellotext_schedule_connect(): void {
    if (!has_action('shutdown', 'hellotext_connect')) {
        add_action('shutdown', 'hellotext_connect');
    }
}

/**
 * Move the integration when the business ID changes.
 *
 * The access token is still the previous business's at this point, so the
 * previous integration is removed with it before connecting the new one.
 *
 * @param mixed $old_value Previous value.
 * @param mixed $new_value New value.
 * @return void
 */
function hellotext_business_id_updated(mixed $old_value, mixed $new_value): void {
    if ($old_value && $old_value !== $new_value) {
        hellotext_disconnect((string) $old_value);
    }

    hellotext_schedule_connect();
}

add_action('add_option_' . Constants::OPTION_BUSINESS_ID, 'hellotext_schedule_connect', 10, 0);
add_action('add_option_' . Constants::OPTION_ACCESS_TOKEN, 'hellotext_schedule_connect', 10, 0);
add_action('update_option_' . Constants::OPTION_BUSINESS_ID, 'hellotext_business_id_updated', 10, 2);
add_action('update_option_' . Constants::OPTION_ACCESS_TOKEN, 'hellotext_schedule_connect', 10, 0);
