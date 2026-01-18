<?php

namespace Hellotext\Api;

use Hellotext\Constants;

/**
 * Client
 *
 * HTTP client for communicating with the Hellotext API.
 *
 * @package Hellotext\Api
 */
class Client {
    /**
     * API URL suffix.
     *
     * @var string
     */
    public static string $sufix = '/' . Constants::API_VERSION;

    /**
     * Set a custom API suffix and return a client instance.
     *
     * @param string $sufix Suffix to append to API URL.
     * @return self
     */
    public static function with_sufix(string $sufix = ''): self {
        if (0 < strlen($sufix) && '/' !== $sufix[0]) {
            $sufix = '/' . $sufix;
        }

        self::$sufix = $sufix;

        return new self();
    }

    /**
     * Make an HTTP request to the Hellotext API.
     *
     * @param string $method HTTP method.
     * @param string $path API endpoint path.
     * @param array $data Request payload.
     * @return array
     */
    public static function request(string $method = 'GET', string $path = '/', array $data = []): array {
        $request_url = self::get_api_url() . $path;
        $access_token = get_option(Constants::OPTION_ACCESS_TOKEN);

        $args = [
            'method'  => strtoupper($method),
            'timeout' => 15,
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
            ],
            'sslverify' => true,
        ];

        // Add body for non-GET requests
        if (!empty($data) && 'GET' !== $method) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($request_url, $args);

        // Handle errors
        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    '[Hellotext] API request failed: %s %s - Error: %s',
                    $method,
                    $request_url,
                    $response->get_error_message()
                ));
            }

            return [
                'request' => [
                    'method' => $method,
                    'path'   => $request_url,
                    'data'   => $data,
                ],
                'status'  => 0,
                'body'    => null,
                'error'   => $response->get_error_message(),
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body_raw = wp_remote_retrieve_body($response);

        // Log non-2xx responses
        if (defined('WP_DEBUG') && WP_DEBUG && ($status_code < 200 || $status_code >= 300)) {
            error_log(sprintf(
                '[Hellotext] API request returned %d: %s %s',
                $status_code,
                $method,
                $request_url
            ));
        }

        return [
            'request' => [
                'method' => $method,
                'path'   => $request_url,
                'data'   => $data,
            ],
            'status' => $status_code,
            'body'   => !empty($body_raw) ? json_decode($body_raw, true) : null,
            'error'  => null,
        ];
    }

    /**
     * Make a GET request.
     *
     * @param string $path API endpoint path.
     * @param array|null $data Request payload.
     * @return array
     */
    public static function get(string $path = '/', ?array $data = null): array {
        return self::request('GET', $path, $data ?? []);
    }

    /**
     * Make a POST request.
     *
     * @param string $path API endpoint path.
     * @param array|null $data Request payload.
     * @return array
     */
    public static function post(string $path = '/', ?array $data = null): array {
        return self::request('POST', $path, $data ?? []);
    }

    /**
     * Make a PATCH request.
     *
     * @param string $path API endpoint path.
     * @param array|null $data Request payload.
     * @return array
     */
    public static function patch(string $path = '/', ?array $data = null): array {
        return self::request('PATCH', $path, $data ?? []);
    }

    /**
     * Make a PUT request.
     *
     * @param string $path API endpoint path.
     * @param array|null $data Request payload.
     * @return array
     */
    public static function put(string $path = '/', ?array $data = null): array {
        return self::request('PUT', $path, $data ?? []);
    }

    /**
     * Make a DELETE request.
     *
     * @param string $path API endpoint path.
     * @param array|null $data Request payload.
     * @return array
     */
    public static function delete(string $path = '/', ?array $data = null): array {
        return self::request('DELETE', $path, $data ?? []);
    }

    /**
     * Get base API URL.
     *
     * @return string
     */
    private static function get_api_url(): string {
        global $HELLOTEXT_API_URL;

        return $HELLOTEXT_API_URL . self::$sufix;
    }

}
