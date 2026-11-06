<?php

use Hellotext\Constants;
use Hellotext\Services\Session;

beforeEach(function () {
	$GLOBALS['test_http_requests'] = [];
	$GLOBALS['test_post_meta'] = [];
	$GLOBALS['test_saved_orders'] = [];
	$GLOBALS['test_coupon_valid'] = true;
	$GLOBALS['test_is_user_logged_in'] = true;
	$GLOBALS['test_current_user_id'] = 1;
	$GLOBALS['test_users'] = [];
	$GLOBALS['test_user_meta'] = [];
	$GLOBALS['test_options'] = [];
	$GLOBALS['test_doing_ajax'] = false;
	$GLOBALS['test_referer'] = false;
	$GLOBALS['test_order_status'] = 'processing';
	$GLOBALS['test_order_user_id'] = 1;
	$GLOBALS['test_post_terms'] = [];
	$GLOBALS['wpdb']->tables = ['wp_woocommerce_api_keys' => []];
	unset($GLOBALS['test_actions']['shutdown'], $GLOBALS['test_woocommerce'], $GLOBALS['test_product_parent_id'], $GLOBALS['test_order_item_product']);
	$GLOBALS['wp'] = (object) ['request' => 'cart'];
	$_COOKIE = [];

	$GLOBALS['test_wp_remote_request'] = function ($url, $args = []) {
		$GLOBALS['test_http_requests'][] = [
			'url' => $url,
			'args' => $args,
			'body' => json_decode($args['body'] ?? '{}', true),
		];

		return [
			'response' => ['code' => 200],
			'body' => json_encode(['success' => true, 'id' => 'profile_123']),
		];
	};
});

function tracked_request(int $index = -1): array
{
	$requests = $GLOBALS['test_http_requests'];

	return $requests[$index < 0 ? count($requests) + $index : $index];
}

function tracked_event(int $index = -1): array
{
	return tracked_request($index)['body'];
}

function tracked_actions(): array
{
	return array_values(array_filter(array_map(
		fn ($request) => $request['body']['action'] ?? null,
		$GLOBALS['test_http_requests']
	)));
}

function a_registered_user(int $id): WP_User
{
	$user = new WP_User($id);
	$user->nickname = 'janed';
	$user->first_name = 'Jane';
	$user->last_name = 'Doe';
	$user->user_email = 'jane@example.test';
	$GLOBALS['test_users'][$user->ID] = $user;

	return $user;
}

function a_cart_with(int $quantity): void
{
	WC()->cart->items = $quantity > 0
		? ['abc' => ['data' => new WC_Product(), 'quantity' => $quantity]]
		: [];
}

test('loads the latest unversioned Hellotext.js with only the business ID', function () {
	$GLOBALS['test_scripts'] = [];
	$GLOBALS['test_inline_scripts'] = [];

	hellotext_enqueue_script();

	expect($GLOBALS['test_scripts']['hellotext']['src'])->toBe('https://cdn.jsdelivr.net/npm/@hellotext/hellotext/dist/hellotext.min.js')
		->and($GLOBALS['test_scripts']['hellotext']['ver'])->toBeNull()
		->and($GLOBALS['test_inline_scripts']['hellotext'])->toBe(['Hellotext.initialize("test_business_123");']);
});

test('does not load Hellotext.js before the business ID is set', function () {
	$GLOBALS['test_scripts'] = [];
	$GLOBALS['test_options'][Constants::OPTION_BUSINESS_ID] = '';

	hellotext_enqueue_script();

	expect($GLOBALS['test_scripts'])->toBe([]);
});

