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

