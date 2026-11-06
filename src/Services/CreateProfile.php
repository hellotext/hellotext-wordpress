<?php

namespace Hellotext\Services;

use Hellotext\Api\Client;
use Hellotext\Constants;

/**
 * CreateProfile
 *
 * Creates Hellotext profiles for customers and links them to the visitor's
 * Hellotext.js session.
 *
 * @package Hellotext\Services
 */
class CreateProfile {
    /**
     * WordPress user ID, null for guest checkouts.
     *
     * @var int|null
     */
    public ?int $user_id;

    /**
     * Guest billing details.
     *
     * @var array
     */
    public array $billing;

    /**
     * Session identifier.
     *
     * @var string|null
     */
    public ?string $session;

    /**
     * API client class name.
     *
     * @var string
     */
    public string $client = Client::class;

    /**
     * Create a new profile service instance.
     *
     * @param int|null $user_id WordPress user ID.
     * @param array $billing Guest billing details.
     */
    public function __construct(?int $user_id, array $billing = []) {
        $this->user_id = $user_id;
        $this->billing = $billing;
        $this->session = $this->visitor_is_customer() ? Session::current() : null;
    }

    /**
     * Process profile creation/association flow.
     *
     * @return void
     */
    public function process(): void {
        if (!$this->user_id) {
            if (!empty($this->billing)) {
                $this->attach_profile_to_session($this->create_hellotext_profile());
            }

            return;
        }

        if (!get_user_by('id', $this->user_id)) {
            return;
        }

        $profile_id = get_user_meta($this->user_id, Constants::META_PROFILE_ID, true);

        if (!$profile_id) {
            $profile_id = $this->create_hellotext_profile();

            if ($profile_id) {
                update_user_meta($this->user_id, Constants::META_PROFILE_ID, $profile_id);
            }
        }

        if ($profile_id && $this->session && get_user_meta($this->user_id, Constants::META_SESSION, true) !== $this->session) {
            $this->attach_profile_to_session($profile_id);
            update_user_meta($this->user_id, Constants::META_SESSION, $this->session);
        }
    }

    /**
     * Create a Hellotext profile.
     *
     * @return string|null The new profile ID.
     */
    public function create_hellotext_profile(): ?string {
        $response = $this->client::post(Constants::API_ENDPOINT_PROFILES, array_filter([
            'session' => $this->session,
            'reference' => $this->user_id,
            'first_name' => $this->attribute('first_name'),
            'last_name' => $this->attribute('last_name'),
            'email' => $this->attribute('email'),
            'phone' => $this->attribute('phone'),
            'lists' => ['WooCommerce'],
        ]));

        $profile_id = $response['body']['id'] ?? null;

        return is_scalar($profile_id) && '' !== (string) $profile_id ? (string) $profile_id : null;
    }

    /**
     * Read a customer attribute from the user account, falling back to
     * their billing details.
     *
     * @param string $name Attribute name.
     * @return string|null
     */
    private function attribute(string $name): ?string {
        if ($this->user_id) {
            $user = get_user_by('id', $this->user_id);
            $value = match ($name) {
                'first_name' => $user->first_name ?? '',
                'last_name' => $user->last_name ?? '',
                'email' => $user->user_email ?? '',
                default => '',
            };

            if ('' === $value) {
                $value = (string) get_user_meta($this->user_id, "billing_{$name}", true);
            }
        } else {
            $value = (string) ($this->billing[$name] ?? '');
        }

        return '' === $value ? null : $value;
    }

    /**
     * Attach profile ID to current session.
     *
     * @param string|null $profile_id Hellotext profile ID.
     * @return void
     */
    private function attach_profile_to_session(?string $profile_id): void {
        if (!$profile_id || !$this->session) {
            return;
        }

        $this->client::patch(Constants::API_ENDPOINT_SESSIONS . '/' . rawurlencode($this->session), [
            'session' => $this->session,
            'profile' => $profile_id,
        ]);
    }

    /**
     * Whether the browser making this request belongs to the customer.
     *
     * The cookie of a shop manager creating a user, or of anyone other than
     * the customer, must not be linked to the customer's profile.
     *
     * @return bool
     */
    private function visitor_is_customer(): bool {
        $current_user_id = get_current_user_id();

        return 0 === $current_user_id || $current_user_id === $this->user_id;
    }
}
