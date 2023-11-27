<?php

use Hellotext\Adapters\OrderAdapter;
use Hellotext\Adapters\ProductAdapter;

beforeEach(function () {
    $this->order = wc_create_order();

    $product = new WC_Product();
    $product->set_props([
        'name' => 'Sample Product',
        'regular_price' => '10.00',
        'sale_price' => '8.00',
        'stock_quantity' => 100,
        'manage_stock' => true,
    ]);
    $product->save();
    $product = wc_get_product($product);

    $this->order->add_product($product, 1);
    $this->order->apply_changes();
    $this->order->save();
});

test('throws an exception when order is not found', function () {
    (new OrderAdapter(null))->get();
})->throws(\Exception::class, 'Order not found');

test('returns the correct structure', function () {
    $result = (new OrderAdapter($this->order))->get();

    expect($result)->toBeArray();
    expect($result)->toHaveKey('reference');
    expect($result)->toHaveKey('type');
    expect($result)->toHaveKey('products');
    expect($result)->toHaveKey('amount');
    expect($result)->toHaveKey('currency');
});

test('has the correct type', function () {
    $result = (new OrderAdapter($this->order))->get();

    expect($result['type'])->toBe('order');
});

test('finds the correct order if passed an ID', function () {
    $result = (new OrderAdapter($this->order->get_id()))->get();

    expect($result['reference'])->toBe($this->order->get_id());
});

test('has the products array', function () {
    $result = (new OrderAdapter($this->order))->get();

    expect($result['products'])->toBeArray();
});

test('has the correct amount of products', function () {
    $result = (new OrderAdapter($this->order))->get();

    expect($result['products'])->toHaveLength(1);
});

test('products have the correct type', function () {
    $result = (new OrderAdapter($this->order))->get();

    expect($result['products'][0]['type'])->toBe('product');
});

test('will return the products passed in the constructor', function () {
    $result = (new OrderAdapter($this->order, ['test']))->get();

    expect($result['products'][0])->toBe('test');
});

