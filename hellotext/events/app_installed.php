<?php

function hellotext_activate () {
	$hellotext_business_id = get_option('hellotext_business_id');
	if (!$hellotext_business_id) return;

	$hellotext = new Hellotext();
	$hellotext->track('app.installed', array());
}
