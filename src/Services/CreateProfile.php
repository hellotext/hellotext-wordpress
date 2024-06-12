<?php

namespace Hellotext\Services;

use Hellotext\Api\Client;

class CreateProfile {
	public $user;
	public $hellotext_profile_id;

	public $client;
	public $billing;

	public $user_id;
	public $session;

	public function __construct($user_id, $billing = []) {
		$this->user_id = $user_id;
		$this->session = isset($_COOKIE['hello_session']) ? sanitize_text_field($_COOKIE['hello_session']) : null;
		$this->client = Client::class;
		$this->billing = $billing;
	}

	public function process () {
		if (isset($this->billing) && !empty($this->billing)) {
			$this->create_hellotext_profile();
			return;
		}

		if (! $this->user_id) {
			return;
		}

		if (! $this->verify_if_profile_exists()) {
			$this->get_user();
			$this->create_hellotext_profile();
			$this->attach_profile_to_session();
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

		return false != $hellotext_profile_id && '' != $hellotext_profile_id;
	}

	private function session_changed () {
		return get_user_meta($this->user_id, 'hellotext_session', true) != $this->session;
	}

	private function create_hellotext_profile () {
		$phone = get_user_meta($this->user_id, 'billing_phone', true);

		$response = $this->client::post('/profiles', array_filter(array(
			'session' => $this->session,
			'reference' => isset($this->user) ? $this->user->ID : null,
			'first_name' => $this->user->nickname ?? $this->billing['first_name'],
			'last_name' => $this->user->last_name ?? $this->billing['last_name'],
			'email' => $this->user->user_email ?? $this->billing['email'],
			'phone' => $phone ?? $this->billing['phone'],
			'lists' => array('WooCommerce'),
		)));

		var_dump(json_encode($response));

		add_user_meta( $this->user_id, 'hellotext_profile_id', $response['body']['id'], true );
	}

	private function attach_profile_to_session () {
		$profile_id = get_user_meta($this->user_id, 'hellotext_profile_id', true);

		$response = $this->client::patch("/sessions/{$this->session}", array(
			'session' => $this->session,
			'profile' => $profile_id,
		));
	}
}

add_action('hellotext_create_profile', function ($payload = null) {
	if (is_array($payload)) {
		( new CreateProfile(null, $payload) )->process();
	}

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
