<?php

class Hellotext {
    // TODO: set to false before release
    private $DEV_MODE = true;
    private $DEV_URL = 'http://api.lvh.me:4000/v1/track/events';
    private $API_URL = 'https://api.hellotext.com/v1/track/events';

    public function __construct ($business_id = null) {
        if (! isset($business_id)) {
            $business_id = get_option('business_id');
        }

        $this->business_id = $business_id;
        $this->curl = curl_init($this->get_api_url());
        $this->set_curl_options();
    }

    public function track ($event, $payload) {
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode(
            array_merge(
                array('event' => $event),
                $payload
            )
        ));

        $result = curl_exec($this->curl);

        var_dump($result);
        die();

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
            'Authorization: Bearer ' . $this->business_id,
        ));
    }

    private function get_api_url () {
        if (isset($this->DEV_MODE)) {
            return $this->DEV_URL;
        }

        return $this->API_URL;
    }
}
