<?php

use Hellotext\Constants;

/**
 * Handle custom option updates for Hellotext.
 *
 * @param string $option Option name.
 * @param mixed $old_value Previous value.
 * @param mixed $new_value New value.
 * @return void
 */
function custom_field_updated(string $option, mixed $old_value, mixed $new_value): void {
	switch ($option) {
		case Constants::OPTION_BUSINESS_ID:
			do_action('hellotext_remove_integration', $old_value);
			do_action('hellotext_create_integration', $new_value);
			break;
	}
}

add_action('update_option', 'custom_field_updated', 10, 3);
