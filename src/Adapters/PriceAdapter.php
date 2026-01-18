<?php

namespace Hellotext\Adapters;

class PriceAdapter {
	public float|string $price;
	public string $currency;

	public function __construct (float|string $price, ?string $currency = null) {
		$this->price = $price;
		$this->currency = is_null($currency) ? get_woocommerce_currency() : $currency;
	}

	public function get (): array {
		return array(
			'amount' => $this->price,
			'currency' => $this->currency,
			'converted_amount' => $this->price,
			'converted_currency' => $this->currency,
		);
	}
}
