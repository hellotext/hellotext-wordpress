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
		if (is_null($this->order)) { return; }

		return array(
			'reference' => $this->order->get_id(),
			'type' => 'order',
			'products' => ( isset($this->products) && 0 < count($this->products) )
				? $this->products
				: $this->adapted_products(),
			'amount' => $this->order->get_total(),
			'currency' => $this->order->get_currency(),
		);
	}

	public function adapted_products () {
		$items = $this->order->get_items();

		foreach ($items as $item) {
			$product = $item->get_product();

			$this->products[] = ( new ProductAdapter($product) )->get();
		}

		return $this->products;
	}

}
