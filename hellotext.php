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


add_action( 'admin_menu', 'hellotext_admin_menu' );
function hellotext_admin_menu()  {
    add_menu_page(
        'Hellotext',// page title
        'Hellotext', // menu title
        'manage_options',// capability
        'hellotext',// menu slug
    );
}


add_action( 'admin_init', 'hellotext_settings_init' );
function hellotext_settings_init() {

    add_settings_section(
        'hellotext_setting_section',
        __( 'Hellotext Settings', 'hellotext_settings' ),
        'hellotext_description_section_callback',
        'hellotext-form'
    );

    add_settings_field(
       'business_id',
       __( 'Business ID', 'business_id' ),
       'business_id_field',
       'hellotext-form',
       'hellotext_setting_section'
    );

    register_setting( 'hellotext-form', 'business_id' );
}

function hellotext_description_section_callback () {
    echo 'Set your Hellotext business ID here.';
}

function business_id_field () {
    ?>
    <label for="business_id"><?php _e( 'Bueiness ID' ); ?></label>
    <input type="text" id="business_id" name="business_id" value="<?php echo get_option( 'business_id' ); ?>">
    <?php
}


add_action('plugins_loaded', 'init_hellotext');
function init_hellotext () {
    function custom_woocommerce_menu() {
        add_submenu_page(
            'woocommerce',
            'Hellotext',
            'Hellotext',
            'manage_options',
            'wc-hellotext',
            'hellotext_submenu_page_callback'
        );
    }
    add_action('admin_menu', 'custom_woocommerce_menu');

    function hellotext_submenu_page_callback () {
        ?>
            <h1>Hellotext</h1>
            <p>Here you can configure your Hellotext business where the analytics data will be sent to.</p>

            <form method="POST" action="options.php">
        <?php
            settings_fields( 'hellotext-form' );
            do_settings_sections( 'hellotext-form' );
            submit_button();
        ?>
            </form>
        <?php
    }
}

