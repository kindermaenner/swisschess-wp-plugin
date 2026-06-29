<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use SwissChess\Api\Api;
use SwissChess\Runner\SwissChessRunner;
use Tests\Unit\Helper\FakeRequest;

beforeEach(function () {
    $GLOBALS['wp_actions'] = [];
    $GLOBALS['wp_routes']  = [];
    $GLOBALS['wp_options'] = [
        'swisschess_api_key' => '',
    ];
});

test('Api::init registers rest_api_init action', function () {
    Api::init();

    expect($GLOBALS['wp_actions'])->toHaveCount(1);
    expect($GLOBALS['wp_actions'][0]['hook'])->toBe('rest_api_init');
    expect($GLOBALS['wp_actions'][0]['callback'])->toBe([Api::class, 'register_routes']);
});

test('Api::register_routes registers scan route correctly', function () {
    Api::register_routes();

    expect($GLOBALS['wp_routes'])->toHaveCount(1);

    $route = $GLOBALS['wp_routes'][0];

    expect($route['namespace'])->toBe('swisschess/v1');
    expect($route['route'])->toBe('/scan');

    expect($route['args']['methods'])->toBe(['POST', 'GET']);
    expect($route['args']['callback'])->toBe([Api::class, 'scan']);
    expect($route['args']['permission_callback'])->toBe([Api::class, 'verify_api_key']);
});

test('verify_api_key returns error if API-Key is not set', function () {
    $GLOBALS['wp_options']['swisschess_api_key'] = '';

    $request = new FakeRequest(['x-mb-key' => 'abc']);

    $result = Api::verify_api_key($request);

    expect($result)->toBeInstanceOf(\WP_Error::class);
    expect($result->get_error_code())->toBe('rest_misconfigured');
    expect($result->get_error_data()['status'])->toBe(500);
});

test('verify_api_key returns error if header key is incorrect', function () {
    $GLOBALS['wp_options']['swisschess_api_key'] = 'correct-key';

    $request = new FakeRequest(['x-mb-key' => 'wrong-key']);

    $result = Api::verify_api_key($request);

    expect($result)->toBeInstanceOf(\WP_Error::class);
    expect($result->get_error_code())->toBe('rest_forbidden');
    expect($result->get_error_data()['status'])->toBe(403);
});

test('verify_api_key returns true if key is correct', function () {
    $GLOBALS['wp_options']['swisschess_api_key'] = 'correct-key';

    $request = new FakeRequest(['x-mb-key' => 'correct-key']);

    $result = Api::verify_api_key($request);

    expect($result)->toBeTrue();
});

test('verify_api_key accepts the cron query key fallback', function () {
    $GLOBALS['wp_options']['swisschess_api_key'] = 'correct-key';

    $request = new FakeRequest([], ['key' => 'correct-key']);

    $result = Api::verify_api_key($request);

    expect($result)->toBeTrue();
});

test('scan returns wrapped runner response payload', function () {
    $request = new FakeRequest(['x-mb-key' => 'correct-key']);

    $result = Api::scan($request);

    expect($result)->toBeArray();
    expect($result['success'])->toBeTrue();
    expect($result['data'])->toBeArray();
    expect($result['data'])->toHaveKey('success');
});