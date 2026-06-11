<?php

use Hellotext\Constants;
use Hellotext\Services\Session;

beforeEach(function () {
	$GLOBALS['test_http_requests'] = [];
	$GLOBALS['test_post_meta'] = [];
	$GLOBALS['test_saved_orders'] = [];
	$GLOBALS['test_coupon_valid'] = true;
	$GLOBALS['test_is_user_logged_in'] = true;
	$GLOBALS['test_users'] = [];
	$GLOBALS['test_user_meta'] = [];
	$GLOBALS['wp'] = (object) ['request' => 'cart'];
	$_COOKIE = [];
	$_SESSION = [];

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
	$requests = $GLOBALS['test_http_requests'];

	return $requests[$index < 0 ? count($requests) + $index : $index]['body'];
}

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
		->and($request['url'])->toEndWith(Constants::API_ENDPOINT_TRACK)
		->and($request['args']['headers']['Content-Type'])->toBe('application/json')
		->and($request['args']['headers']['Authorization'])->toBe('Bearer test_business_123');
});

test('tracks valid coupon redemption only', function () {
	do_action('woocommerce_applied_coupon', 'SAVE10');

	$event = tracked_event();

	expect($event['action'])->toBe(Constants::EVENT_COUPON_REDEEMED)
		->and($event['object_parameters']['type'])->toBe('coupon')
		->and($event['object_parameters']['code'])->toBe('SAVE10')
		->and($event['object_parameters']['destination_url'])->toBe('https://example.test/cart');

	$GLOBALS['test_http_requests'] = [];
	$GLOBALS['test_coupon_valid'] = false;

	do_action('woocommerce_applied_coupon', 'EXPIRED');

	expect($GLOBALS['test_http_requests'])->toBe([]);
});

test('tracks cart added and removed events from cart hooks', function () {
	$_COOKIE[Constants::SESSION_COOKIE_NAME] = 'cart-session';
	$GLOBALS['test_is_user_logged_in'] = false;
	$_SESSION[Constants::SESSION_CART_ITEMS] = json_encode([]);
	WC()->cart->items = [
		'abc' => [
			'data' => new WC_Product(),
			'quantity' => 2,
		],
	];

	do_action('woocommerce_add_to_cart');

	$added = tracked_event();
	expect($added['action'])->toBe(Constants::EVENT_CART_ADDED)
		->and($added['session'])->toBe('cart-session')
		->and($added['amount'])->toBe(50)
		->and($added['currency'])->toBe('USD')
		->and($added['url'])->toBe('https://example.test/cart')
		->and($added['object_parameters']['items'][0]['quantity'])->toBe(2)
		->and($added['object_parameters']['items'][0]['product']['reference'])->toBe(1);

	$GLOBALS['test_http_requests'] = [];
	WC()->cart->items = [];

	do_action('woocommerce_cart_item_removed');

	$removed = tracked_event();
	expect($removed['action'])->toBe(Constants::EVENT_CART_REMOVED)
		->and($removed['session'])->toBe('cart-session')
		->and($removed['object_parameters']['items'][0]['quantity'])->toBe(2)
		->and($removed['object_parameters']['items'][0]['product']['reference'])->toBe(1);
});

test('tracks cart quantity increases and ignores unchanged carts', function () {
	$_COOKIE[Constants::SESSION_COOKIE_NAME] = 'cart-session';
	$GLOBALS['test_is_user_logged_in'] = false;
	$_SESSION[Constants::SESSION_CART_ITEMS] = json_encode([
		[
			'product' => [
				'reference' => 1,
				'source' => 'woo',
			],
			'quantity' => 1,
		],
	]);
	WC()->cart->items = [
		'abc' => [
			'data' => new WC_Product(),
			'quantity' => 3,
		],
	];

	do_action('woocommerce_after_cart_item_quantity_update');

	expect(tracked_event()['action'])->toBe(Constants::EVENT_CART_ADDED);

	$GLOBALS['test_http_requests'] = [];

	hellotext_cart_updated();

	expect($GLOBALS['test_http_requests'])->toBe([]);
});

