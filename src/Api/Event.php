<?php

namespace Hellotext\Api;

class Event {
	public function __construct ($session = null) {
		$this->hellotext_business_id = get_option('hellotext_business_id');
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

		return $HELLOTEXT_API_URL . '/v1/track/events';
	}

	private function browser_session () {
		if (isset($_COOKIE['hello_session'])) {
			return sanitize_text_field($_COOKIE['hello_session']);
		}

		return null;
	}
}
