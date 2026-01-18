<?php

use Hellotext\Constants;

function custom_field_updated($option, $old_value, $new_value) {
	switch ($option) {
		case Constants::OPTION_BUSINESS_ID:
			do_action('hellotext_remove_integration', $old_value);
			do_action('hellotext_create_integration', $new_value);
			break;
	}
}

add_action('update_option', 'custom_field_updated', 10, 3);
