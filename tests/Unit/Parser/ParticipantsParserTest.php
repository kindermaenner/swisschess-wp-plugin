<?php

declare(strict_types=1);

use SwissChess\Parser\ParticipantsParser;

it('extracts tournament name from html', function () {
    $html = file_get_contents(__DIR__ . '/../../data/runde3/stadtmeisterschaft-teil.html');

    $parser = new ParticipantsParser();
    $name = $parser->extractTournamentName($html);

    expect($name)->toBe('Stadtmeisterschaft Entenhausen 2026');
});

it('creates an xpath from swisschess html', function () {
    $html = file_get_contents(__DIR__ . '/../../data/runde3/stadtmeisterschaft-teil.html');

    $parser = new ParticipantsParser();
    $xpath = $parser->parseHtml($html);

    expect($xpath)->toBeInstanceOf(DOMXPath::class);

    $rows = $xpath->query('//tbody/tr');
    expect($rows->length)->toBeGreaterThan(0);
});

it('parses all participants correctly', function () {
    $html = file_get_contents(
        __DIR__ . '/../../data/runde3/stadtmeisterschaft-teil.html'
    );

    $parser = new ParticipantsParser();
    $result = $parser->parseParticipants($html);

    // Anzahl prüfen
    expect($result)->toBeArray();
    expect($result)->toHaveCount(9);

    // Erwartete Daten aus der Datei
    $expected = [
        [
            'number' => 1,
            'name' => 'Donald Duck',
            'title' => 'IM',
            'twz' => '2300',
            'gender' => '',
            'club' => 'SG Entenhausen',
            'country' => 'GER',
            'birthyear' => '1931',
        ],
        [
            'number' => 2,
            'name' => 'Gustav Gans',
            'title' => '',
            'twz' => '950',
            'gender' => '',
            'club' => 'Caissia Glücksente',
            'country' => 'GER',
            'birthyear' => '1950',
        ],
        [
            'number' => 3,
            'name' => 'Daniel Düsentrieb',
            'title' => 'GM',
            'twz' => '2484',
            'gender' => '',
            'club' => 'SC Geistesblitz',
            'country' => 'GER',
            'birthyear' => '1952',
        ],
        [
            'number' => 4,
            'name' => 'Mickey Mouse',
            'title' => '',
            'twz' => '1632',
            'gender' => '',
            'club' => 'SG Entenhausen',
            'country' => 'GER',
            'birthyear' => '1928',
        ],
        [
            'number' => 5,
            'name' => 'Minnie Mouse',
            'title' => 'WIM',
            'twz' => '1884',
            'gender' => '',
            'club' => 'SG Entenhausen',
            'country' => 'GER',
            'birthyear' => '1930',
        ],
        [
            'number' => 6,
            'name' => 'Daisy Duck',
            'title' => '',
            'twz' => '1792',
            'gender' => '',
            'club' => 'SG Entenhausen',
            'country' => 'GER',
            'birthyear' => '1940',
        ],
        [
            'number' => 7,
            'name' => 'Dagobert Duck',
            'title' => '',
            'twz' => '1193',
            'gender' => '',
            'club' => 'SC Goldente',
            'country' => 'GER',
            'birthyear' => '1947',
        ],
        [
            'number' => 8,
            'name' => 'Klaas Klever',
            'title' => '',
            'twz' => '1710',
            'gender' => '',
            'club' => 'SC Geistesblitz',
            'country' => 'GER',
            'birthyear' => '1963',
        ],
        [
            'number' => 9,
            'name' => 'Gundel Gaukeley',
            'title' => '',
            'twz' => '',
            'gender' => '',
            'club' => 'SG Entenhausen',
            'country' => 'GER',
            'birthyear' => '1961',
        ],
    ];

    // Jede Zeile prüfen
    foreach ($expected as $index => $participant) {
        expect($result[$index])->toMatchArray($participant);
    }
});
