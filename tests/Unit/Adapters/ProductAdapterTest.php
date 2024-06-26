<?php

use Hellotext\Adapters\ProductAdapter;

beforeEach(function () {
    $product = new WC_Product();
    $product->set_props([
        'name' => 'Sample Product',
        'price' => '10.00',
    ]);
    $product->save();
    $this->product = wc_get_product($product);
});

test('throws an exception when product is not found', function () {
    (new ProductAdapter(null))->get();
})->throws(\Exception::class, 'Product not found');

test('returns the correct structure', function () {
    $result = (new ProductAdapter($this->product))->get();

    expect($result)->toBeArray();
    expect($result)->toHaveKey('reference');
    expect($result)->toHaveKey('source');
    expect($result)->toHaveKey('type');
    expect($result)->toHaveKey('name');
    expect($result)->toHaveKey('price');
});

test('has the correct type', function () {
    $result = (new ProductAdapter($this->product))->get();

    expect($result['type'])->toBe('product');
});

test('has the correct source', function () {
    $result = (new ProductAdapter($this->product))->get();

    expect($result['source'])->toBe('woo');
});

test('finds the correct product if passed an ID', function () {
    $result = (new ProductAdapter($this->product->get_id()))->get();

    expect($result['name'])->toBe($this->product->get_name());
});

test('sets the quantity if item is passed', function () {
    $item = new WC_Order_Item_Product();
    $item->set_quantity(2);

    $result = (new ProductAdapter($this->product, $item))->get();

    expect($result['quantity'])->toBe(2);
});
