<?php

if (!isset($GLOBALS['test_actions'])) {
	$GLOBALS['test_actions'] = [];
}

if (!isset($GLOBALS['test_post_meta'])) {
	$GLOBALS['test_post_meta'] = [];
}

if (!isset($GLOBALS['test_transients'])) {
	$GLOBALS['test_transients'] = [];
}

if (!isset($GLOBALS['test_user_meta'])) {
	$GLOBALS['test_user_meta'] = [];
}

if (!defined('DAY_IN_SECONDS')) {
	define('DAY_IN_SECONDS', 86400);
}

function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1)
{
	$GLOBALS['test_actions'][$hook_name][] = [
		'callback' => $callback,
		'accepted_args' => $accepted_args,
	];

	return true;
}

function do_action($hook_name, ...$args)
{
	foreach ($GLOBALS['test_actions'][$hook_name] ?? [] as $action) {
		call_user_func_array($action['callback'], array_slice($args, 0, $action['accepted_args']));
	}
}

function register_activation_hook($file, $callback)
{
	$GLOBALS['test_activation_hook'] = $callback;
}

function register_deactivation_hook($file, $callback)
{
	$GLOBALS['test_deactivation_hook'] = $callback;
}

function register_uninstall_hook($file, $callback)
{
	$GLOBALS['test_uninstall_hook'] = $callback;
}

function sanitize_text_field($value)
{
	return is_scalar($value) ? trim((string) $value) : '';
}

function site_url($path = '')
{
	return 'https://example.test' . $path;
}

function home_url($path = '')
{
	return 'https://example.test/' . ltrim($path, '/');
}

function add_query_arg($args = [], $url = '')
{
	return $url;
}

function wp_get_post_terms($post_id, $taxonomy, $args = [])
{
	return [];
}

function wp_get_attachment_url($attachment_id)
{
	return 'https://example.test/image.jpg';
}

function get_permalink($post_id)
{
	return 'https://example.test/product/' . $post_id;
}

function load_plugin_textdomain($domain, $deprecated = false, $plugin_rel_path = false)
{
	return true;
}

function is_admin()
{
	return false;
}

function get_transient($transient)
{
	return $GLOBALS['test_transients'][$transient] ?? false;
}

function set_transient($transient, $value, $expiration = 0)
{
	$GLOBALS['test_transients'][$transient] = $value;

	return true;
}

function delete_transient($transient)
{
	unset($GLOBALS['test_transients'][$transient]);

	return true;
}

function wp_kses_post($data)
{
	return $data;
}

function wp_kses($data, $allowed_html = [])
{
	return $data;
}

function __($text, $domain = 'default')
{
	return $text;
}

function _e($text, $domain = 'default')
{
	echo $text;
}

function esc_attr($text)
{
	return htmlspecialchars((string) $text, ENT_QUOTES);
}

function esc_html($text)
{
	return htmlspecialchars((string) $text, ENT_QUOTES);
}

function selected($selected, $current = true, $echo = true)
{
	$result = $selected == $current ? ' selected="selected"' : '';
	if ($echo) {
		echo $result;
	}

	return $result;
}

function add_settings_section()
{
	return true;
}

function add_settings_field()
{
	return true;
}

function register_setting()
{
	return true;
}

function add_submenu_page()
{
	return true;
}

function settings_fields()
{
	return true;
}

function do_settings_sections()
{
	return true;
}

function submit_button()
{
	return true;
}

function get_current_user_id()
{
	return 1;
}

function get_bloginfo($show = '')
{
	return match ($show) {
		'name' => 'Test Store',
		'url' => 'https://example.test',
		'admin_email' => 'admin@example.test',
		default => 'Test Store',
	};
}

function wc_rand_hash()
{
	return 'testhash';
}

function wc_api_hash($data)
{
	return 'hashed_' . $data;
}

function get_user_by($field, $value)
{
	$users = $GLOBALS['test_users'] ?? [];

	if ('id' === $field) {
		return $users[$value] ?? null;
	}

	if ('email' === $field) {
		foreach ($users as $user) {
			if (($user->user_email ?? null) === $value) {
				return $user;
			}
		}
	}

	return null;
}

