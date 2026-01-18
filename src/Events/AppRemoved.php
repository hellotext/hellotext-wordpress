<?php

use Hellotext\Api\Client;
use Hellotext\Api\Event;
use Hellotext\Constants;

function hellotext_deactivate ($hellotext_business_id = null) {
     if (!$hellotext_business_id) {
         $hellotext_business_id = get_option(Constants::OPTION_BUSINESS_ID);
     }

     if (!$hellotext_business_id) {
         return;
     }

     do_action('hellotext_remove_integration', $hellotext_business_id);
 }

add_action('hellotext_remove_integration', function ($business_id) {
	Client::with_sufix()
		->delete(Constants::API_ENDPOINT_INTEGRATIONS_WOO, [
		'shop' => [
			'business_id' => $business_id,
			]
		]);
});
