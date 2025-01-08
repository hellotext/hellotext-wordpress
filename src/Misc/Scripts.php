<?php

add_action( 'admin_head', 'hellotext_script' );
add_action( 'wp_head', 'hellotext_script' );

function hellotext_script () {
	global $HELLOTEXT_API_URL;

    $business_id = get_option('hellotext_business_id');
    $webchat_id = get_option('hellotext_webchat_id');

    ?>
        <script type="module">
            import 'https://unpkg.com/@hellotext/hellotext@latest/dist/hellotext.js';

            let config = {};

            <?php if ($webchat_id) : ?>
                config.webChat = {
                    id: '<?php echo esc_html($webchat_id); ?>'
                };
            <?php endif; ?>

            Hellotext.initialize('<?php echo esc_html($business_id); ?>', config);
        </script>
    <?php
}
