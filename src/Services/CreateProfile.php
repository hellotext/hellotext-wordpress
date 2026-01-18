<?php

namespace Hellotext\Services;

use Hellotext\Api\Client;
use Hellotext\Constants;

class CreateProfile {
	public \WP_User|false|null $user = null;
	public ?string $hellotext_profile_id = null;

	public string $client;
	public array $billing;

	public ?int $user_id;
	public ?string $session;

	public function __construct(?int $user_id, array $billing = []) {
		$this->user_id = $user_id;
		$this->session = isset($_COOKIE[Constants::SESSION_COOKIE_NAME])
			? sanitize_text_field($_COOKIE[Constants::SESSION_COOKIE_NAME])
			: null;
		$this->client = Client::class;
		$this->billing = $billing;
	}

	public function process (): void {
		if (isset($this->billing) && !empty($this->billing)) {
			$this->create_hellotext_profile();
			$this->attach_profile_to_session();
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

	private function get_user (): void {
		$this->user = get_user_by('id', $this->user_id);

		if (!$this->user) {
			throw new \Exception("User with id {$this->user_id} not found");
		}
	}

	private function verify_if_profile_exists (): bool {
		$hellotext_profile_id = get_user_meta($this->user_id ?? $this->session, Constants::META_PROFILE_ID, true);

		return false != $hellotext_profile_id && '' != $hellotext_profile_id;
	}

	private function session_changed (): bool {
		return get_user_meta($this->user_id, Constants::META_SESSION, true) != $this->session;
	}

	public function create_hellotext_profile (): void {
		$profile = get_user_meta($this->user_id ?? $this->session, Constants::META_PROFILE_ID, true);

		if ($profile) {
			if (isset($this->user)) {
				update_user_meta($this->user->ID, Constants::META_PROFILE_ID, $profile);
			}

            $this->client::patch(Constants::API_ENDPOINT_SESSIONS . "/{$this->session}", array(
               'session' => $this->session,
               'profile' => $profile,
            ));

			return;
		}

		$phone = get_user_meta($this->user_id, 'billing_phone', true);

		$response = $this->client::post(Constants::API_ENDPOINT_PROFILES, array_filter(array(
			'session' => $this->session,
			'reference' => isset($this->user) ? $this->user->ID : null,
			'first_name' => $this->user->nickname ?? $this->billing['first_name'],
			'last_name' => $this->user->last_name ?? $this->billing['last_name'],
			'email' => $this->user->user_email ?? $this->billing['email'],
			'phone' => empty($phone) ? $this->billing['phone'] : $phone,
			'lists' => array('WooCommerce'),
		)));

		add_user_meta( $this->user_id ?? $this->session, Constants::META_PROFILE_ID, $response['body']['id'], true );

		$this->client::patch(Constants::API_ENDPOINT_SESSIONS . "/{$this->session}", array(
           'session' => $this->session,
           'profile' => $response['body']['id'],
        ));
	}

	private function attach_profile_to_session (): void {
		$profile_id = get_user_meta($this->user_id ?? $this->session, Constants::META_PROFILE_ID, true);

		$response = $this->client::patch(Constants::API_ENDPOINT_SESSIONS . "/{$this->session}", array(
			'session' => $this->session,
			'profile' => $profile_id,
		));
	}
}

add_action('hellotext_create_profile', function (mixed $payload = null): void {
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
