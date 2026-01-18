<?php

namespace Hellotext\Adapters;

use Hellotext\Adapters\PriceAdapter;

/**
 * RefundAdapter
 *
 * Transforms WooCommerce refunds into Hellotext payloads.
 *
 * @package Hellotext\Adapters
 */
class RefundAdapter {
	/**
	 * WooCommerce refund instance.
	 *
	 * @var \WC_Order_Refund
	 */
	public \WC_Order_Refund $refund;

	/**
	 * WooCommerce order instance.
	 *
	 * @var \WC_Order
	 */
	public \WC_Order $order;

	/**
	 * Create a new adapter instance.
	 *
	 * @param \WC_Order_Refund $refund Refund instance.
	 * @param \WC_Order $order Order instance.
	 */
	public function __construct (\WC_Order_Refund $refund, \WC_Order $order) {
		$this->refund = $refund;
		$this->order = $order;
	}

	/**
	 * Get the adapted refund payload.
	 *
	 * @return array
	 */
	public function get (): array {
		return array(
			'reference' => $this->refund->get_id(),
			'source' => 'woo',
			'amount' => $this->refund->get_amount(),
			'currency' => $this->refund->get_currency(),
			'total' => ( new PriceAdapter($this->order->get_total(), $this->order->get_currency()) )->get(),
		);
	}

}
