<?php

namespace Hellotext\Api;

use Hellotext\Constants;

/**
 * Event
 *
 * Tracks events by posting to the Hellotext API.
 *
 * @package Hellotext\Api
 */
class Event {
	/**
	 * Hellotext business ID token.
	 *
	 * @var string
	 */
	private string $hellotext_business_id;

	/**
	 * Session identifier.
	 *
	 * @var string|null
	 */
	private ?string $session;

	/**
	 * cURL handle.
	 *
	 * @var resource|\CurlHandle
	 */
	private $curl;

	/**
	 * Create a new event tracker.
	 *
	 * @param string|null $session Optional session identifier.
	 */
	public function __construct (?string $session = null) {
		$this->hellotext_business_id = get_option(Constants::OPTION_BUSINESS_ID);
		$this->session = $session;
		$this->curl = curl_init($this->get_api_url());
		$this->set_curl_options();
	}

	/**
	 * Track an event.
	 *
	 * @param string $action Event action name.
	 * @param array $payload Event payload data.
	 * @return void
	 */
	public function track (string $action, array $payload): void {
		$body = array_merge(
			array(
				'action' => $action,
				'session' => ( isset($this->session) && $this->session )
					? $this->session
					: $this->browser_session(),
			),
			$payload
		);

		curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($body));

		$result = curl_exec($this->curl);

		curl_close($this->curl);
	}

	/**
	 * Configure cURL options for the event request.
	 *
	 * @return void
	 */
	private function set_curl_options (): void {
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');

		// Headers
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $this->hellotext_business_id,
		));
	}

	/**
	 * Get the event tracking API URL.
	 *
	 * @return string
	 */
	private function get_api_url (): string {
		global $HELLOTEXT_API_URL;

		return $HELLOTEXT_API_URL . Constants::API_ENDPOINT_TRACK;
	}

	/**
	 * Retrieve session from browser cookie.
	 *
	 * @return string|null
	 */
	private function browser_session (): ?string {
		if (isset($_COOKIE[Constants::SESSION_COOKIE_NAME])) {
			return sanitize_text_field($_COOKIE[Constants::SESSION_COOKIE_NAME]);
		}

		return null;
	}
}
