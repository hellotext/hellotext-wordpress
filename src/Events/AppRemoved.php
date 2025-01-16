<?php

use Hellotext\Api\Client;
use Hellotext\Api\Event;

function hellotext_deactivate ($hellotext_business_id = null) {
     if (!$hellotext_business_id) {
         $hellotext_business_id = get_option('hellotext_business_id');
     }

     if (!$hellotext_business_id) {
         return;
     }

     do_action('hellotext_remove_integration', $hellotext_business_id);
 }

add_action('hellotext_remove_integration', function ($business_id) {
	Client::with_sufix()
		->delete('/integrations/woo', [
		'shop' => [
			'business_id' => $business_id,
			]
		]);
});
