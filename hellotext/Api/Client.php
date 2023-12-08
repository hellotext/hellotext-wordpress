<?php

namespace Hellotext\Api;

class Client {
	const DEV_API_URL = 'http://api.lvh.me:4000';
	const API_URL = 'https://api.hellotext.com';

	public static $sufix = '/v1';

	public static function with_sufix ($sufix = '') {
		if (0 < strlen($sufix) && '/' !== $sufix[0]) {
			$sufix = '/' . $sufix;
		}

		self::$sufix = $sufix;

		return new self();
	}

	public static function request ($method = 'GET', $path = '/', $data = []) {
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

	public static function get ($path = '/', $data = null) {
		return self::request('GET', $path, $data);
	}

	public static function post ($path = '/', $data = null) {
		return self::request('POST', $path, $data);
	}

	public static function patch ($path = '/', $data = null) {
		return self::request('PATCH', $path, $data);
	}

	public static function put ($path = '/', $data = null) {
		return self::request('PUT', $path, $data);
	}

	public static function delete ($path = '/', $data = null) {
		return self::request('DELETE', $path, $data);
	}

	private static function get_api_url () {
		global $HELLOTEXT_DEV_MODE;

		$domain = ( isset($HELLOTEXT_DEV_MODE) && $HELLOTEXT_DEV_MODE )
			? self::DEV_API_URL
			: self::API_URL;

		return $domain . self::$sufix;
	}

	private static function set_curl_options ($curl, $method) {
		$hellotext_access_token = get_option('hellotext_access_token');

		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $hellotext_access_token,
		));
	}
}
