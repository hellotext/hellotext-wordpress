<?php

add_action( 'admin_head', 'hellotext_script' );
add_action( 'wp_head', 'hellotext_script' );

function hellotext_script () {
	global $HELLOTEXT_API_URL;

	?>
		<script type="module">
			import Hellotext from 'https://unpkg.com/@hellotext/hellotext@latest/src/index.js';

			window.Hellotext = Hellotext;

			window.Hellotext.__apiURL = <?php echo $HELLOTEXT_API_URL . '/v1' ?>;

			window.Hellotext.initialize('<?php echo esc_html(get_option('hellotext_business_id')); ?>');
		</script>
	<?php
}

