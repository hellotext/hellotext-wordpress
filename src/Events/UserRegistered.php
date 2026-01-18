<?php

use Hellotext\Services\CreateProfile;

add_action( 'user_register', 'hellotext_user_registered', 10, 1 );

/**
 * Create a Hellotext profile when a user registers.
 *
 * @param int $user_id WordPress user ID.
 * @return void
 */
function hellotext_user_registered (int $user_id): void {
	$service = new CreateProfile($user_id);
	$service->process();
}
