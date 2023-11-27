<?php

use Hellotext\Adapters\RefundAdapter;
use Hellotext\Adapters\OrderAdapter;

beforeEach(function () {
    $this->order = wc_create_order();
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
    expect($result)->toHaveKey('type');
    expect($result)->toHaveKey('amount');
    expect($result)->toHaveKey('amount');
    expect($result)->toHaveKey('refundable');
});

test('has the correct type', function () {
    $result = (new RefundAdapter($this->refund, $this->order))->get();

    expect($result['type'])->toBe('refund');
});

test('the refundable has the correct type', function () {
    $result = (new RefundAdapter($this->refund, $this->order))->get();

    expect($result['refundable']['type'])->toBe('order');
});

test('the refundable has the correct amount', function () {
    $result = (new RefundAdapter($this->refund, $this->order))->get();

    expect($result['refundable']['amount'])->toBe($this->order->get_total() / 2);
});
