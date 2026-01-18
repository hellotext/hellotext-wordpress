<?php

namespace Hellotext\Adapters;

/**
 * PriceAdapter
 *
 * Normalizes price values to Hellotext payload structure.
 *
 * @package Hellotext\Adapters
 */
class PriceAdapter {
    /**
     * Price amount.
     *
     * @var float|string
     */
    public float|string $price;

    /**
     * Currency code.
     *
     * @var string
     */
    public string $currency;

    /**
     * Create a new adapter instance.
     *
     * @param float|string $price Raw price amount.
     * @param string|null $currency Currency code or null to use store default.
     */
    public function __construct(float|string $price, ?string $currency = null) {
        $this->price = $price;
        $this->currency = is_null($currency) ? get_woocommerce_currency() : $currency;
    }

    /**
     * Get the adapted price payload.
     *
     * @return array
     */
    public function get(): array {
        return [
            'amount' => $this->price,
            'currency' => $this->currency,
            'converted_amount' => $this->price,
            'converted_currency' => $this->currency,
        ];
    }
}
