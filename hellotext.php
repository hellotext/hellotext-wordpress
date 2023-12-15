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
 * Version: 0.1.2
 * Author: Hellotext
 * Author URI: https://hellotext.com
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

$HELLOTEXT_DEV_MODE = false;

$paths = [
	'Adapters',
	'Api',
	'Events',
	'Misc',
	'Services',
];

foreach ($paths as $current_path) {
	$scan = scandir( plugin_dir_path( __FILE__ ) . 'src/' . $current_path . '/' );
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
