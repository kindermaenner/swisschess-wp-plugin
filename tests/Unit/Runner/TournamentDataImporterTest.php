<?php

declare(strict_types=1);

use SwissChess\Runner\TournamentDataImporter;

it('recognizes title-cased SwissChess export filenames', function () {
    $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'swisschess-import-' . uniqid('', true);
    mkdir($directory);

    $files = [
        'Stadtmeisterschaft2026-Teil.html' => '<H2>Stadtmeisterschaft 2026</H2><table><tr>'
            . '<td>1</td><td>Spieler</td><td></td><td>1900</td><td>M</td><td>Verein</td>'
            . '<td>GER</td><td>1990</td></tr></table>',
        'Stadtmeisterschaft2026-TeilRang.html' => '<table><tr><td>1</td><td>Spieler</td><td></td>'
            . '<td>1900</td><td>M</td><td>Verein</td><td>GER</td><td>1</td><td>0</td>'
            . '<td>0</td><td>1.0</td><td>0</td><td>0</td></tr></table>',
        'Stadtmeisterschaft2026-Paar-R1.html' => '<H2>Paarungsliste 1. Runde</H2><table><tr>'
            . '<td>1</td><td>1</td><td>Spieler</td><td></td><td>0.0</td><td>-</td>'
            . '<td>2</td><td>Gegner</td><td></td><td>0.0</td><td>-</td></tr></table>',
    ];

    $paths = [];
    foreach ($files as $filename => $contents) {
        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($path, $contents);
        $paths[] = $path;
    }

    $participants = [];
    $ranking = [];
    $pairings = [];
    $tournamentName = '';

    (new TournamentDataImporter())->parseFiles(
        $paths,
        $participants,
        $ranking,
        $pairings,
        $tournamentName
    );

    foreach ($paths as $path) {
        unlink($path);
    }
    rmdir($directory);

    expect($participants)->toHaveCount(1)
        ->and($ranking)->toHaveCount(1)
        ->and($pairings)->toHaveCount(1)
        ->and($tournamentName)->toBe('Stadtmeisterschaft 2026');
});