test('tracks product viewed events', function () {
	global $product;

	$_COOKIE[Constants::SESSION_COOKIE_NAME] = 'browser-session';
	$product = new WC_Product();

	do_action('woocommerce_after_single_product');

	$event = tracked_event();
	$request = tracked_request();

	expect($event['action'])->toBe(Constants::EVENT_PRODUCT_VIEWED)
		->and($event['session'])->toBe('browser-session')
		->and($event['object_parameters']['reference'])->toBe(1)
		->and($event['object_parameters']['source'])->toBe('woo')
		->and($request['url'])->toBe('https://api.hellotext.com/v1/track/events')
		->and($request['args']['headers']['Authorization'])->toBe('Bearer test_business_123');
});

test('reads variation categories from the parent product', function () {
	global $product;

	$GLOBALS['test_product_parent_id'] = 7;
	$GLOBALS['test_post_terms'][7]['product_cat'] = ['Shoes'];
	$product = new WC_Product();

	do_action('woocommerce_after_single_product');

	expect(tracked_event()['object_parameters']['categories'])->toBe(['Shoes']);
});

test('tracks valid coupon redemption only', function () {
	do_action('woocommerce_applied_coupon', 'SAVE10');

	$event = tracked_event();

	expect($event['action'])->toBe(Constants::EVENT_COUPON_REDEEMED)
		->and($event['object_parameters']['code'])->toBe('SAVE10')
		->and($event['object_parameters']['destination_url'])->toBe('https://example.test/cart');

	$GLOBALS['test_http_requests'] = [];
	$GLOBALS['test_coupon_valid'] = new WP_Error('Coupon expired');

	do_action('woocommerce_applied_coupon', 'EXPIRED');

	expect($GLOBALS['test_http_requests'])->toBe([]);
});

test('tracks cart changes once at the end of the request', function () {
	$_COOKIE[Constants::SESSION_COOKIE_NAME] = 'cart-session';
	$GLOBALS['test_current_user_id'] = 0;
	a_cart_with(2);

	do_action('woocommerce_add_to_cart');
	do_action('woocommerce_add_to_cart');

	expect($GLOBALS['test_http_requests'])->toBe([]);

	do_action('shutdown');

	$added = tracked_event();
	expect(tracked_actions())->toBe([Constants::EVENT_CART_ADDED])
		->and($added['session'])->toBe('cart-session')
		->and($added['amount'])->toBe(50)
		->and($added['currency'])->toBe('USD')
		->and($added['url'])->toBe('https://example.test/cart')
		->and($added['object_parameters']['items'][0]['quantity'])->toBe(2)
		->and($added['object_parameters']['items'][0]['product']['reference'])->toBe(1);

	$GLOBALS['test_http_requests'] = [];
	a_cart_with(0);

	do_action('woocommerce_cart_item_removed');
	do_action('shutdown');

	$removed = tracked_event();
	expect($removed['action'])->toBe(Constants::EVENT_CART_REMOVED)
		->and($removed['object_parameters']['items'][0]['quantity'])->toBe(2);
});

test('reports only the quantity that changed and ignores unchanged carts', function () {
	$GLOBALS['test_current_user_id'] = 0;
	a_cart_with(1);
	hellotext_cart_updated();
	$GLOBALS['test_http_requests'] = [];

	a_cart_with(3);
	hellotext_cart_updated();

	expect(tracked_event()['action'])->toBe(Constants::EVENT_CART_ADDED)
		->and(tracked_event()['object_parameters']['items'][0]['quantity'])->toBe(2);

	$GLOBALS['test_http_requests'] = [];

	hellotext_cart_updated();

	expect($GLOBALS['test_http_requests'])->toBe([]);
});

test('does not report purchased items as removed when the cart is emptied', function () {
	$GLOBALS['test_current_user_id'] = 0;
	a_cart_with(2);
	hellotext_cart_updated();
	$GLOBALS['test_http_requests'] = [];

	a_cart_with(0);
	do_action('woocommerce_cart_emptied');
	hellotext_cart_updated();

	expect($GLOBALS['test_http_requests'])->toBe([]);
});

