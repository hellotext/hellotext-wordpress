<?php

namespace Hellotext\Adapters;

use Hellotext\Adapters\PriceAdapter;

class ProductAdapter {
	public $product; // WooCommerce product
	public $item;    // Cart/Order item

	public function __construct ($product, $item = null) {
		$this->product = is_numeric($product) ? wc_get_product( $product ) : $product;
		$this->item = $item;
	}

	public function get () {
		if (!$this->product) {
			throw new \Exception('Product not found');
		}

		$response = array(
			'reference' => $this->product->get_id(),
			'source' => 'woo',
			'type' => 'product',
			'name' => $this->product->get_name(),
			'categories' => wp_get_post_terms( $this->product->get_id(), 'product_cat', array( 'fields' => 'names' ) ),
			'price' => ( new PriceAdapter($this->product->get_price()) )->get(),
			'tags' => wp_get_post_terms( $this->product->get_id(), 'product_tag', array( 'fields' => 'names' ) ),
			'image_url' => wp_get_attachment_url( $this->product->get_image_id() ),
			'url' => get_permalink( $this->product->get_id() ),
		);

		if (isset($this->item)) {
			$response['quantity'] = is_array($this->item)
				? $this->item['quantity']
				: $this->item->get_quantity();
		}

		$response = array_filter($response, function ($value) {
			return null != $value && [] != $value;
		});

		return $response;
	}

}
