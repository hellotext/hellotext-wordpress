<?php

add_action( 'admin_head', 'hellotext_script' );
add_action( 'wp_head', 'hellotext_script' );

function hellotext_script () {
	global $HELLOTEXT_API_URL;

	?>
		<script type="module">
            import 'https://unpkg.com/@hellotext/hellotext@latest/dist/hellotext.js';
            Hellotext.initialize('<?php echo esc_html(get_option('hellotext_business_id')); ?>');
		</script>
	<?php
}