test('tracks order placed and stores encrypted session with order CRUD', function () {
	$_COOKIE[Constants::SESSION_COOKIE_NAME] = 'checkout-session';
	$order = new WC_Order();

	hellotext_order_placed($order);

	$event = tracked_event();
	$encrypted_session = $order->get_meta(Constants::META_SESSION, true);

	expect($event['action'])->toBe(Constants::EVENT_ORDER_PLACED)
		->and($event['session'])->toBe('checkout-session')
		->and($event['object_parameters']['reference'])->toBe(1)
		->and(Session::decrypt($encrypted_session))->toBe('checkout-session')
		->and($GLOBALS['test_saved_orders'][1])->toBeTrue();
});

test('tracks supported order status transitions with stored session', function (string $status, string $action) {
	$order = new WC_Order();
	$order->update_meta_data(Constants::META_SESSION, Session::encrypt('stored-session'));

	do_action('woocommerce_order_status_changed', $order->get_id(), 'pending', $status, $order);

	$event = tracked_event();

	expect($event['action'])->toBe($action)
		->and($event['session'])->toBe('stored-session')
		->and($event['object_parameters']['reference'])->toBe(1);
})->with([
			'processing' => ['processing', Constants::EVENT_ORDER_CONFIRMED],
			'cancelled' => ['cancelled', Constants::EVENT_ORDER_CANCELLED],
			'completed' => ['completed', Constants::EVENT_ORDER_DELIVERED],
		]);

test('does not track unsupported order status transitions', function () {
	$order = new WC_Order();
	$order->update_meta_data(Constants::META_SESSION, Session::encrypt('stored-session'));

	do_action('woocommerce_order_status_changed', $order->get_id(), 'pending', 'on-hold', $order);

	expect($GLOBALS['test_http_requests'])->toBe([]);
});

test('tracks refunds with the order session', function () {
	$order = new WC_Order();
	$order->update_meta_data(Constants::META_SESSION, Session::encrypt('refund-session'));

	do_action('woocommerce_order_refunded', $order->get_id(), 1);

	$event = tracked_event();

	expect($event['action'])->toBe(Constants::EVENT_REFUND_RECEIVED)
		->and($event['session'])->toBe('refund-session')
		->and($event['object_parameters']['reference'])->toBe(1)
		->and($event['object_parameters']['source'])->toBe('woo');
});

test('creates a profile when a user registration hook fires', function () {
	$_COOKIE[Constants::SESSION_COOKIE_NAME] = 'registration-session';
	$user = new WP_User(42);
	$user->nickname = 'Jane';
	$user->last_name = 'Doe';
	$user->user_email = 'jane@example.test';
	$GLOBALS['test_users'][$user->ID] = $user;
	$GLOBALS['test_user_meta'][$user->ID]['billing_phone'] = '+15555550100';

	do_action('user_register', $user->ID);

	$profile_request = tracked_request(0);
	$session_request = tracked_request(1);

	expect($profile_request['url'])->toEndWith('/profiles')
		->and($profile_request['args']['method'])->toBe('POST')
		->and($profile_request['args']['headers']['Authorization'])->toBe('Bearer test_token_123')
		->and($profile_request['args']['headers']['Content-Type'])->toBe('application/json')
		->and($profile_request['args']['headers']['X-Plugin-Version'])->toBe('1.3.1')
		->and($profile_request['body']['session'])->toBe('registration-session')
		->and($profile_request['body']['reference'])->toBe(42)
		->and($profile_request['body']['email'])->toBe('jane@example.test')
		->and($GLOBALS['test_user_meta'][$user->ID][Constants::META_PROFILE_ID])->toBe('profile_123')
		->and($session_request['url'])->toEndWith('/sessions/registration-session')
		->and($session_request['args']['method'])->toBe('PATCH')
		->and($session_request['body']['profile'])->toBe('profile_123');
});

test('deactivation removes the Hellotext WooCommerce integration', function () {
	hellotext_deactivate('business_456');

	$request = tracked_request();

	expect($request['url'])->toEndWith('/integrations/woo')
		->and($request['args']['method'])->toBe('DELETE')
		->and($request['args']['headers']['Authorization'])->toBe('Bearer test_token_123')
		->and($request['args']['headers']['Content-Type'])->toBe('application/json')
		->and($request['args']['headers']['X-Plugin-Version'])->toBe('1.3.1')
		->and($request['body']['shop']['business_id'])->toBe('business_456');
});
