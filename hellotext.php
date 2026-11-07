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
 * Version: 2.0.1
 * Requires Plugins: woocommerce
 * Author: Hellotext
 * Author URI: https://www.hellotext.com
 * License: GPL v2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: hellotext
 * Domain Path: /languages
 */

use Hellotext\Constants;

// Load Composer autoloader (handles all classes)
require_once __DIR__ . '/vendor/autoload.php';

// Load event handlers (contain functions, not classes)
$event_files = glob(__DIR__ . '/src/Events/*.php');
foreach ($event_files as $file) {
	require_once $file;
}

// Load misc files (Settings, Scripts contain functions)
$misc_files = glob(__DIR__ . '/src/Misc/*.php');
foreach ($misc_files as $file) {
	require_once $file;
}

// Functions on Events/AppInstalled.php and Events/AppRemoved.php
register_activation_hook(__FILE__, 'hellotext_activate');
register_deactivation_hook(__FILE__, 'hellotext_deactivate');

/**
 * Check for plugin updates from GitHub.
 * Caches result for 24 hours to avoid rate limiting.
 *
 * @return void
 */
function hellotext_version_check() {
	if (!is_admin() || !current_user_can('update_plugins')) {
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
					esc_html($latest),
					esc_url($release['html_url'])
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
function hellotext_uninstall() {
    foreach (Constants::OPTIONS as $option) {
        delete_option($option);
    }

    delete_transient('hellotext_version_check');
    delete_transient('hellotext_integration_triggered');

    hellotext_delete_api_keys();
}

register_uninstall_hook(__FILE__, 'hellotext_uninstall');
