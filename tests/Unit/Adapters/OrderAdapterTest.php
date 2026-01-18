<?php

use Hellotext\Adapters\OrderAdapter;
use Hellotext\Adapters\ProductAdapter;

beforeEach(function () {
	$this->order = wc_create_order();

	$product = new WC_Product();
	$product->set_props([
		'name' => 'Sample Product',
		'price' => '10.00',
	]);
	$product->save();
	$product = wc_get_product($product);

	$this->order->add_product($product, 1);
	$this->order->apply_changes();
	$this->order->save();
});

test('throws an exception when order is not found', function () {
	(new OrderAdapter(0))->get();
})->throws(\Exception::class, 'Order not found');

test('returns the correct structure', function () {
	$result = (new OrderAdapter($this->order))->get();

	expect($result)->toBeArray();
	expect($result)->toHaveKey('reference');
	expect($result)->toHaveKey('items');
	expect($result)->toHaveKey('total');
	expect($result['source'])->toBe('woo');
});

test('finds the correct order if passed an ID', function () {
	$result = (new OrderAdapter($this->order->get_id()))->get();
	expect($result['reference'])->toBe($this->order->get_id());
});

test('has the products array as items', function () {
	$result = (new OrderAdapter($this->order))->get();
	expect($result['items'])->toBeArray();
});

test('has the correct amount of products', function () {
	$result = (new OrderAdapter($this->order))->get();
	expect($result['items'])->toHaveLength(1);
});

test('products have the correct type', function () {
	$result = (new OrderAdapter($this->order))->get();
	expect($result['items'][0])->toHaveKey('product');
});

test('will return the products passed in the constructor', function () {
	$result = (new OrderAdapter($this->order, ['test']))->get();
	expect($result['items'][0])->toBe('test');
});
