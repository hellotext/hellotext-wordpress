<?php

class OrderAdapter {
    public $order;    // WooCommerce Order
    public $products; // Order items

    public function __construct ($order, $products = []) {
        $this->order = is_numeric($order) ? wc_get_order( $order ) : $order;
        $this->products = $products;
    }

    public function get () {
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
        $products = [];

        foreach ($this->order->get_items() as $item) {
            $product = $item->get_product();
            $adapter = new ProductAdapter($product, $this->order->get_item($product->id));
            $products[] = $adapter->get();
        }

        return $products;
    }

}
