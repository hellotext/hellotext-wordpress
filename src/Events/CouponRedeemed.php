<?php

use Hellotext\Api\Event;

add_action( 'woocommerce_applied_coupon', 'hellotext_coupon_redeemed', 10, 1 );

function hellotext_coupon_redeemed ($code) {
	do_action('hellotext_create_profile');

	$coupon = new \WC_Coupon($code);
	$discounts = new \WC_Discounts();

	$valid = $discounts->is_coupon_valid($coupon);


	if ($valid) {
		( new Event() )->track('coupon.redeemed', [
			'coupon_parameters' => [
				'type' => 'coupon',
				'reference' => $coupon->get_id(),
				'code' => $code,
				'description' => $coupon->get_description(),
				'destination_url' => site_url('/cart'),
			]
		]);
	}
}