test('reports the page a background add to cart came from', function () {
	$GLOBALS['test_current_user_id'] = 0;
	$GLOBALS['test_doing_ajax'] = true;
	$GLOBALS['test_referer'] = 'https://example.test/shop';
	a_cart_with(1);

	hellotext_cart_updated();

	expect(tracked_event()['url'])->toBe('https://example.test/shop');
});

test('tracks order placed with the session captured at checkout', function () {
	$_COOKIE[Constants::SESSION_COOKIE_NAME] = 'checkout-session';
	$order = new WC_Order();

	do_action('woocommerce_store_api_checkout_order_processed', $order);

	expect(tracked_actions())->toBe([]);

	$_COOKIE = [];
	do_action('woocommerce_thankyou', $order->get_id());

	$event = tracked_event();

	expect($event['action'])->toBe(Constants::EVENT_ORDER_PLACED)
		->and($event['session'])->toBe('checkout-session')
		->and($event['object_parameters']['reference'])->toBe(1)
		->and(Session::decrypt($order->get_meta(Constants::META_SESSION, true)))->toBe('checkout-session');
});

test('tracks order placed once per order', function () {
	$order = new WC_Order();
	hellotext_capture_checkout($order);

	do_action('woocommerce_thankyou', $order->get_id());
	do_action('woocommerce_thankyou', $order->get_id());
	do_action('woocommerce_order_status_changed', $order->get_id(), 'pending', 'completed', wc_get_order($order->get_id()));

	expect(array_count_values(tracked_actions())[Constants::EVENT_ORDER_PLACED])->toBe(1);
});

test('tracks order placed when an unpaid order is paid later', function () {
	$_COOKIE[Constants::SESSION_COOKIE_NAME] = 'checkout-session';
	$GLOBALS['test_order_status'] = 'pending';
	$order = new WC_Order();

	do_action('woocommerce_checkout_order_processed', $order->get_id(), [], $order);
	do_action('woocommerce_thankyou', $order->get_id());

	expect(tracked_actions())->toBe([]);

	$_COOKIE[Constants::SESSION_COOKIE_NAME] = 'shop-manager-session';
	$GLOBALS['test_order_status'] = 'processing';
	do_action('woocommerce_order_status_changed', $order->get_id(), 'pending', 'processing', $order);

	expect(tracked_actions())->toBe([Constants::EVENT_ORDER_PLACED, Constants::EVENT_ORDER_CONFIRMED])
		->and(tracked_event(-2)['session'])->toBe('checkout-session')
		->and(tracked_event(-1)['session'])->toBe('checkout-session');
});

test('does not track order placed again for orders tracked by earlier versions', function () {
	$order = new WC_Order();
	$order->update_meta_data(Constants::META_SESSION, Session::encrypt('legacy-session'));

	do_action('woocommerce_thankyou', $order->get_id());
	do_action('woocommerce_order_status_changed', $order->get_id(), 'processing', 'completed', $order);

	expect(tracked_actions())->toBe([Constants::EVENT_ORDER_DELIVERED]);
});

test('creates a guest profile from the billing details at checkout', function () {
	$_COOKIE[Constants::SESSION_COOKIE_NAME] = 'guest-session';
	$GLOBALS['test_current_user_id'] = 0;
	$GLOBALS['test_order_user_id'] = 0;

	hellotext_capture_checkout(new WC_Order());

	expect(tracked_request(0)['url'])->toBe('https://api.hellotext.com/v1/profiles')
		->and(tracked_request(0)['body']['email'])->toBe('jane@example.test')
		->and(tracked_request(0)['body']['phone'])->toBe('+15555550100')
		->and(tracked_request(1)['url'])->toBe('https://api.hellotext.com/v1/sessions/guest-session')
		->and(tracked_request(1)['body']['profile'])->toBe('profile_123');
});

