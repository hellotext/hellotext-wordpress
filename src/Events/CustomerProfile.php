<?php

use Hellotext\Services\CreateProfile;

/**
 * Handle Hellotext profile creation action.
 *
 * Only fire it from requests the customer makes: product pages, cart,
 * checkout and registration.
 *
 * @param mixed $customer Guest billing details, a user ID, or null for the current user.
 * @return void
 */
add_action('hellotext_create_profile', function (mixed $customer = null): void {
    if (is_array($customer)) {
        (new CreateProfile(null, $customer))->process();
        return;
    }

    $user_id = is_numeric($customer) && (int) $customer > 0 ? (int) $customer : get_current_user_id();

    if ($user_id > 0) {
        (new CreateProfile($user_id))->process();
    }
}, 10, 1);
