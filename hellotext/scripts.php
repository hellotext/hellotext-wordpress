<?php

add_action( 'admin_head', 'hellotext_script' );
add_action( 'wp_head',	  'hellotext_script' );
function hellotext_script () {
    ?>
        <script type="module">
            import Hellotext from 'https://unpkg.com/@hellotext/hellotext@latest/src/index.js';

            window.Hellotext = Hellotext;
            // TODO: remove this line before release
            window.Hellotext.__apiURL = 'http://api.lvh.me:4000/v1/';
            window.Hellotext.initialize('<?= get_option( 'hellotext_business_id' ) ?>');
        </script>
    <?php
}