test('tracks supported order status transitions with the stored session', function (string $status, string $action) {
	$_COOKIE[Constants::SESSION_COOKIE_NAME] = 'shop-manager-session';
	$GLOBALS['test_order_status'] = $status;
	$order = new WC_Order();
	$order->update_meta_data(Constants::META_SESSION, Session::encrypt('stored-session'));

	do_action('woocommerce_order_status_changed', $order->get_id(), 'pending', $status, $order);

	$event = tracked_event();

	expect($GLOBALS['test_http_requests'])->toHaveCount(1)
		->and($event['action'])->toBe($action)
		->and($event['session'])->toBe('stored-session')
		->and($event['object_parameters']['reference'])->toBe(1);
})->with([
	'processing' => ['processing', Constants::EVENT_ORDER_CONFIRMED],
	'cancelled' => ['cancelled', Constants::EVENT_ORDER_CANCELLED],
	'completed' => ['completed', Constants::EVENT_ORDER_DELIVERED],
]);

test('never sends the shop manager session for orders without one', function () {
	$_COOKIE[Constants::SESSION_COOKIE_NAME] = 'shop-manager-session';
	$order = new WC_Order();

	do_action('woocommerce_order_status_changed', $order->get_id(), 'pending', 'completed', $order);

	expect(tracked_event()['session'])->toBeNull();
});

test('does not track unsupported order status transitions', function () {
	$GLOBALS['test_order_status'] = 'on-hold';
	$order = new WC_Order();

	do_action('woocommerce_order_status_changed', $order->get_id(), 'pending', 'on-hold', $order);

	expect($GLOBALS['test_http_requests'])->toBe([]);
});

test('tracks orders whose products were deleted', function () {
	$GLOBALS['test_order_item_product'] = false;
	$order = new WC_Order();

	do_action('woocommerce_order_status_changed', $order->get_id(), 'processing', 'completed', $order);

	expect(tracked_event()['object_parameters']['items'][0]['product']['name'])->toBe('Deleted product');
});

test('tracks refunds with the order session', function () {
	$order = new WC_Order();
	$order->update_meta_data(Constants::META_SESSION, Session::encrypt('refund-session'));

	do_action('woocommerce_order_refunded', $order->get_id(), 1);

	$event = tracked_event();

	expect($GLOBALS['test_http_requests'])->toHaveCount(1)
		->and($event['action'])->toBe(Constants::EVENT_REFUND_RECEIVED)
		->and($event['session'])->toBe('refund-session')
		->and($event['object_parameters']['source'])->toBe('woo');
});

test('creates a profile when a visitor registers', function () {
	$_COOKIE[Constants::SESSION_COOKIE_NAME] = 'registration-session';
	$GLOBALS['test_current_user_id'] = 0;
	$user = a_registered_user(42);
	$GLOBALS['test_user_meta'][$user->ID]['billing_phone'] = '+15555550100';

	do_action('user_register', $user->ID);

	$profile_request = tracked_request(0);
	$session_request = tracked_request(1);

	expect($profile_request['url'])->toBe('https://api.hellotext.com/v1/profiles')
		->and($profile_request['args']['method'])->toBe('POST')
		->and($profile_request['args']['headers']['Authorization'])->toBe('Bearer test_token_123')
		->and($profile_request['args']['headers']['X-Plugin-Version'])->toBe('1.3.1')
		->and($profile_request['body']['session'])->toBe('registration-session')
		->and($profile_request['body']['reference'])->toBe(42)
		->and($profile_request['body']['first_name'])->toBe('Jane')
		->and($profile_request['body']['email'])->toBe('jane@example.test')
		->and($profile_request['body']['phone'])->toBe('+15555550100')
		->and($GLOBALS['test_user_meta'][$user->ID][Constants::META_PROFILE_ID])->toBe('profile_123')
		->and($session_request['url'])->toBe('https://api.hellotext.com/v1/sessions/registration-session')
		->and($session_request['args']['method'])->toBe('PATCH')
		->and($session_request['body']['profile'])->toBe('profile_123');
});

