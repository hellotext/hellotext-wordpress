<?php

use Hellotext\Api\Client;
use Hellotext\Constants;

beforeEach(function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', '/tmp/wordpress/');
    }
});

test('sends requests to the Hellotext API', function () {
    $response = Client::get(Constants::API_ENDPOINT_PROFILES);

    expect($response['request']['path'])->toBe('https://api.hellotext.com/v1/profiles');
});

test('keeps each endpoint on its own path', function () {
    Client::post(Constants::API_ENDPOINT_INTEGRATIONS_WOO);
    $response = Client::post(Constants::API_ENDPOINT_PROFILES);

    expect($response['request']['path'])->toBe('https://api.hellotext.com/v1/profiles');
});

test('Client::get() returns expected structure', function () {
    $response = Client::get('/test');

    expect($response)->toBeArray();
    expect($response)->toHaveKeys(['request', 'status', 'body', 'error']);
});

test('Client::post() returns expected structure', function () {
    $response = Client::post('/test', ['data' => 'value']);

    expect($response)->toBeArray();
    expect($response)->toHaveKeys(['request', 'status', 'body', 'error']);
});

test('Client::put() returns expected structure', function () {
    $response = Client::put('/test/1', ['data' => 'value']);

    expect($response)->toBeArray();
    expect($response)->toHaveKeys(['request', 'status', 'body', 'error']);
});

test('Client::patch() returns expected structure', function () {
    $response = Client::patch('/test/1', ['data' => 'value']);

    expect($response)->toBeArray();
    expect($response)->toHaveKeys(['request', 'status', 'body', 'error']);
});

test('Client::delete() returns expected structure', function () {
    $response = Client::delete('/test/1');

    expect($response)->toBeArray();
    expect($response)->toHaveKeys(['request', 'status', 'body', 'error']);
});

test('request includes method in response', function () {
    $response = Client::get('/test');

    expect($response['request']['method'])->toBe('GET');
});

test('request includes path in response', function () {
    $response = Client::get('/test/endpoint');

    expect($response['request']['path'])->toContain('/test/endpoint');
});

test('request includes data in response', function () {
    $data = ['key' => 'value'];
    $response = Client::post('/test', $data);

    expect($response['request']['data'])->toBe($data);
});

test('all HTTP methods accept optional data parameter', function () {
    // These should not throw errors
    expect(fn() => Client::get('/test', null))->not->toThrow(Error::class);
    expect(fn() => Client::post('/test', null))->not->toThrow(Error::class);
    expect(fn() => Client::put('/test', null))->not->toThrow(Error::class);
    expect(fn() => Client::patch('/test', null))->not->toThrow(Error::class);
    expect(fn() => Client::delete('/test', null))->not->toThrow(Error::class);
});

test('handles empty path gracefully', function () {
    $response = Client::get('');

    expect($response)->toBeArray();
    expect($response)->toHaveKey('status');
});

test('request method is case-insensitive', function () {
    $response = Client::request('get', '/test');
    expect($response['request']['method'])->toBe('get');
});

test('static methods can be called', function () {
    expect(method_exists(Client::class, 'get'))->toBeTrue();
    expect(method_exists(Client::class, 'post'))->toBeTrue();
    expect(method_exists(Client::class, 'put'))->toBeTrue();
    expect(method_exists(Client::class, 'patch'))->toBeTrue();
    expect(method_exists(Client::class, 'delete'))->toBeTrue();
    expect(method_exists(Client::class, 'request'))->toBeTrue();
});
