<?php

namespace Hellotext\Services;

use Hellotext\Api\Client;

class CreateProfile {
	public $user;
	public $hellotext_profile_id;

	public $client;

	public $user_id;
	public $session;

	public function __construct($user_id) {
		$this->user_id = $user_id;
		$this->session = isset($_COOKIE['hello_session']) ? sanitize_text_field($_COOKIE['hello_session']) : null;
		$this->client = Client::class;
	}

	public function process () {
		if (! $this->user_id) {
			return;
		}

		if (! $this->verify_if_profile_exists()) {
			$this->get_user();
			$this->create_hellotext_profile();
		}

		if ($this->session_changed()) {
			$this->attach_profile_to_session();
		}
	}

	private function get_user () {
		$this->user = get_user_by('id', $this->user_id);

		if (!$this->user) {
			throw new \Exception("User with id {$this->user_id} not found");
		}
	}

	private function verify_if_profile_exists () {
		$hellotext_profile_id = get_user_meta($this->user_id, 'hellotext_profile_id', true);

		return null != $hellotext_profile_id && '' != $hellotext_profile_id;
	}

	private function session_changed () {
		return get_user_meta($this->user_id, 'hellotext_session', true) != $this->session;
	}

	private function create_hellotext_profile () {
		$response = $this->client::post('/profiles', array(
			'session' => $this->session,
			'reference' => $this->user->ID,
			'first_name' => $this->user->nickname,
			'email' => $this->user->user_email,
			'lists' => array('WooCommerce'),
		));

		add_user_meta( $this->user_id, 'hellotext_profile_id', $response['body']['id'] );
	}

	private function attach_profile_to_session () {
		$profile_id = get_user_meta($this->user_id, 'hellotext_profile_id', true);
		$response = $this->client::patch("/sessions/{$this->session}", array(
			'session' => $this->session,
			'profile' => $profile_id,
		));
	}
}

add_action('hellotext_create_profile', function ($user_id = null) {
	if (!is_user_logged_in() && !isset($user_id)) {
		return;
	}

	$user = ( null != $user_id )
		? get_user_by('id', $user_id)
		: wp_get_current_user();

	if (!$user) {
		return;
	}

	( new CreateProfile($user->ID) )->process();
}, 10, 1);
