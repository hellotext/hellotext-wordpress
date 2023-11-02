<?php

add_action( 'admin_menu', 'hellotext_admin_menu' );
function hellotext_admin_menu()  {
    add_menu_page(
        'Hellotext',// page title
        'Hellotext', // menu title
        'manage_options',// capability
        'hellotext',// menu slug
    );
}
