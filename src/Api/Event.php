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
     * Storefront events pass Session::current(); order events pass the session
     * stored on the order, since they also run from wp-admin and payment
     * callbacks where the browser cookie belongs to someone else.
     *
     * @param string|null $session Session identifier.
     */
    public function __construct(?string $session = null) {
        $this->hellotext_business_id = (string) get_option(Constants::OPTION_BUSINESS_ID);
        $this->session = $session;
    }

    /**
     * Track an event.
     *
     * @param string $action Event action name.
     * @param array $payload Event payload data.
     * @return void
     */
    public function track(string $action, array $payload): void {
        if (!$this->hellotext_business_id) {
            return;
        }

        $body = array_merge(
            [
                'action' => $action,
                'session' => $this->session,
            ],
            $payload
        );

        $response = wp_remote_post(
            Client::api_url() . Constants::API_ENDPOINT_TRACK,
            [
                'timeout' => 10,
                'blocking' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->hellotext_business_id,
                ],
                'body' => wp_json_encode($body),
                'sslverify' => true,
            ]
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
}
