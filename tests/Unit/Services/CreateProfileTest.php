<?php

use Hellotext\Services\CreateProfile;
use Hellotext\Api\Client;

$billingInfo = [
	'first_name' => 'John',
	'last_name' => 'Doe',
	'email' => 'john@example.com',
	'phone' => '1234567890',
	'address' => '123 Main St',
	'city' => 'New York',
	'state' => 'NY',
	'zip' => '10001',
	'country' => 'US'
];

beforeAll(function () {
	$_COOKIE['hello_session'] = '123';
});

beforeEach(function () {
	$this->user = TestHelper::find_or_create_user();
});

test('throws an exception if the user does not exist', function () {
	$this->expectException(\Exception::class);
	(new CreateProfile(999))->process();
});

test('does call the create_hellotext_profile function if the user id is not present but billing is', function () use ($billingInfo) {
	$service = Mockery::mock('Hellotext\Services\CreateProfile[create_hellotext_profile]', array( null, $billingInfo ))->makePartial();
	$service->shouldReceive('create_hellotext_profile')->once();

	$service->process();
});

test('does not call the create_hellotext_profile function if the user id is not present and billing is not either', function () {
	$service = Mockery::mock('Hellotext\Services\CreateProfile[create_hellotext_profile]', array( null ))->makePartial();
	$service->shouldNotReceive('create_hellotext_profile');

	$service->process();
});

// TODO: uncomment this test when a database connection is available
// test('calls the Hellotext\Api\Client with the correct parameters', function () {
//     $client = Mockery::mock(Client::class);

//     $client->shouldReceive('post')
//         ->once()
//         ->andReturn(array(
//             'body' => array(
//                 'id' => 1,
//             ),
//         ));

//     $client->shouldReceive('patch')->once();

//     $service = new CreateProfile($this->user->get_id());
//     $service->client = $client;

//     var_dump($this->user);

//     $service->process();
// });