function wp_create_user($name, $password, $email)
{
	$user = new WP_User(count($GLOBALS['test_users'] ?? []) + 1);
	$user->nickname = $name;
	$user->last_name = '';
	$user->user_email = $email;
	$GLOBALS['test_users'][$user->ID] = $user;

	return $user;
}

function wp_get_current_user()
{
	return new WP_User(1);
}

function is_user_logged_in()
{
	return $GLOBALS['test_is_user_logged_in'] ?? true;
}

function get_user_meta($user_id, $key = '', $single = false)
{
	if ('' === $key) {
		return $GLOBALS['test_user_meta'][$user_id] ?? [];
	}

	return $GLOBALS['test_user_meta'][$user_id][$key] ?? '';
}

function add_user_meta($user_id, $meta_key, $meta_value, $unique = false)
{
	$GLOBALS['test_user_meta'][$user_id][$meta_key] = $meta_value;

	return true;
}

function update_user_meta($user_id, $meta_key, $meta_value)
{
	$GLOBALS['test_user_meta'][$user_id][$meta_key] = $meta_value;

	return true;
}

function plugin_basename($file)
{
	return basename($file);
}

function delete_option($option)
{
	unset($GLOBALS['test_options'][$option]);

	return true;
}

function add_post_meta($post_id, $meta_key, $meta_value, $unique = false)
{
	$GLOBALS['test_post_meta'][$post_id][$meta_key] = $meta_value;

	return true;
}

function update_post_meta($post_id, $meta_key, $meta_value)
{
	$GLOBALS['test_post_meta'][$post_id][$meta_key] = $meta_value;

	return true;
}

function get_post_meta($post_id, $key = '', $single = false)
{
	if ('' === $key) {
		return $GLOBALS['test_post_meta'][$post_id] ?? [];
	}

	return $GLOBALS['test_post_meta'][$post_id][$key] ?? '';
}

function wc_load_cart()
{
	return true;
}

function WC()
{
	if (!isset($GLOBALS['test_woocommerce'])) {
		$GLOBALS['test_woocommerce'] = (object) [
			'cart' => new WC_Cart(),
		];
	}

	return $GLOBALS['test_woocommerce'];
}

// Mock WooCommerce wc_create_order function
function wc_create_order($args = array())
{
	return new WC_Order();
}

// Mock WooCommerce wc_create_refund function
function wc_create_refund($args = array())
{
	return new WC_Order_Refund($args);
}

