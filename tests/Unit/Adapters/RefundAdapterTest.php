<?php

use Hellotext\Adapters\RefundAdapter;
use Hellotext\Adapters\OrderAdapter;

use Hellotext\Services\Session;

beforeEach(function () {
	$user = TestHelper::find_or_create_user();
	$_COOKIE['hello_session'] = '123';

	$this->order = wc_create_order([
		'customer_id' => $user->ID
	]);

	add_post_meta($this->order->get_id(), 'hellotext_session', Session::encrypt('123'));

	$this->refund = wc_create_refund([
		'amount' => $this->order->get_total() / 2,
		'order_id' => $this->order->get_id(),
	]);
});

test('throws an exception when refund is not found', function () {
	(new RefundAdapter(null, null))->get();
})->throws(\Exception::class, 'Refund not found');

test('throws an exception when order is not found', function () {
	(new RefundAdapter($this->refund, null))->get();
})->throws(\Exception::class, 'Order not found');

test('returns the correct structure', function () {
	$result = (new RefundAdapter($this->refund, $this->order))->get();

	expect($result)->toBeArray();
	expect($result)->toHaveKey('reference');
	expect($result)->toHaveKey('amount');
	expect($result)->toHaveKey('amount');
});
