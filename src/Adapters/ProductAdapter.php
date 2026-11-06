<?php

namespace Hellotext\Adapters;

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
    public function __construct(int|\WC_Product $product) {
        $this->product = is_numeric($product) ? wc_get_product($product) : $product;
    }

    /**
     * Get the adapted product payload.
     *
     * @return array
     * @throws \Exception When product is not found.
     */
    public function get(): array {
        if (!$this->product) {
            throw new \Exception('Product not found');
        }

        // Variations carry no categories or tags of their own.
        $catalog_id = $this->product->get_parent_id() ?: $this->product->get_id();

        $response = [
            'reference' => $this->product->get_id(),
            'source' => 'woo',
            'name' => $this->product->get_name(),
            'categories' => $this->term_names($catalog_id, 'product_cat'),
            'price' => (new PriceAdapter($this->product->get_price()))->get(),
            'tags' => $this->term_names($catalog_id, 'product_tag'),
            'image_url' => wp_get_attachment_url($this->product->get_image_id()),
            'url' => $this->product->get_permalink(),
        ];

        $response = array_filter($response, function ($value) {
            return null != $value && [] != $value;
        });

        return $response;
    }

    /**
     * Term names of a product, or an empty list when the taxonomy lookup fails.
     *
     * @param int $product_id Product ID.
     * @param string $taxonomy Taxonomy name.
     * @return array
     */
    private function term_names(int $product_id, string $taxonomy): array {
        $terms = wp_get_post_terms($product_id, $taxonomy, ['fields' => 'names']);

        return is_array($terms) ? $terms : [];
    }
}