// Mock WooCommerce wc_get_product function
function wc_get_product($id = 0)
{
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

function wc_get_order($id = 0)
{
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

function get_woocommerce_currency()
{
	return 'USD';
}

class WC_Order
{
	public $data = [
		'billing' => [
			'first_name' => 'Jane',
			'last_name' => 'Doe',
			'email' => 'jane@example.test',
			'phone' => '+15555550100',
		],
	];

	private array $meta_data = [];

	public function get_id()
	{
		return 1;
	}

	public function get_user_id()
	{
		return 1;
	}

	public function get_total()
	{
		return 50;
	}

	public function get_currency()
	{
		return 'USD';
	}

	public function add_product($product, $quantity)
	{
		$item = new WC_Order_Item_Product();
		$item->set_quantity($quantity);

		return $item;
	}

	public function get_items()
	{
		return [
			new WC_Order_Item_Product()
		];
	}

	public function apply_changes()
	{
		return true;
	}

	public function save()
	{
		$GLOBALS['test_saved_orders'][$this->get_id()] = true;

		return true;
	}

	public function add_meta_data($key, $value, $unique = false)
	{
		$this->meta_data[$key] = $value;
		$GLOBALS['test_post_meta'][$this->get_id()][$key] = $value;
	}

	public function update_meta_data($key, $value)
	{
		$this->add_meta_data($key, $value);
	}

	public function get_meta($key = '', $single = true)
	{
		if (isset($this->meta_data[$key])) {
			return $this->meta_data[$key];
		}

		return $GLOBALS['test_post_meta'][$this->get_id()][$key] ?? '';
	}
}

class WC_Order_Refund
{

	public $amount;
	public $order_id;

	public function __construct($args = [])
	{
		if (is_array($args)) {
			$this->amount = $args['amount'] ?? 25;
			$this->order_id = $args['order_id'] ?? 1;
			return;
		}

		$this->amount = 25;
		$this->order_id = 1;
	}
	public function get_id()
	{
		return 1;
	}

	public function get_amount()
	{
		return $this->amount;
	}

	public function get_currency()
	{
		return 'USD';
	}
}

class WC_Order_Item_Product
{
	public $quantity;

	public function get_product_id()
	{
		return 1;
	}

	public function set_quantity($quantity)
	{
		$this->quantity = $quantity;
	}

	public function get_quantity()
	{
		return $this->quantity;
	}

	public function get_product()
	{
		return new WC_Product();
	}
}

class WC_Product
{
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

	public function save()
	{
	}

	public function get_id()
	{
		return 1;
	}

	// getters

	public function get_name()
	{
		return 'simple';
	}

	public function get_price()
	{
		return 50;
	}

	public function get_image_id()
	{
		return 1;
	}

	public function set_props($props)
	{
		foreach ($props as $key => $value) {
			$this->$key = $value;
		}
	}
}

class WC_Coupon
{
	public function __construct(public string $code)
	{
	}

	public function get_id()
	{
		return 10;
	}

	public function get_description()
	{
		return 'Test coupon';
	}
}

class WC_Discounts
{
	public function is_coupon_valid($coupon)
	{
		return $GLOBALS['test_coupon_valid'] ?? true;
	}
}

class WC_Cart
{
	public array $items = [];

	public function get_cart()
	{
		return $this->items;
	}

	public function get_cart_contents_total()
	{
		return 50;
	}
}

class User
{
	public $ID;
	public string $nickname = '';
	public string $last_name = '';
	public string $user_email = '';

	public function __construct($id)
	{
		$this->ID = $id;
	}

	public function get_id()
	{
		return $this->ID;
	}
}

class WP_User extends User
{
}

class WP_Error
{
	public function __construct(private string $message = 'Test error')
	{
	}

	public function get_error_message()
	{
		return $this->message;
	}
}

// Mock WordPress HTTP API functions
if (!function_exists('wp_remote_request')) {
	function wp_remote_request($url, $args = array())
	{
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
	function wp_remote_post($url, $args = array())
	{
		return wp_remote_request($url, $args);
	}
}

if (!function_exists('wp_remote_get')) {
	function wp_remote_get($url, $args = array())
	{
		return wp_remote_request($url, $args);
	}
}

if (!function_exists('is_wp_error')) {
	function is_wp_error($thing)
	{
		return $thing instanceof WP_Error;
	}
}

if (!function_exists('wp_remote_retrieve_response_code')) {
	function wp_remote_retrieve_response_code($response)
	{
		return $response['response']['code'] ?? 200;
	}
}

if (!function_exists('wp_remote_retrieve_body')) {
	function wp_remote_retrieve_body($response)
	{
		return $response['body'] ?? '';
	}
}

if (!function_exists('get_option')) {
	function get_option($option, $default = false)
	{
		// Allow per-test override
		if (isset($GLOBALS['test_options'][$option])) {
			return $GLOBALS['test_options'][$option];
		}

		// Default mock values
		if ($option === 'hellotext_access_token') {
			return 'test_token_123';
		}

		if ($option === 'hellotext_business_id') {
			return 'test_business_123';
		}

		return $default;
	}
}

if (!function_exists('get_plugin_data')) {
	function get_plugin_data($plugin_file, $markup = true, $translate = true)
	{
		// Allow per-test override
		if (isset($GLOBALS['test_plugin_data'])) {
			return $GLOBALS['test_plugin_data'];
		}

		return [
			'Name' => 'Hellotext',
			'Version' => '1.3.1',
			'Author' => 'Hellotext',
		];
	}
}
