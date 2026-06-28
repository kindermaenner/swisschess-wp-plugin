<?php

use SwissChess\Parser\RankingParser;

it('parses the full ranking list correctly', function () {
    $html = file_get_contents(
        __DIR__ . '/../../data/neue_auslosung/stadtmeisterschaft-teilrang.html'
    );

    $parser = new RankingParser();
    $result = $parser->parseRanking($html);

    // Anzahl prüfen
    expect($result)->toBeArray();
    expect($result)->toHaveCount(9);

    // Erwartete Daten aus der Datei
    $expected = [
        [
            'rank' => 1,
            'name' => 'Daniel Düsentrieb',
            'title' => 'GM',
            'twz' => '2484',
            'gender' => '',
            'club' => 'SC Geistesblitz',
            'country' => 'GER',
            'wins' => '1',
            'draws' => '0',
            'losses' => '0',
            'points' => '1.0',
            'buchholz' => '2.0',
            'sonneborn' => '1.00',
        ],
        [
            'rank' => 1,
            'name' => 'Dagobert Duck',
            'title' => '',
            'twz' => '1193',
            'gender' => '',
            'club' => 'SC Goldente',
            'country' => 'GER',
            'wins' => '1',
            'draws' => '0',
            'losses' => '1',
            'points' => '1.0',
            'buchholz' => '2.0',
            'sonneborn' => '1.00',
        ],
        [
            'rank' => 1,
            'name' => 'Gundel Gaukeley',
            'title' => '',
            'twz' => '',
            'gender' => '',
            'club' => 'SG Entenhausen',
            'country' => 'GER',
            'wins' => '1',
            'draws' => '0',
            'losses' => '0',
            'points' => '1.0',
            'buchholz' => '2.0',
            'sonneborn' => '1.00',
        ],
        [
            'rank' => 4,
            'name' => 'Mickey Mouse',
            'title' => '',
            'twz' => '1632',
            'gender' => '',
            'club' => 'SG Entenhausen',
            'country' => 'GER',
            'wins' => '1',
            'draws' => '0',
            'losses' => '0',
            'points' => '1.0',
            'buchholz' => '1.0',
            'sonneborn' => '0.00',
        ],
        [
            'rank' => 4,
            'name' => 'Daisy Duck',
            'title' => '',
            'twz' => '1792',
            'gender' => '',
            'club' => 'SG Entenhausen',
            'country' => 'GER',
            'wins' => '1',
            'draws' => '0',
            'losses' => '0',
            'points' => '1.0',
            'buchholz' => '1.0',
            'sonneborn' => '0.00',
        ],
        [
            'rank' => 6,
            'name' => 'Donald Duck',
            'title' => 'IM',
            'twz' => '2300',
            'gender' => '',
            'club' => 'SG Entenhausen',
            'country' => 'GER',
            'wins' => '0',
            'draws' => '1',
            'losses' => '0',
            'points' => '0.5',
            'buchholz' => '0.5',
            'sonneborn' => '0.25',
        ],
        [
            'rank' => 6,
            'name' => 'Minnie Mouse',
            'title' => 'WIM',
            'twz' => '1884',
            'gender' => '',
            'club' => 'SG Entenhausen',
            'country' => 'GER',
            'wins' => '0',
            'draws' => '1',
            'losses' => '0',
            'points' => '0.5',
            'buchholz' => '0.5',
            'sonneborn' => '0.25',
        ],
        [
            'rank' => 8,
            'name' => 'Gustav Gans',
            'title' => '',
            'twz' => '950',
            'gender' => '',
            'club' => 'Caissia Glückse',
            'country' => 'GER',
            'wins' => '0',
            'draws' => '0',
            'losses' => '1',
            'points' => '0.0',
            'buchholz' => '1.5',
            'sonneborn' => '0.00',
        ],
        [
            'rank' => 8,
            'name' => 'Klaas Klever',
            'title' => '',
            'twz' => '1710',
            'gender' => '',
            'club' => 'SC Geistesblitz',
            'country' => 'GER',
            'wins' => '0',
            'draws' => '0',
            'losses' => '1',
            'points' => '0.0',
            'buchholz' => '1.5',
            'sonneborn' => '0.00',
        ],
    ];

    foreach ($expected as $i => $entry) {
        expect($result[$i])->toMatchArray($entry);
    }
});
