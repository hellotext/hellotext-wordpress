<?php

use Hellotext\Services\CreateProfile;
use Hellotext\Api\Client;

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
