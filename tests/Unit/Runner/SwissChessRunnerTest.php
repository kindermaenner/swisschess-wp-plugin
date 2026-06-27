<?php

use SwissChess\Runner\SwissChessRunner;

it('builds a correct participant index from start_number', function () {

    $participants = [
        ['number' => 1, 'name' => 'Rangosch, Lukas'],
        ['number' => 4, 'name' => 'Kindermann, Thor'],
        ['number' => 7, 'name' => 'Pye, Francis'],
    ];

    $expected = [
        1 => 'Rangosch, Lukas',
        4 => 'Kindermann, Thor',
        7 => 'Pye, Francis',
    ];

    $runner = new SwissChessRunner();
    $index  = invokeMethod($runner, 'buildParticipantIndexById', [$participants]);

    expect($index)->toBe($expected);
});

it('fixes pairing names in a 2D round structure', function () {

    $participants = [
        ['number' => 1, 'name' => 'Rangosch, Lukas'],
        ['number' => 4, 'name' => 'Kindermann, Thor'],
        ['number' => 7, 'name' => 'Pye, Francis'],
    ];

    $pairings = [
        [   // Runde 1
            [
                'round' => 1,
                'board' => 1,
                'white_id' => 7,
                'white_name' => 'Pye, Fra',
                'black_id' => 4,
                'black_name' => 'Kindermann, Th',
            ],
        ],
        [   // Runde 2
            [
                'round' => 2,
                'board' => 1,
                'white_id' => 1,
                'white_name' => 'Rangosch, Luk',
                'black_id' => 7,
                'black_name' => 'Pye, Fra',
            ],
        ],
    ];

    $expected = [
        [
            [
                'round' => 1,
                'board' => 1,
                'white_id' => 7,
                'white_name' => 'Pye, Francis',
                'black_id' => 4,
                'black_name' => 'Kindermann, Thor',
            ],
        ],
        [
            [
                'round' => 2,
                'board' => 1,
                'white_id' => 1,
                'white_name' => 'Rangosch, Lukas',
                'black_id' => 7,
                'black_name' => 'Pye, Francis',
            ],
        ],
    ];

    $runner = new SwissChessRunner();
    $result = invokeMethod($runner, 'fixPairingNames', [$pairings, $participants]);

    expect($result)->toBe($expected);
});

it('returns true when the highest round has no results', function () {
    $runner = new SwissChessRunner();
    $pairings = [
        [ // Runde 1 – fertig
            ['round' => 1, 'result' => '1-0'],
        ],
        [ // Runde 3 – unfertig
            ['round' => 3, 'result' => '-'],
        ],
        [ // Runde 2 – fertig
            ['round' => 2, 'result' => '0-1'],
        ],
    ];

    $result = invokeMethod($runner, 'shouldPublishNextRound', [$pairings]);
    expect($result)->toBeTrue();
});

it('returns false when the highest round has results', function () {
    $runner = new SwissChessRunner();

    $pairings = [
        [ ['round' => 1, 'result' => '1-0'] ],
        [ ['round' => 3, 'result' => '0-1'] ], // höchste Runde, aber fertig
        [ ['round' => 2, 'result' => '-'] ],
    ];

    $result = invokeMethod($runner, 'shouldPublishNextRound', [$pairings]);
    expect($result)->toBeFalse();
});

it('returns false for empty or invalid pairing data', function () {
    $runner = new SwissChessRunner();

    $result = invokeMethod($runner, 'shouldPublishNextRound', [[]]);
    expect($result)->toBeFalse();

    $result = invokeMethod($runner, 'shouldPublishNextRound', [
        [ ['round' => null, 'result' => '-'] ]
    ]);
    expect($result)->toBeFalse();
});