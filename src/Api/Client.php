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
	public static function with_sufix (string $sufix = ''): self {
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
	public static function request (string $method = 'GET', string $path = '/', array $data = []): array {
		$request_url = self::get_api_url() . $path;
		$curl = curl_init($request_url);
		self::set_curl_options($curl, $method);

		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

		$result = curl_exec($curl);

		return array(
			'request' => array(
				'method' => $method,
				'path' => $request_url,
				'data' => $data,
			),
			'status' => curl_getinfo($curl, CURLINFO_HTTP_CODE),
			'body' => json_decode($result, true)
		);
	}

	/**
	 * Make a GET request.
	 *
	 * @param string $path API endpoint path.
	 * @param array|null $data Request payload.
	 * @return array
	 */
	public static function get (string $path = '/', ?array $data = null): array {
		return self::request('GET', $path, $data);
	}

	/**
	 * Make a POST request.
	 *
	 * @param string $path API endpoint path.
	 * @param array|null $data Request payload.
	 * @return array
	 */
	public static function post (string $path = '/', ?array $data = null): array {
		return self::request('POST', $path, $data);
	}

	/**
	 * Make a PATCH request.
	 *
	 * @param string $path API endpoint path.
	 * @param array|null $data Request payload.
	 * @return array
	 */
	public static function patch (string $path = '/', ?array $data = null): array {
		return self::request('PATCH', $path, $data);
	}

	/**
	 * Make a PUT request.
	 *
	 * @param string $path API endpoint path.
	 * @param array|null $data Request payload.
	 * @return array
	 */
	public static function put (string $path = '/', ?array $data = null): array {
		return self::request('PUT', $path, $data);
	}

	/**
	 * Make a DELETE request.
	 *
	 * @param string $path API endpoint path.
	 * @param array|null $data Request payload.
	 * @return array
	 */
	public static function delete (string $path = '/', ?array $data = null): array {
		return self::request('DELETE', $path, $data);
	}

	/**
	 * Get base API URL.
	 *
	 * @return string
	 */
	private static function get_api_url (): string {
		global $HELLOTEXT_API_URL;

		return $HELLOTEXT_API_URL . self::$sufix;
	}

	/**
	 * Configure cURL options for the request.
	 *
	 * @param resource|\CurlHandle $curl cURL handle.
	 * @param string $method HTTP method.
	 * @return void
	 */
	private static function set_curl_options ($curl, string $method): void {
		$hellotext_access_token = get_option(Constants::OPTION_ACCESS_TOKEN);

		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $hellotext_access_token,
		));
	}
}
