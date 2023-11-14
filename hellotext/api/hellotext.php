<?php

class Hellotext {
	// private $DEV_MODE = true;
    private $DEV_URL = 'http://api.lvh.me:4000/v1/track/events';
    private $API_URL = 'https://api.hellotext.com/v1/track/events';

    public function __construct ($hellotext_business_id = null) {
        if (! isset($hellotext_business_id)) {
            $hellotext_business_id = get_option('hellotext_business_id');
        }

        $this->hellotext_business_id = $hellotext_business_id;
        $this->curl = curl_init($this->get_api_url());
        $this->set_curl_options();
    }

    public function track ($event, $payload) {
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode(
            array_merge(
				array(
					'event' => $event,
					'session' => $this->browser_session(),
				),
                $payload
            )
        ));

        $result = curl_exec($this->curl);

        if (curl_errno($this->curl)) {
            echo 'Hellotext API call error:' . curl_error($this->curl);
        } else {
            echo 'Hellotext API call success' . json_encode($result);
        }

        curl_close($this->curl);
    }

    private function set_curl_options () {
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "POST");

        // Headers
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->hellotext_business_id,
        ));
    }

    private function get_api_url () {
        if (isset($this->DEV_MODE) && $this->DEV_MODE) {
            return $this->DEV_URL;
        }

        return $this->API_URL;
    }

	private function browser_session () {
		if (isset($_COOKIE['hello_session'])) {
			return $_COOKIE['hello_session'];
		}

		return null;
	}
}
