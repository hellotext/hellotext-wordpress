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
 */

include_once( plugin_dir_path( __FILE__ ) . 'hellotext/menu.php' );
include_once( plugin_dir_path( __FILE__ ) . 'hellotext/settings.php' );
include_once( plugin_dir_path( __FILE__ ) . 'hellotext/inject_script.php' );

// API
include_once( plugin_dir_path( __FILE__ ) . 'hellotext/api/cart.php' );
include_once( plugin_dir_path( __FILE__ ) . 'hellotext/api/product.php' );
