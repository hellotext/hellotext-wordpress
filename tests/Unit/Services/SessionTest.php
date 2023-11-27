<?php

use Hellotext\Services\Session;

test('encrypts data', function () {
    $encrypted = Session::encrypt('test');
    expect($encrypted)->not->toBe('test');
});

test('decrypts data', function () {
    $encrypted = Session::encrypt('test');
    $decrypted = Session::decrypt($encrypted);
    expect($decrypted)->toBe('test');
});
