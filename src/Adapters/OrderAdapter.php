<?php

namespace Hellotext\Adapters;

/**
 * OrderAdapter
 *
 * Transforms WooCommerce orders into Hellotext payloads.
 *
 * @package Hellotext\Adapters
 */
class OrderAdapter {
    /**
     * WooCommerce order instance.
     *
     * @var \WC_Order|false
     */
    public $order;

    /**
     * Adapted order items.
     *
     * @var array
     */
    public array $products;

    /**
     * Create a new adapter instance.
     *
     * @param int|\WC_Order $order Order ID or order instance.
     * @param array $products Pre-adapted products list.
     */
    public function __construct($order, array $products = []) {
        $this->order = is_numeric($order) ? wc_get_order($order) : $order;
        $this->products = $products;
    }

    /**
     * Get the adapted order payload.
     *
     * @return array
     * @throws \Exception When order is not found.
     */
    public function get(): array {
        if (!$this->order) {
            throw new \Exception('Order not found');
        }

        return [
            'source' => 'woo',
            'delivery' => 'deliver',
            'reference' => $this->order->get_id(),
            'items' => $this->adapted_products(),
            'total' => (new PriceAdapter($this->order->get_total(), $this->order->get_currency()))->get(),
        ];
    }

    /**
     * Adapt order items to product payloads.
     *
     * @return array
     */
    public function adapted_products(): array {
        $products = $this->products;

        foreach ($this->order->get_items() as $item) {
            $product = $item->get_product();

            $products[] = [
                'product' => $product ? (new ProductAdapter($product))->get() : $this->deleted_product($item),
                'quantity' => $item->get_quantity(),
            ];
        }

        return $products;
    }

    /**
     * Describe an order item whose product was deleted from the store.
     *
     * @param \WC_Order_Item_Product $item Order item.
     * @return array
     */
    private function deleted_product(\WC_Order_Item_Product $item): array {
        return [
            'reference' => $item->get_variation_id() ?: $item->get_product_id(),
            'source' => 'woo',
            'name' => $item->get_name(),
        ];
    }

}
