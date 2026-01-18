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
	 * Create a new event tracker.
	 *
	 * @param string|null $session Optional session identifier.
	 */
	public function __construct (?string $session = null) {
		$this->hellotext_business_id = get_option(Constants::OPTION_BUSINESS_ID);
		$this->session = $session;
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
				'action'  => $action,
				'session' => $this->session ?? $this->browser_session(),
			),
			$payload
		);

		$response = wp_remote_post(
			$this->get_api_url(),
			array(
				'timeout'   => 10,
				'blocking'  => false,
				'headers'   => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->hellotext_business_id,
				),
				'body'      => json_encode($body),
				'sslverify' => true,
			)
		);

		// Only log if there's an error and debug is enabled
		if (is_wp_error($response) && defined('WP_DEBUG') && WP_DEBUG) {
			error_log(sprintf(
				'[Hellotext] Event tracking failed for action "%s": %s',
				$action,
				$response->get_error_message()
			));
		}
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
