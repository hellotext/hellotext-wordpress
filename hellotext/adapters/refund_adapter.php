<?php

class RefundAdapter {
    public $refund; // WooCommerce Refund

    public function __construct ($refund, $order) {
        $this->refund = $refund;
        $this->order = $order;
    }

    public function get () {
        return array(
            'reference' => $this->refund->get_id(),
            'type' => 'refund',
            'amount' => $this->refund->get_amount(),
            'currency' => $this->refund->get_currency(),
            'refundable' => [
                'type' => 'order',
                'amount' => $this->order->get_total(),
                'currency' => $this->order->get_currency(),
            ]
        );
    }

}
