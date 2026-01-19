<?php

// Mock WooCommerce wc_create_order function
function wc_create_order ($args = array()) {
	return new WC_Order();
}

// Mock WooCommerce wc_create_refund function
function wc_create_refund ($args = array()) {
	return new WC_Order_Refund($args);
}

// Mock WooCommerce wc_get_product function
function wc_get_product ($id = 0) {
	// If already a WC_Product, return it
	if ($id instanceof WC_Product) {
		return $id;
	}

	// If invalid ID, return false
	if (!is_numeric($id) || $id <= 0) {
		return false;
	}

	return new WC_Product();
}

function wc_get_order ($id = 0) {
	// If already a WC_Order, return it
	if ($id instanceof WC_Order) {
		return $id;
	}

	// If invalid ID, return false
	if (!is_numeric($id) || $id <= 0) {
		return false;
	}

	return new WC_Order();
}

function get_woocommerce_currency () {
	return 'USD';
}

class WC_Order {
	public function get_id () {
		return 1;
	}

	public function get_total () {
		return 50;
	}

	public function get_currency () {
		return 'USD';
	}

	public function add_product ($product, $quantity) {
		$item = new WC_Order_Item_Product();
		$item->set_quantity($quantity);

		return $item;
	}

	public function get_items () {
		return [
			new WC_Order_Item_Product()
		];
	}

	public function apply_changes () {
		return true;
	}

	public function save () {
		return true;
	}
}

class WC_Order_Refund {

	public $amount;
	public $order_id;

	public function __construct ($args) {
		$this->amount = $args['amount'];
		$this->order_id = $args['order_id'];
	}
	public function get_id () {
		return 1;
	}

	public function get_amount () {
		return $this->amount;
	}

	public function get_currency () {
		return 'USD';
	}
}

class WC_Order_Item_Product {
	public $quantity;

	public function get_product_id () {
		return 1;
	}

	public function set_quantity ($quantity) {
		$this->quantity = $quantity;
	}

	public function get_quantity () {
		return $this->quantity;
	}

	public function get_product () {
		return new WC_Product();
	}
}

class WC_Product {
	public $reference;
	public $type;
	public $name;
	public $categories;
	public $currency;
	public $price;
	public $amount;
	public $tags;
	public $fields;
	public $image_url;

	public function save () {
	}

	public function get_id () {
		return 1;
	}

	// getters

	public function get_name () {
		return 'simple';
	}

	public function get_price () {
		return 50;
	}

	public function get_image_id () {
		return 1;
	}

	public function set_props ($props) {
		foreach ($props as $key => $value) {
			$this->$key = $value;
		}
	}
}

class User {
	public $ID;

	public function __construct ($id) {
		$this->ID = $id;
	}

	public function get_id () {
		return $this->ID;
	}
}

// Mock WordPress HTTP API functions
if (!function_exists('wp_remote_request')) {
	function wp_remote_request ($url, $args = array()) {
		// Allow per-test override
		if (isset($GLOBALS['test_wp_remote_request'])) {
			return $GLOBALS['test_wp_remote_request']($url, $args);
		}

		return array(
			'response' => array('code' => 200),
			'body' => json_encode(array('success' => true)),
		);
	}
}

if (!function_exists('wp_remote_post')) {
	function wp_remote_post ($url, $args = array()) {
		return wp_remote_request($url, $args);
	}
}

if (!function_exists('wp_remote_get')) {
	function wp_remote_get ($url, $args = array()) {
		return wp_remote_request($url, $args);
	}
}

if (!function_exists('is_wp_error')) {
	function is_wp_error ($thing) {
		return $thing instanceof WP_Error;
	}
}

if (!function_exists('wp_remote_retrieve_response_code')) {
	function wp_remote_retrieve_response_code ($response) {
		return $response['response']['code'] ?? 200;
	}
}

if (!function_exists('wp_remote_retrieve_body')) {
	function wp_remote_retrieve_body ($response) {
		return $response['body'] ?? '';
	}
}

if (!function_exists('get_option')) {
	function get_option ($option, $default = false) {
		// Allow per-test override
		if (isset($GLOBALS['test_options'][$option])) {
			return $GLOBALS['test_options'][$option];
		}

		// Default mock values
		if ($option === 'hellotext_access_token') {
			return 'test_token_123';
		}

		return $default;
	}
}

if (!function_exists('get_plugin_data')) {
	function get_plugin_data ($plugin_file, $markup = true, $translate = true) {
		// Allow per-test override
		if (isset($GLOBALS['test_plugin_data'])) {
			return $GLOBALS['test_plugin_data'];
		}

		return [
			'Name' => 'Hellotext',
			'Version' => '1.3.0',
			'Author' => 'Hellotext',
		];
	}
}
