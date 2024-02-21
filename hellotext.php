<?php
/**
 * Hellotext
 *
 * @package Hellotext
 *
 * @wordpress-plugin
 * Plugin Name: Hellotext
 * Plugin URI: https://hellotext.com
 * Description: Integrates Hellotext tracking to WooCommerce.
 * Version: 0.1.4
 * Author: Hellotext Team
 * Author URI: https://github.com/hellotext
 * License: GPL v2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// TODO: Refactor this to use the APP_ENV variable
$HELLOTEXT_DEV_MODE = false;

if (! isset($_ENV['APP_ENV'])) {
	$_ENV['APP_ENV'] = 'production';
}

$TEST = $_ENV['APP_ENV'] === 'test';

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
