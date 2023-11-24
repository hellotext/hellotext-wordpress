<?php

use Hellotext\Adapters\OrderAdapter;

test('throws an exception when order is not found', function () {
    (new OrderAdapter(null))->get();
})->throws(\Exception::class, 'Order not found');

test('returns an order', function () {
    $order = wc_create_order();

    $adapter = new OrderAdapter($order);
    $result = $adapter->get();

    expect($result)->toBeArray();
    expect($result)->toHaveKey('reference');
    expect($result)->toHaveKey('type');
    expect($result)->toHaveKey('products');
    expect($result)->toHaveKey('amount');
    expect($result)->toHaveKey('currency');
    expect($result['products'])->toBeArray();
    expect($result['currency'])->toBe('MXN');
});

