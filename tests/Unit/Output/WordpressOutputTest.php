<?php

declare(strict_types=1);

use SwissChess\Output\WordpressOutput;

/**
 * WP-Mocks
 */
beforeEach(function () {
    $GLOBALS['test_meta'] = [];
});

/**
 * Mock: get_post_meta
 */
function get_post_meta($post_id)
{
    return $GLOBALS['test_meta'][$post_id] ?? [];
}

/**
 * Mock: update_post_meta
 */
function update_post_meta($post_id, $key, $value)
{
    if (!isset($GLOBALS['test_meta'][$post_id])) {
        $GLOBALS['test_meta'][$post_id] = [];
    }

    if (!isset($GLOBALS['test_meta'][$post_id][$key])) {
        $GLOBALS['test_meta'][$post_id][$key] = [];
    }

    $GLOBALS['test_meta'][$post_id][$key][] = $value;
}

/**
 * Test-Wrapper für protected Methoden
 */
class WordpressOutputTester extends WordpressOutput
{
    public function participants(array $p): string
    {
        return $this->participantsToHtmlTable($p);
    }

    public function ranking(array $r): string
    {
        return $this->rankingToHtmlTable($r);
    }

    public function pairings(array $p): string
    {
        return $this->pairingsToHtmlTable($p);
    }

    public function copyMeta(int $from, int $to)
    {
        return $this->copyAllMeta($from, $to);
    }
}

# ---------------------------------------------------------
# participantsToHtmlTable
# ---------------------------------------------------------

it('returns message when participants list is empty', function () {
    $o = new WordpressOutputTester();
    $html = $o->participants([]);

    expect($html)->toContain('Keine Teilnehmerdaten vorhanden');
});

it('renders participants table correctly', function () {
    $o = new WordpressOutputTester();

    $html = $o->participants([
        [
            'number' => 1,
            'name' => 'Max Mustermann',
            'title' => 'GM',
            'twz' => 2400,
            'gender' => 'M',
            'club' => 'Hamburg',
            'country' => 'DE'
        ]
    ]);

    expect($html)->toContain('<table>');
    expect($html)->toContain('<td>1</td>');
    expect($html)->toContain('<td>Max Mustermann</td>');
    expect($html)->toContain('<td>GM</td>');
    expect($html)->toContain('<td>2400</td>');
    expect($html)->toContain('<td>M</td>');
    expect($html)->toContain('<td>Hamburg</td>');
    expect($html)->toContain('<td>DE</td>');
});

# ---------------------------------------------------------
# rankingToHtmlTable
# ---------------------------------------------------------

it('returns message when ranking list is empty', function () {
    $o = new WordpressOutputTester();
    $html = $o->ranking([]);

    expect($html)->toContain('Keine Ranglistendaten vorhanden');
});

it('renders ranking table correctly', function () {
    $o = new WordpressOutputTester();

    $html = $o->ranking([
        [
            'rank' => 1,
            'name' => 'Anna Beispiel',
            'title' => 'WGM',
            'twz' => 2300,
            'gender' => 'F',
            'points' => 5,
            'wins' => 4,
            'draws' => 2,
            'losses' => 0,
            'buchholz' => 12.5,
            'sonneborn' => 10.0,
            'club' => 'Berlin',
            'country' => 'DE'
        ]
    ]);

    expect($html)->toContain('<td>1</td>');
    expect($html)->toContain('<td>Anna Beispiel</td>');
    expect($html)->toContain('<td>WGM</td>');
    expect($html)->toContain('<td>2300</td>');
    expect($html)->toContain('<td>F</td>');
    expect($html)->toContain('<td>5</td>');
    expect($html)->toContain('<td>4</td>');
    expect($html)->toContain('<td>2</td>');
    expect($html)->toContain('<td>0</td>');
    expect($html)->toContain('<td>12.5</td>');
    expect($html)->toContain('<td>10</td>');
    expect($html)->toContain('<td>Berlin</td>');
    expect($html)->toContain('<td>DE</td>');
});

# ---------------------------------------------------------
# pairingsToHtmlTable
# ---------------------------------------------------------

it('returns message when pairings list is empty', function () {
    $o = new WordpressOutputTester();
    $html = $o->pairings([]);

    expect($html)->toContain('Keine Paarungsdaten vorhanden');
});

it('renders pairings table correctly', function () {
    $o = new WordpressOutputTester();

    $html = $o->pairings([
        [
            [
                'round' => 1,
                'board' => 3,
                'white_id' => 10,
                'white_name' => 'Spieler Weiß',
                'white_points' => 2,
                'black_id' => 20,
                'black_name' => 'Spieler Schwarz',
                'black_points' => 1,
                'result' => '1-0'
            ]
        ]
    ]);

    expect($html)->toContain('Runde 1');
    expect($html)->toContain('<td>3</td>');
    expect($html)->toContain('<td>10</td>');
    expect($html)->toContain('<td>Spieler Weiß</td>');
    expect($html)->toContain('<td>2</td>');
    expect($html)->toContain('<td>20</td>');
    expect($html)->toContain('<td>Spieler Schwarz</td>');
    expect($html)->toContain('<td>1</td>');
    expect($html)->toContain('<td>1-0</td>');
});

# ---------------------------------------------------------
# copyAllMeta
# ---------------------------------------------------------

it('copies all meta except blacklist', function () {

    $from = 10;
    $to   = 20;

    $GLOBALS['test_meta'][$from] = [
        '_generate_layout' => ['full-width'],
        '_generate_sidebar_layout' => ['no-sidebar'],
        '_custom_key' => ['abc'],
        '_edit_last' => ['should_not_copy'],
        '_thumbnail_id' => [999],
    ];

    $o = new WordpressOutputTester();
    $o->copyMeta($from, $to);

    expect($GLOBALS['test_meta'][$to])
        ->not->toHaveKey('_edit_last')
        ->not->toHaveKey('_thumbnail_id');

    expect($GLOBALS['test_meta'][$to])
        ->toHaveKey('_generate_layout')
        ->toHaveKey('_generate_sidebar_layout')
        ->toHaveKey('_custom_key');

    expect($GLOBALS['test_meta'][$to]['_generate_layout'][0])
        ->toBe('full-width');

    expect($GLOBALS['test_meta'][$to]['_custom_key'][0])
        ->toBe('abc');
});

it('copies multiple values per meta key', function () {

    $from = 11;
    $to   = 21;

    $GLOBALS['test_meta'][$from] = [
        '_generate_layout' => ['full-width', 'override'],
    ];

    $o = new WordpressOutputTester();
    $o->copyMeta($from, $to);

    expect($GLOBALS['test_meta'][$to]['_generate_layout'])
        ->toBe(['full-width', 'override']);
});

it('unserializes values correctly', function () {

    $from = 12;
    $to   = 22;

    $serialized = serialize(['a' => 1, 'b' => 2]);

    $GLOBALS['test_meta'][$from] = [
        '_generate_complex' => [$serialized],
    ];

    $o = new WordpressOutputTester();
    $o->copyMeta($from, $to);

    expect($GLOBALS['test_meta'][$to]['_generate_complex'][0])
        ->toBe(['a' => 1, 'b' => 2]);
});
