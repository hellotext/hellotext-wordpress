<?php

namespace Hellotext\Adapters;

class PriceAdapter {
	public $price;
	public $currency;

	public function __construct ($price, $currency = null) {
		$this->price = $price;
		$this->currency = is_null($currency) ? get_woocommerce_currency() : $currency;
	}

	public function get () {
		return array(
			'amount' => $this->price,
			'currency' => $this->currency,
			'converted_amount' => $this->price,
			'converted_currency' => $this->currency,
		);
	}
}
