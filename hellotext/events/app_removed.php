<?php

function hellotext_deactivate () {
	$hellotext_business_id = get_option('hellotext_business_id');
	if (!$hellotext_business_id) return;

	$hellotext = new Hellotext();
	$hellotext->track('app.removed', array());
}
