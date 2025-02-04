<?php

namespace Hellotext\Adapters;

use Hellotext\Adapters\ProductAdapter;
use Hellotext\Adapters\PriceAdapter;

class OrderAdapter {
	public $order;    // WooCommerce Order
	public $products; // Order items

	public function __construct ($order, $products = []) {
		$this->order = is_numeric($order) ? wc_get_order( $order ) : $order;
		$this->products = $products;
	}

	public function get () {
		if (!$this->order) {
			throw new \Exception('Order not found');
		}

		return array(
            'source' => 'woo',
            'delivery' => 'deliver',
			'reference' => $this->order->get_id(),
			'items' => $this->adapted_products(),
			'total' => ( new PriceAdapter($this->order->get_total(), $this->order->get_currency()) )->get(),
		);
	}

	public function adapted_products () {
		$items = $this->order->get_items();

		foreach ($items as $item) {
			$product = $item->get_product();

            $this->products[] = [
                'product' => ( new ProductAdapter($product) )->get(),
                'quantity' => $item->get_quantity(),
            ];
		}

		return $this->products;
	}

}
