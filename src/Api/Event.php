<?php

namespace Hellotext\Api;

use Hellotext\Constants;

class Event {
	public function __construct ($session = null) {
		$this->hellotext_business_id = get_option(Constants::OPTION_BUSINESS_ID);
		$this->session = $session;
		$this->curl = curl_init($this->get_api_url());
		$this->set_curl_options();
	}

	public function track ($action, $payload) {
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

	private function set_curl_options () {
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');

		// Headers
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $this->hellotext_business_id,
		));
	}

	private function get_api_url () {
		global $HELLOTEXT_API_URL;

		return $HELLOTEXT_API_URL . Constants::API_ENDPOINT_TRACK;
	}

	private function browser_session () {
		if (isset($_COOKIE[Constants::SESSION_COOKIE_NAME])) {
			return sanitize_text_field($_COOKIE[Constants::SESSION_COOKIE_NAME]);
		}

		return null;
	}
}
