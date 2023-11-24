<?php

namespace Hellotext\Adapters;

use Hellotext\Adapters\ProductAdapter;

class OrderAdapter {
    public $order;    // WooCommerce Order
    public $products; // Order items

    public function __construct ($order, $products = []) {
        $this->order = is_numeric($order) ? wc_get_order( $order ) : $order;
        $this->products = $products;
    }

    public function get () {
        if (is_null($this->order)) {
            return throw new \Exception('Order not found');
        }

        return array(
            'reference' => $this->order->get_id(),
            'type' => 'order',
            'products' => (isset($this->products) && sizeof($this->products) > 0)
                ? $this->products
                : $this->adapted_products(),
            'amount' => $this->order->get_total(),
            'currency' => $this->order->get_currency(),
        );
    }

    public function adapted_products () {
        return array_map(function ($item) {
            $product = $item->get_product();

            return (new ProductAdapter($product, $item))->get();
        }, $this->order->get_items());
    }

}
