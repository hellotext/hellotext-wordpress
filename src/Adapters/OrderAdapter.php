<?php

namespace Hellotext\Adapters;

use Hellotext\Adapters\ProductAdapter;
use Hellotext\Adapters\PriceAdapter;

/**
 * OrderAdapter
 *
 * Transforms WooCommerce orders into Hellotext payloads.
 *
 * @package Hellotext\Adapters
 */
class OrderAdapter {
	/**
	 * WooCommerce order instance.
	 *
	 * @var \WC_Order|false
	 */
	public \WC_Order|false $order;

	/**
	 * Adapted order items.
	 *
	 * @var array
	 */
	public array $products;

	/**
	 * Create a new adapter instance.
	 *
	 * @param int|\WC_Order $order Order ID or order instance.
	 * @param array $products Pre-adapted products list.
	 */
	public function __construct (int|\WC_Order $order, array $products = []) {
		$this->order = is_numeric($order) ? wc_get_order( $order ) : $order;
		$this->products = $products;
	}

	/**
	 * Get the adapted order payload.
	 *
	 * @return array
	 * @throws \Exception When order is not found.
	 */
	public function get (): array {
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

	/**
	 * Adapt order items to product payloads.
	 *
	 * @return array
	 */
	public function adapted_products (): array {
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
