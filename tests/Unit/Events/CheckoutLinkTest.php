<?php

beforeEach(function () {
	unset($GLOBALS['test_woocommerce'], $GLOBALS['test_unpurchasable'], $GLOBALS['test_product_type']);
	$_GET = [];
});

function cart_lines(): array
{
	return array_map(fn ($item) => [$item['product_id'], $item['quantity']], array_values(WC()->cart->get_cart()));
}

test('replaces the cart with the products of the link and opens the checkout with its other parameters', function () {
	WC()->cart->add_to_cart(99, 1);
	$_GET = ['hellotext_checkout' => '1', 'products' => '701:2,55:1', 'utm_source' => 'hellotext', 'utm_campaign' => 'spring sale'];

	expect(hellotext_fill_checkout_link_cart())->toBe('https://example.test/checkout/?utm_source=hellotext&utm_campaign=spring+sale')
		->and(cart_lines())->toBe([[701, 2], [55, 1]]);
});

test('adds up a product listed twice and orders one unit of a line without a quantity', function () {
	$_GET = ['hellotext_checkout' => '1', 'products' => '701:2,701,55'];

	hellotext_fill_checkout_link_cart();

	expect(cart_lines())->toBe([[701, 3], [55, 1]]);
});

test('opens the page of a product whose options the shopper has to pick', function () {
	$GLOBALS['test_unpurchasable'] = [701];
	$GLOBALS['test_product_type'] = 'variable';
	$_GET = ['hellotext_checkout' => '1', 'products' => '701:1,55:1'];

	expect(hellotext_fill_checkout_link_cart())->toBe('https://example.test/product/1')
		->and(cart_lines())->toBe([[55, 1]]);
});

test('opens the cart when no product could be added', function () {
	$GLOBALS['test_unpurchasable'] = [701];
	$_GET = ['hellotext_checkout' => '1', 'products' => '701:1'];

	expect(hellotext_fill_checkout_link_cart())->toBe('https://example.test/cart');
});

test('leaves the cart alone when the link lists no valid product', function (mixed $products) {
	WC()->cart->add_to_cart(99, 1);
	$_GET = ['hellotext_checkout' => '1', 'products' => $products];

	expect(hellotext_fill_checkout_link_cart())->toBe('https://example.test/cart')
		->and(cart_lines())->toBe([[99, 1]]);
})->with([
	'empty' => [''],
	'not IDs' => ['abc:2,:3'],
	'an array' => [['701:1']],
]);

test('does nothing on pages without a checkout link', function () {
	WC()->cart->add_to_cart(99, 1);

	hellotext_checkout_link();

	expect(cart_lines())->toBe([[99, 1]]);
});
