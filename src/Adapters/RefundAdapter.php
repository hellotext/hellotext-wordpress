<?php

namespace Hellotext\Adapters;

use Hellotext\Adapters\PriceAdapter;

class RefundAdapter {
	public \WC_Order_Refund $refund; // WooCommerce Refund
	public \WC_Order $order;

	public function __construct (\WC_Order_Refund $refund, \WC_Order $order) {
		$this->refund = $refund;
		$this->order = $order;
	}

	public function get (): array {
		if (!$this->refund) {
			throw new \Exception('Refund not found');
		}

		if (!$this->order) {
			throw new \Exception('Order not found');
		}

		return array(
			'reference' => $this->refund->get_id(),
			'source' => 'woo',
			'amount' => $this->refund->get_amount(),
			'currency' => $this->refund->get_currency(),
			'total' => ( new PriceAdapter($this->order->get_total(), $this->order->get_currency()) )->get(),
		);
	}

}
