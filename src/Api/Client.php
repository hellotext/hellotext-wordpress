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
     * Make an HTTP request to the Hellotext API.
     *
     * @param string $method HTTP method.
     * @param string $path API endpoint path.
     * @param array $data Request payload.
     * @return array
     */
    public static function request(string $method = 'GET', string $path = '/', array $data = []): array {
        $request_url = self::api_url() . $path;
        $access_token = get_option(Constants::OPTION_ACCESS_TOKEN);

        $args = [
            'method' => strtoupper($method),
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
                'X-Plugin-Version' => self::get_plugin_version(),
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
                wc_get_logger()->error(sprintf(
                    'API request failed: %s %s - Error: %s',
                    $method,
                    $request_url,
                    $response->get_error_message()
                ), ['source' => 'hellotext']);
            }

            return [
                'request' => [
                    'method' => $method,
                    'path' => $request_url,
                    'data' => $data,
                ],
                'status' => 0,
                'body' => null,
                'error' => $response->get_error_message(),
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body_raw = wp_remote_retrieve_body($response);

        // Log non-2xx responses
        if (defined('WP_DEBUG') && WP_DEBUG && ($status_code < 200 || $status_code >= 300)) {
            wc_get_logger()->error(sprintf(
                'API request returned %d: %s %s',
                $status_code,
                $method,
                $request_url
            ), ['source' => 'hellotext']);
        }

        return [
            'request' => [
                'method' => $method,
                'path' => $request_url,
                'data' => $data,
            ],
            'status' => $status_code,
            'body' => !empty($body_raw) ? json_decode($body_raw, true) : null,
            'error' => null,
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
     * Define HELLOTEXT_API_URL in wp-config.php to point the plugin at another
     * Hellotext environment.
     *
     * @return string
     */
    public static function api_url(): string {
        return defined('HELLOTEXT_API_URL') ? rtrim(HELLOTEXT_API_URL, '/') : Constants::API_URL;
    }

    /**
     * Get plugin version.
     *
     * @return string
     */
    private static function get_plugin_version(): string {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugin_file = dirname(__DIR__, 2) . '/hellotext.php';
        $plugin_data = get_plugin_data($plugin_file, false, false);

        return $plugin_data['Version'] ?? 'unknown';
    }

}
