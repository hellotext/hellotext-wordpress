<?php

class ProductAdapter {
    public $product; // WooCommerce product
    public $item;    // Cart/Order item

    public function __construct ($product, $item = null) {
        $this->product = is_numeric($product) ? wc_get_product( $product ) : $product;
        $this->item = $item;
    }

    public function get () {
        $response = array(
            'reference' => $this->product->id,
            'type' => 'product',
            'name' => $this->product->name,
            'categories' => wp_get_post_terms( $this->product->id, 'product_cat', array( 'fields' => 'names' ) ),
            'currency' => get_woocommerce_currency(),
            'price' => $this->product->get_price(),
            'amount' => $this->product->get_price(),
            'tags' => wp_get_post_terms( $this->product->id, 'product_tag', array( 'fields' => 'names' ) ),
            'image_url' => wp_get_attachment_url( $this->product->get_image_id() ),
        );

        if (isset($this->item)) {
            $response['quantity'] = $this->item->get_quantity();
        }

        return $response;
    }

}
