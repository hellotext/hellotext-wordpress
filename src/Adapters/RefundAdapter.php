<?php

namespace Hellotext\Adapters;

use Hellotext\Adapters\PriceAdapter;

class RefundAdapter {
	public $refund; // WooCommerce Refund

	public function __construct ($refund, $order) {
		$this->refund = $refund;
		$this->order = $order;
	}

	public function get () {
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
