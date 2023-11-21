<?php

add_action( 'woocommerce_after_order_details', 'hellotext_order_placed' );

function hellotext_order_placed ( $order ) {
    $products = [];

    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        $productAdapter = new ProductAdapter($product, $item);

        $products[] = $productAdapter->get();
    };

    $order = new OrderAdapter($order, $products);
    $order = $order->get();


    ?>
        <script type="module">
            const order_parameters = <?= wp_json_encode($order) ?>

            Hellotext.track('order.placed', { order_parameters })

            <?php foreach ($products as $product) { ?>
                var product_parameters = JSON.parse('<?= json_encode($product) ?>')

                Hellotext.track('product.purchased', { product_parameters })
            <?php } ?>
        </script>
    <?php
}

