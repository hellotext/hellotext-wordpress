<?php

namespace Hellotext\Services;

use Hellotext\Api\Client;

class CreateProfile
{
    public $user;
    public $hellotext_profile_id;

    public $user_id;
    public function __construct($user_id)
    {
        $this->user_id = $user_id;
        $this->session = $_COOKIE['hello_session'];
    }

    public function process ()
    {
        $exists = $this->verify_if_profile_exists();

        if ($exists) return false;

        $this->get_user();
        $this->create_hellotext_profile();
        $this->attach_profile_to_session();
    }

    private function get_user ()
    {
        $this->user = get_user_by('id', $this->user_id);
    }

    private function verify_if_profile_exists ()
    {
        if (get_user_meta($this->user_id, 'hellotext_profile_id', true)) {
            return true;
        }
    }

    private function create_hellotext_profile ()
    {
        $response = Client::post('/profiles', array(
            'session' => $this->session,
            'reference' => $this->user->ID,
            'first_name' => $this->user->nickname,
            'email' => $this->user->user_email,
            'lists' => array('WooCommerce'),
        ));

        add_user_meta( $this->user_id, 'hellotext_profile_id', $response['body']['id'] );
    }

    private function attach_profile_to_session ()
    {
        $profile_id = get_user_meta($this->user_id, 'hellotext_profile_id', true);
        $response = Client::patch("/sessions/{$this->session}", array(
            'session' => $this->session,
            'profile' => $profile_id,
        ));
    }
}

add_action('hellotext_create_profile', function ($user_id = null) {
    if (!is_user_logged_in() && !isset($user_id)) return;

    $user = isset($user_id) ? get_user_by('id', $user_id) : wp_get_current_user();

    if (get_user_meta($user->ID, 'hellotext_profile_id', true) == null) {
        (new CreateProfile($user->ID))->process();
    }
}, 10, 1);