test('does not link a shop manager session to users they create', function () {
	$_COOKIE[Constants::SESSION_COOKIE_NAME] = 'shop-manager-session';
	$GLOBALS['test_current_user_id'] = 1;
	$user = a_registered_user(42);

	do_action('user_register', $user->ID);

	expect($GLOBALS['test_http_requests'])->toHaveCount(1)
		->and(tracked_request(0)['body'])->not->toHaveKey('session');
});

test('links a signed-in customer session once', function () {
	global $product;

	$_COOKIE[Constants::SESSION_COOKIE_NAME] = 'customer-session';
	$user = a_registered_user(1);
	$GLOBALS['test_user_meta'][$user->ID][Constants::META_PROFILE_ID] = 'profile_123';
	$product = new WC_Product();

	do_action('woocommerce_after_single_product');
	do_action('woocommerce_after_single_product');

	$session_requests = array_filter(
		$GLOBALS['test_http_requests'],
		fn ($request) => str_ends_with($request['url'], '/v1/sessions/customer-session')
	);

	expect($session_requests)->toHaveCount(1);
});

test('connects once both settings are saved and replaces the API key', function () {
	$GLOBALS['wpdb']->tables['wp_woocommerce_api_keys'][] = ['description' => 'Hellotext', 'consumer_key' => 'old'];

	do_action('update_option_' . Constants::OPTION_BUSINESS_ID, '', 'test_business_123', Constants::OPTION_BUSINESS_ID);
	do_action('update_option_' . Constants::OPTION_ACCESS_TOKEN, 'old_token', 'test_token_123', Constants::OPTION_ACCESS_TOKEN);

	expect($GLOBALS['test_http_requests'])->toBe([]);

	do_action('shutdown');

	$keys = $GLOBALS['wpdb']->tables['wp_woocommerce_api_keys'];
	$request = tracked_request();

	expect($GLOBALS['test_http_requests'])->toHaveCount(1)
		->and($request['url'])->toBe('https://api.hellotext.com/integrations/woo')
		->and($request['args']['method'])->toBe('POST')
		->and($request['body']['shop']['consumer_key'])->toBe('ck_testhash')
		->and($keys)->toHaveCount(1)
		->and($keys[0]['consumer_key'])->toBe('hashed_ck_testhash')
		->and($keys[0]['permissions'])->toBe('read_write');
});

test('removes the previous business integration when the business ID changes', function () {
	do_action('update_option_' . Constants::OPTION_BUSINESS_ID, 'business_456', 'test_business_123', Constants::OPTION_BUSINESS_ID);
	do_action('shutdown');

	expect(array_map(fn ($request) => $request['args']['method'], $GLOBALS['test_http_requests']))->toBe(['DELETE', 'POST'])
		->and(tracked_request(0)['body']['shop']['business_id'])->toBe('business_456');
});

test('deactivation removes the integration and revokes its API key', function () {
	$GLOBALS['wpdb']->tables['wp_woocommerce_api_keys'][] = ['description' => 'Hellotext'];
	$GLOBALS['wpdb']->tables['wp_woocommerce_api_keys'][] = ['description' => 'Other app'];

	hellotext_deactivate();

	$request = tracked_request();

	expect($request['url'])->toBe('https://api.hellotext.com/integrations/woo')
		->and($request['args']['method'])->toBe('DELETE')
		->and($request['args']['headers']['Authorization'])->toBe('Bearer test_token_123')
		->and($request['body']['shop']['business_id'])->toBe('test_business_123')
		->and($GLOBALS['wpdb']->tables['wp_woocommerce_api_keys'])->toBe([['description' => 'Other app']]);
});

test('uninstall removes every option, including the old webchat settings', function () {
	$GLOBALS['test_options']['hellotext_webchat_id'] = 'webchat_1';

	hellotext_uninstall();

	expect($GLOBALS['test_options'])->toBe([]);
});
