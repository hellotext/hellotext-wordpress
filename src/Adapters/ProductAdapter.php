<?php

namespace Hellotext\Adapters;

use Hellotext\Adapters\PriceAdapter;

/**
 * ProductAdapter
 *
 * Transforms WooCommerce products into Hellotext payloads.
 *
 * @package Hellotext\Adapters
 */
class ProductAdapter {
	/**
	 * WooCommerce product instance.
	 *
	 * @var \WC_Product|false
	 */
	public \WC_Product|false $product;

	/**
	 * Create a new adapter instance.
	 *
	 * @param int|\WC_Product $product Product ID or product instance.
	 */
	public function __construct (int|\WC_Product $product) {
		$this->product = is_numeric($product) ? wc_get_product( $product ) : $product;
	}

	/**
	 * Get the adapted product payload.
	 *
	 * @return array
	 * @throws \Exception When product is not found.
	 */
	public function get (): array {
		if (!$this->product) {
			throw new \Exception('Product not found');
		}

		$response = array(
			'reference' => $this->product->get_id(),
			'source' => 'woo',
			'name' => $this->product->get_name(),
			'categories' => wp_get_post_terms( $this->product->get_id(), 'product_cat', array( 'fields' => 'names' ) ),
			'price' => ( new PriceAdapter($this->product->get_price()) )->get(),
			'tags' => wp_get_post_terms( $this->product->get_id(), 'product_tag', array( 'fields' => 'names' ) ),
			'image_url' => wp_get_attachment_url( $this->product->get_image_id() ),
			'url' => get_permalink( $this->product->get_id() ),
		);

		$response = array_filter($response, function ($value) {
			return null != $value && [] != $value;
		});

		return $response;
	}

}
