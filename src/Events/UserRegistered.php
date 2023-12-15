<?php

use Hellotext\Services\CreateProfile;

add_action( 'user_register', 'hellotext_user_registered', 10, 1 );

function hellotext_user_registered ($user_id) {
	$service = new CreateProfile($user_id);
	$service->process();
}
