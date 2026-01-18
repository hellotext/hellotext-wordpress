<?php

namespace Hellotext\Api;

use Hellotext\Constants;

class Client {
	public static string $sufix = '/' . Constants::API_VERSION;

	public static function with_sufix (string $sufix = ''): self {
		if (0 < strlen($sufix) && '/' !== $sufix[0]) {
			$sufix = '/' . $sufix;
		}

		self::$sufix = $sufix;

		return new self();
	}

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

	public static function get (string $path = '/', ?array $data = null): array {
		return self::request('GET', $path, $data);
	}

	public static function post (string $path = '/', ?array $data = null): array {
		return self::request('POST', $path, $data);
	}

	public static function patch (string $path = '/', ?array $data = null): array {
		return self::request('PATCH', $path, $data);
	}

	public static function put (string $path = '/', ?array $data = null): array {
		return self::request('PUT', $path, $data);
	}

	public static function delete (string $path = '/', ?array $data = null): array {
		return self::request('DELETE', $path, $data);
	}

	private static function get_api_url (): string {
		global $HELLOTEXT_API_URL;

		return $HELLOTEXT_API_URL . self::$sufix;
	}

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
