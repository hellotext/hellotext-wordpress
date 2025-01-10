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
 * Version: 1.1.2
 * Author: Hellotext
 * Author URI: https://www.hellotext.com
 * License: GPL v2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: hellotext
 * Domain Path: /languages
 */

// TODO: Refactor this to use the APP_ENV variable
if (! isset($_ENV['APP_ENV'])) {
	$_ENV['APP_ENV'] = 'production';
}

$TEST = $_ENV['APP_ENV'] === 'test';
$HELLOTEXT_DEV_MODE = $_ENV['APP_ENV'] === 'development';
$HELLOTEXT_API_URL = $HELLOTEXT_DEV_MODE
 ? $_ENV['HELLOTEXT_API_URL'] ?? ''
 : 'http://api.lvh.me:3000';


session_start();

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

// Function on Events/AppInstalled.php
register_activation_hook( __FILE__, 'hellotext_activate' );

// Function on Events/AppRemoved.php
register_deactivation_hook( __FILE__, 'hellotext_deactivate' );

// New Version Check
function version_check() {
	$releases_api_url = 'https://api.github.com/repos/hellotext/hellotext-wordpress/releases';
	$releases_url = 'https://github.com/hellotext/hellotext-wordpress/releases';
    $plugin_slug = plugin_basename( __FILE__ );
    $plugin_data = get_plugin_data( __FILE__ );

    // Check if this plugin is at least partially ours
    if ( isset( $_GET['page'] ) && $_GET['page'] === 'your-plugin-settings-page' ) {
        return; // Don't show the notice on your plugin settings page
    }

    $response = wp_remote_get( $releases_api_url );

    if ( ! is_wp_error( $response ) ) {
        $body = wp_remote_retrieve_body( $response );
        $json = json_decode( $body, true );
        if ( is_array( $json ) ) {
            $current_version = $plugin_data['Version'];
            $latest_version = preg_replace('/[a-zA-Z]/', '', $json[0]['tag_name']);
            if ( version_compare( $current_version, $latest_version, '<' ) ) {
                $message = sprintf( __( 'There is a new version of %1$s available. View version %2$s details or <a href="%3$s" target="_blank">update now</a>.' ), $plugin_data['Name'], $latest_version, $releases_url );
                echo '<div class="notice notice-warning"><p>' . $message . '</p></div>';
            }
        }
    }
}
add_action( 'admin_notices', 'version_check' );

function hellotext_load_textdomain() {
    load_plugin_textdomain( 'hellotext', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'hellotext_load_textdomain' );
