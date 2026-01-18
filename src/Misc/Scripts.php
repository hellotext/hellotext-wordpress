<?php

add_action( 'admin_head', 'hellotext_script' );
add_action( 'wp_head', 'hellotext_script' );

/**
 * Output Hellotext embed script in the page head.
 *
 * @return void
 */
function hellotext_script (): void {
	global $HELLOTEXT_API_URL;

    $business_id = get_option('hellotext_business_id');
    $webchat_id = get_option('hellotext_webchat_id');
    $placement = get_option('hellotext_webchat_placement', 'bottom-right');
    $behaviour = get_option('hellotext_webchat_behaviour', 'popover');

    ?>
        <script type="module">
            import 'https://unpkg.com/@hellotext/hellotext@latest/dist/hellotext.js';

            let config = {};

            <?php if ($webchat_id) : ?>
                config.webChat = {
                    id: '<?php echo esc_html($webchat_id); ?>',
                    placement: '<?php echo esc_html($placement); ?>',
                    behaviour: '<?php echo esc_html($behaviour); ?>',
                };
            <?php endif; ?>

            Hellotext.initialize('<?php echo esc_html($business_id); ?>', config);
        </script>
    <?php
}
