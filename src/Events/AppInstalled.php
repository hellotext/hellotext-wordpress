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
 * Every connection issues a new WooCommerce API key for Hellotext, so
 * reconnecting after a deactivation or a credential change works. Hellotext
 * reads products and creates coupons and webhooks through this key, which
 * needs read_write access.
 *
 * The previous key keeps working until Hellotext accepts the new one. A
 * failed connection keeps it, is shown on the settings page, and is retried
 * the next time the settings are saved.
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

    $api_keys_table = $wpdb->prefix . 'woocommerce_api_keys';
    $consumer_key = 'ck_' . wc_rand_hash();
    $consumer_secret = 'cs_' . wc_rand_hash();

    // A connection cut short leaves its pending key behind.
    hellotext_delete_api_keys([Constants::API_KEY_PENDING_DESCRIPTION]);

    $wpdb->insert($api_keys_table, [
        'user_id' => $user_id,
        'description' => Constants::API_KEY_PENDING_DESCRIPTION,
        'permissions' => 'read_write',
        'consumer_key' => wc_api_hash($consumer_key),
        'consumer_secret' => $consumer_secret,
        'truncated_key' => substr($consumer_key, -7),
    ]);

    $response = Client::post(Constants::API_ENDPOINT_INTEGRATIONS_WOO, [
        'shop' => [
            'name' => get_bloginfo('name'),
            'url' => get_bloginfo('url'),
            'email' => get_bloginfo('admin_email'),
            'consumer_key' => $consumer_key,
            'consumer_secret' => $consumer_secret,
            'currency' => get_woocommerce_currency(),
            // Decides the checkout link Hellotext builds: WooCommerce's own from 10.0, the plugin's before.
            'version' => defined('WC_VERSION') ? WC_VERSION : null,
        ],
    ]);

    if ($response['status'] >= 200 && $response['status'] < 300) {
        hellotext_delete_api_keys([Constants::API_KEY_DESCRIPTION]);
        $wpdb->update($api_keys_table, ['description' => Constants::API_KEY_DESCRIPTION], ['description' => Constants::API_KEY_PENDING_DESCRIPTION]);
        delete_option(Constants::OPTION_CONNECTION_FAILED);
    } else {
        $wpdb->delete($api_keys_table, ['description' => Constants::API_KEY_PENDING_DESCRIPTION]);
        update_option(Constants::OPTION_CONNECTION_FAILED, true, false);
    }
}

/**
 * Retry a failed connection when the settings are saved again.
 *
 * Saving unchanged values does not run the option update hooks, so the
 * filter, which runs on every save, schedules it.
 *
 * @param mixed $value Access token being saved.
 * @return mixed
 */
function hellotext_retry_failed_connection(mixed $value): mixed {
    if (get_option(Constants::OPTION_CONNECTION_FAILED)) {
        hellotext_schedule_connect();
    }

    return $value;
}

/**
 * Delete the WooCommerce API keys issued to Hellotext, connected and pending
 * ones by default.
 *
 * @param array $descriptions Descriptions of the keys to delete.
 * @return void
 */
function hellotext_delete_api_keys(array $descriptions = [Constants::API_KEY_DESCRIPTION, Constants::API_KEY_PENDING_DESCRIPTION]): void {
    global $wpdb;

    $api_keys_table = $wpdb->prefix . 'woocommerce_api_keys';

    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $api_keys_table)) !== $api_keys_table) {
        return;
    }

    foreach ($descriptions as $description) {
        $wpdb->delete($api_keys_table, ['description' => $description]);
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
add_filter('pre_update_option_' . Constants::OPTION_ACCESS_TOKEN, 'hellotext_retry_failed_connection', 10, 1);
