<?php
/**
 * Hellotext
 *
 * @package Hellotext
 *
 * @wordpress-plugin
 * Plugin Name: Hellotext
 * Plugin URI: https://github.com/hellotext/hellotext-wordpress
 * Description: Integrates Hellotext tracking to WooCommerce.
 * Version: 1.2.3
 * Author: Hellotext
 * Author URI: https://www.hellotext.com
 * License: GPL v2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: hellotext
 * Domain Path: /languages
 */

use Hellotext\Constants;

require_once plugin_dir_path(__FILE__) . 'src/Constants.php';

// TODO: Refactor this to use the APP_ENV variable
if (! isset($_ENV['APP_ENV'])) {
	$_ENV['APP_ENV'] = 'production';
}

$TEST = $_ENV['APP_ENV'] === 'test';
$HELLOTEXT_DEV_MODE = $_ENV['APP_ENV'] === 'development';
$HELLOTEXT_API_URL = $HELLOTEXT_DEV_MODE
 ? $_ENV['HELLOTEXT_API_URL'] ?? ''
 : 'https://api.hellotext.com';


if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

$paths = [
	'Adapters',
	'Api',
	'Events',
	'Misc',
	'Services',
];

foreach ($paths as $current_path) {
	$scan = scandir(plugin_dir_path( __FILE__ ) . 'src/' . $current_path . '/');

	foreach ($scan as $file) {
		if (strpos($file, '.php') !== false) {
			include('src/' . $current_path . '/' . $file);
		}
	}
}

// Function on Events/AppRemoved.php
register_deactivation_hook( __FILE__, 'hellotext_deactivate' );

/**
 * Check for plugin updates from GitHub.
 * Caches result for 24 hours to avoid rate limiting.
 *
 * @return void
 */
function hellotext_version_check() {
	if (!is_admin()) {
		return;
	}

	$cache_key = 'hellotext_version_check';
	$cached = get_transient($cache_key);

	if (false !== $cached) {
		if (isset($cached['message'])) {
			echo '<div class="notice notice-warning"><p>' . wp_kses_post($cached['message']) . '</p></div>';
		}
		return;
	}

	$response = wp_remote_get(
		'https://api.github.com/repos/hellotext/hellotext-wordpress/releases/latest',
		['timeout' => 5]
	);

	$cache_data = [];

	if (!is_wp_error($response) && 200 === wp_remote_retrieve_response_code($response)) {
		$release = json_decode(wp_remote_retrieve_body($response), true);

		if (isset($release['tag_name'])) {
			$current = get_plugin_data(__FILE__)['Version'];
			$latest = ltrim($release['tag_name'], 'v');

			if (version_compare($current, $latest, '<')) {
				$cache_data['message'] = sprintf(
					'New version of Hellotext available: %s. <a href="%s">View details</a>',
					$latest,
					$release['html_url']
				);
			}
		}
	}

	set_transient($cache_key, $cache_data, DAY_IN_SECONDS);

	if (isset($cache_data['message'])) {
		echo '<div class="notice notice-warning"><p>' . wp_kses_post($cache_data['message']) . '</p></div>';
	}
}
add_action('admin_notices', 'hellotext_version_check');

/**
 * Load plugin text domain.
 *
 * @return void
 */
function hellotext_load_textdomain() {
    load_plugin_textdomain( 'hellotext', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'hellotext_load_textdomain' );

/**
 * Uninstall handler for cleanup.
 *
 * @return void
 */
function uninstall() {
    global $wpdb;

    delete_option(Constants::OPTION_BUSINESS_ID);
    delete_option(Constants::OPTION_WEBCHAT_ID);
    delete_option(Constants::OPTION_WEBCHAT_PLACEMENT);
    delete_option(Constants::OPTION_WEBCHAT_BEHAVIOUR);
    delete_option(Constants::OPTION_ACCESS_TOKEN);

    $api_keys_table = $wpdb->prefix . 'woocommerce_api_keys';
    if ($wpdb->get_var("SHOW TABLES LIKE '$api_keys_table'") === $api_keys_table) {
        $wpdb->delete($api_keys_table, ['description' => 'Hellotext']);
    }
}

register_uninstall_hook(__FILE__, 'uninstall');
