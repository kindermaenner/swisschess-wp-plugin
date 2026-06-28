<?php

declare(strict_types=1);

use SwissChess\Output\StaticTournamentPage;

/**
 * WP-Mocks
 */
beforeEach(function () {
    // Optionen
    $GLOBALS['wp_options'] = [
        'swisschess_template_static_page' => 'TemplatePage',
        'swisschess_author'   => 1,
    ];

    // Seiten
    $GLOBALS['wp_pages'] = [
        [
            'ID'           => 100,
            'post_title'   => 'TemplatePage',
            'post_name'    => 'templatepage',
            'post_content' => 'Turnier: {{tournament_name}} {{participants}} {{ranking}} {{all_pairings}}',
            'meta'         => [],
        ]
    ];

    // Inserted/Updated pages
    $GLOBALS['wp_inserted'] = [];
    $GLOBALS['wp_updated']  = [];
});

/**
 * Testklasse
 */
class StaticTournamentPageTester extends StaticTournamentPage
{
    public function callCreate(array $participants, array $ranking, array $pairings, string $name)
    {
        return $this->createOrUpdateStaticPage($participants, $ranking, $pairings, $name);
    }
}

# ---------------------------------------------------------
# TESTS
# ---------------------------------------------------------

it('creates a new static page when none exists', function () {

    $page = new StaticTournamentPageTester();

    $participants = [
        [
            'number' => 1,
            'name' => 'Max',
            'title' => 'FM',
            'twz' => 2000,
            'gender' => 'M',
            'club' => 'SV Musterstadt',
            'country' => 'DE',
        ]
    ];

    $ranking = [
        [
            'rank' => 1,
            'name' => 'Max',
            'title' => 'FM',
            'twz' => 2000,
            'gender' => 'M',
            'points' => '1.0',
            'wins' => 1,
            'draws' => 0,
            'losses' => 0,
            'buchholz' => '0.0',
            'sonneborn' => '0.0',
            'club' => 'SV Musterstadt',
            'country' => 'DE',
        ]
    ];

    $pairings = [
        [
            [
                'round' => 1,
                'board' => 1,
                'white_id' => 1,
                'white_name' => 'Max',
                'white_points' => '0.0',
                'black_id' => 1,
                'black_name' => 'Max',
                'black_points' => '0.0',
                'result' => '-',
            ]
        ]
    ];

    $id = $page->callCreate($participants, $ranking, $pairings, 'Stadtmeisterschaft 2026');

    expect($id)->toBeGreaterThan(100);

    $insert = $GLOBALS['wp_inserted'][0];

    expect($insert['post_title'])->toBe('stadtmeisterschaft-2026');
    expect($insert['post_name'])->toBe('stadtmeisterschaft-2026');

    $metaKey = '_stadtmeisterschaft-2026_static_page';

    // WP-realistisch: Meta liegt in wp_meta
    $meta = $GLOBALS['wp_meta'][$id][$metaKey] ?? null;

    expect($meta)->not->toBeNull();
    expect($meta[0])->toBe('1');
});

it('updates an existing static page when meta key exists', function () {

    // Bestehende Seite simulieren
    $GLOBALS['wp_pages'][] = [
        'ID'           => 500,
        'post_title'   => 'stadtmeisterschaft-2026',
        'post_name'    => 'stadtmeisterschaft-2026',
        'post_content' => 'ALT',
    ];

    // WP-realistische Meta-Daten
    $GLOBALS['wp_meta'][500]['_stadtmeisterschaft-2026_static_page'] = ['1'];

    $page = new StaticTournamentPageTester();

    $participants = [
        [
            'number' => 1,
            'name' => 'Max',
            'title' => 'FM',
            'twz' => 2000,
            'gender' => 'M',
            'club' => 'SV Musterstadt',
            'country' => 'DE',
        ]
    ];

    $ranking = [
        [
            'rank' => 1,
            'name' => 'Max',
            'title' => 'FM',
            'twz' => 2000,
            'gender' => 'M',
            'points' => '1.0',
            'wins' => 1,
            'draws' => 0,
            'losses' => 0,
            'buchholz' => '0.0',
            'sonneborn' => '0.0',
            'club' => 'SV Musterstadt',
            'country' => 'DE',
        ]
    ];

    $pairings = [
        [
            [
                'round' => 1,
                'board' => 1,
                'white_id' => 1,
                'white_name' => 'Max',
                'white_points' => '0.0',
                'black_id' => 1,
                'black_name' => 'Max',
                'black_points' => '0.0',
                'result' => '-',
            ]
        ]
    ];

    $id = $page->callCreate($participants, $ranking, $pairings, 'Stadtmeisterschaft 2026');

    expect($id)->toBe(500);

    $update = $GLOBALS['wp_updated'][0];

    expect($update['ID'])->toBe(500);
    expect($update['post_title'])->toBe('stadtmeisterschaft-2026');
    expect($update['post_name'])->toBe('stadtmeisterschaft-2026');

    expect($update['post_content'])->toContain('Stadtmeisterschaft 2026');
});
