<?php
/**
 * Hellotext
 *
 * @package Hellotext
 * @author Hellotext
 * @copyright 2020 Hellotext
 * @license GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Hellotext
 * Plugin URI: https://hellotext.com
 * Description: Integrates Hellotext tracking to WooCommerce.
 * Version: 0.0.1
 * Author: Hellotext
 * Author URI: https://hellotext.com
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * WgwXWr7P
 */

$HELLOTEXT_DEV_MODE = false;

include_once( plugin_dir_path( __FILE__ ) . 'hellotext/menu.php' );
include_once( plugin_dir_path( __FILE__ ) . 'hellotext/settings.php' );
include_once( plugin_dir_path( __FILE__ ) . 'hellotext/scripts.php' );

$paths = [
    'Adapters',
    'Api',
    'Events',
    'Services',
];

foreach ($paths as $path) {
    $scan = scandir( plugin_dir_path( __FILE__ ) . 'hellotext/' . $path . '/' );
    foreach ($scan as $file) {
        if (strpos($file, '.php') !== false) {
            include('hellotext/' . $path . '/' . $file);
        }
    }
}

// NOTE: Disabled for now.
// This function is under ./hellotext/events/app_removed.php
// The `app.installed` function is on ./hellotext/settings.php
// under the hellotext_business_id_updated name.
// register_deactivation_hook( __FILE__, 'hellotext_deactivate' );
// register_activation_hook  ( __FILE__, 'hellotext_activate' );

