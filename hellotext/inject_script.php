<?php

add_action( 'wp_head', 'hellotext_script' );
function hellotext_script () {
    ?>
        <script type="module">
            import Hellotext from 'https://unpkg.com/@hellotext/hellotext@latest/src/index.js';

            window.Hellotext = Hellotext;
            window.Hellotext.initialize('<?= get_option( 'business_id' ) ?>');
        </script>
    <?php
}

add_action( 'wp_head', 'listeners_javascript' );
function listeners_javascript () {
    // up one level
    $path = plugin_dir_url( __FILE__ ) . '../javascript/listeners.js';

    ?>
        <script type="module" src="<?= $path ?>"></script>
    <?php
}

