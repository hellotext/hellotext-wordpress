<?php

namespace Hellotext\Api;

class Client {
    const DEV_URL = 'http://api.lvh.me:4000/v1';
    const API_URL = 'https://api.hellotext.com/v1';

    public static function request ($method = 'GET', $path = '/', $data = []) {
        $requestUrl = self::get_api_url() . $path;
        $curl = curl_init($requestUrl);
        self::set_curl_options($curl, $method);

        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

        $result = curl_exec($curl);

        return array(
            'request' => array(
                'method' => $method,
                'path' => $requestUrl,
                'data' => $data,
            ),
            'status' => curl_getinfo($curl, CURLINFO_HTTP_CODE),
            'body' => json_decode($result, true)
        );
    }

    public static function get ($path = '/', $data = null) {
        return Client::request('GET', $path, $data);
    }

    public static function post ($path = '/', $data = null) {
        return Client::request('POST', $path, $data);
    }

    public static function patch ($path = '/', $data = null) {
        return Client::request('PATCH', $path, $data);
    }

    public static function put ($path = '/', $data = null) {
        return Client::request('PUT', $path, $data);
    }

    public static function delete ($path = '/', $data = null) {
        return Client::request('DELETE', $path, $data);
    }

    private static function get_api_url () {
        return (isset($HELLOTEXT_DEV_MODE) && $HELLOTEXT_DEV_MODE)
            ? self::DEV_URL
            : self::API_URL;
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