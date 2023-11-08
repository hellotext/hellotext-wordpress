<?php

add_action( 'wp_head', 'hellotext_script' );
function hellotext_script () {
    ?>
        <script type="module">
            import Hellotext from 'https://unpkg.com/@hellotext/hellotext@latest/src/index.js';

            window.Hellotext = Hellotext;
            // TODO: remove this line before release
            window.Hellotext.__apiURL = 'http://api.lvh.me:4000/v1/'
            window.Hellotext.initialize('<?= get_option( 'business_id' ) ?>');
        </script>
    <?php
}

add_action( 'wp_head', 'listeners_javascript' );
function listeners_javascript () {
    $path = plugin_dir_url( __FILE__ ) . '../javascript/listeners.js';

    ?>
        <script type="module" src="<?= $path ?>"></script>
    <?php
}


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
            Hellotext.track('order.placed', { order_parameters })
        </script>
    <?php
}
