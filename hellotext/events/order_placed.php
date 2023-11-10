<?php

add_action( 'woocommerce_after_order_details', 'order_details_javascript' );
function order_details_javascript ( $order ) {
    $products = [];

    foreach ($order->get_items() as $item) {
        $product = $item->get_product();

        $products[] = [
            'reference' => $product->id,
            'type' => 'product',
            'name' => $product->name,
            'categories' => wp_get_post_terms( $product->id, 'product_cat', array( 'fields' => 'names' ) ),
            'currency' => get_woocommerce_currency(),
            'price' => $product->get_price(),
            'quantity' => $item->get_quantity(),
            'tags' => wp_get_post_terms( $product->id, 'product_tag', array( 'fields' => 'names' ) ),
            'image_url' => wp_get_attachment_url( $product->get_image_id() ),
        ];
    };

    ?>
        <script type="module">
            const order_parameters = {
                reference: '<?= $order->get_id() ?>',
                type: 'order',
                products: <?= wp_json_encode($products) ?>,
                amount: <?= $order->get_total() ?>,
                currency: '<?= $order->get_currency() ?>',
            }
            console.log(order_parameters)
            Hellotext.track('order.placed', { order_parameters })
        </script>
    <?php
}

