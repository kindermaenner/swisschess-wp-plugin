<?php

use SwissChess\Parser\PairingsParser;

it('parses all pairings of round 1 correctly', function () {
    $html = file_get_contents(
        __DIR__ . '/../../data/neue_auslosung/stadtmeisterschaft-paar-r1.html'
    );

    $parser = new PairingsParser();
    $result = $parser->parsePairings($html);

    // Anzahl Bretter prüfen
    expect($result)->toBeArray();
    expect($result)->toHaveCount(5);

    // Erwartete Daten aus deiner echten Tabelle
    $expected = [
        [
            'round'        => 1,
            'board'        => 1,
            'white_id'     => 1,
            'white_name'   => 'Donald Duck',
            'white_title'  => 'IM',
            'white_points' => '0',
            'black_id'     => 5,
            'black_name'   => 'Minnie Mouse',
            'black_title'  => 'WIM',
            'black_points' => '0',
            'result'       => '½ - ½',
        ],
        [
            'round'        => 1,
            'board'        => 2,
            'white_id'     => 6,
            'white_name'   => 'Daisy Duck',
            'white_title'  => '',
            'white_points' => '0',
            'black_id'     => 2,
            'black_name'   => 'Gustav Gans',
            'black_title'  => '',
            'black_points' => '0',
            'result'       => '1 - 0',
        ],
        [
            'round'        => 1,
            'board'        => 3,
            'white_id'     => 3,
            'white_name'   => 'Daniel Düsentrie',
            'white_title'  => 'GM',
            'white_points' => '0',
            'black_id'     => 7,
            'black_name'   => 'Dagobert Duck',
            'black_title'  => '',
            'black_points' => '0',
            'result'       => '1 - 0',
        ],
        [
            'round'        => 1,
            'board'        => 4,
            'white_id'     => 8,
            'white_name'   => 'Klaas Klever',
            'white_title'  => '',
            'white_points' => '0',
            'black_id'     => 4,
            'black_name'   => 'Mickey Mouse',
            'black_title'  => '',
            'black_points' => '0',
            'result'       => '0 - 1',
        ],
        [
            'round'        => 1,
            'board'        => 5,
            'white_id'     => 9,
            'white_name'   => 'Gundel Gaukeley',
            'white_title'  => '',
            'white_points' => '0',
            'black_id'     => 0,
            'black_name'   => 'spielfrei',
            'black_title'  => '',
            'black_points' => '0',
            'result'       => '+ - -',
        ],
    ];

    foreach ($expected as $i => $entry) {
        expect($result[$i])->toMatchArray($entry);
    }
});

it('parses all pairings of round 2 correctly', function () {
    $html = file_get_contents(
        __DIR__ . '/../../data/neue_auslosung/stadtmeisterschaft-paar-r2.html'
    );

    $parser = new PairingsParser();
    $result = $parser->parsePairings($html);

    // Anzahl Bretter prüfen
    expect($result)->toBeArray();
    expect($result)->toHaveCount(5);

    // Erwartete Daten aus deiner echten Tabelle
    $expected = [
        [
            'round'        => 2,
            'board'        => 1,
            'white_id'     => 9,
            'white_name'   => 'Gundel Gaukeley',
            'white_title'  => '',
            'white_points' => '1',
            'black_id'     => 3,
            'black_name'   => 'Daniel Düsentrie',
            'black_title'  => 'GM',
            'black_points' => '1',
            'result'       => '-',
        ],
        [
            'round'        => 2,
            'board'        => 2,
            'white_id'     => 4,
            'white_name'   => 'Mickey Mouse',
            'white_title'  => '',
            'white_points' => '1',
            'black_id'     => 6,
            'black_name'   => 'Daisy Duck',
            'black_title'  => '',
            'black_points' => '1',
            'result'       => '-',
        ],
        [
            'round'        => 2,
            'board'        => 3,
            'white_id'     => 2,
            'white_name'   => 'Gustav Gans',
            'white_title'  => '',
            'white_points' => '0',
            'black_id'     => 1,
            'black_name'   => 'Donald Duck',
            'black_title'  => 'IM',
            'black_points' => '½',
            'result'       => '-',
        ],
        [
            'round'        => 2,
            'board'        => 4,
            'white_id'     => 5,
            'white_name'   => 'Minnie Mouse',
            'white_title'  => 'WIM',
            'white_points' => '½',
            'black_id'     => 8,
            'black_name'   => 'Klaas Klever',
            'black_title'  => '',
            'black_points' => '0',
            'result'       => '-',
        ],
        [
            'round'        => 2,
            'board'        => 5,
            'white_id'     => 7,
            'white_name'   => 'Dagobert Duck',
            'white_title'  => '',
            'white_points' => '0',
            'black_id'     => 0,
            'black_name'   => 'spielfrei',
            'black_title'  => '',
            'black_points' => '0',
            'result'       => '+ - -',
        ],
    ];

    foreach ($expected as $i => $entry) {
        expect($result[$i])->toMatchArray($entry);
    }
});
