<?php

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
        $this->get_user();
        $this->create_hellotext_profile();
        $this->attach_profile_to_session();
    }

    private function get_user ()
    {
        $this->user = get_user_by('id', $this->user_id);
    }

    private function create_hellotext_profile ()
    {
        $response = HellotextClient::post('/profiles', array(
            'session' => $this->session,
            'first_name' => $this->user->nickname,
            'email' => $this->user->user_email,
            'lists' => array('WooCommerce'),
        ));

        add_user_meta( $this->user_id, 'hellotext_profile_id', $response['body']['id'] );
    }

    private function attach_profile_to_session ()
    {
        $profile_id = get_user_meta($this->user_id, 'hellotext_profile_id', true);
        $response = HellotextClient::patch("/sessions/{$this->session}", array(
            'session' => $this->session,
            'profile' => $profile_id,
        ));
    }
}
