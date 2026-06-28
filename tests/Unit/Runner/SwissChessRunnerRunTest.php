<?php

declare(strict_types=1);

use SwissChess\Runner\SwissChessRunner;

class SwissChessRunnerRunTester extends SwissChessRunner
{
    public function __construct(private array $files, private array $fixture)
    {
    }

    protected function findFiles(): array
    {
        return $this->files;
    }

    protected function parseFiles(array $files): void
    {
        $ref = new ReflectionClass(SwissChessRunner::class);

        foreach (['participants', 'ranking', 'pairings', 'tournament_name'] as $prop) {
            $property = $ref->getProperty($prop);
            $property->setAccessible(true);
            $property->setValue($this, $this->fixture[$prop]);
        }
    }
}

class SwissChessRunnerFileCleanupTester extends SwissChessRunnerRunTester
{
    public function __construct(private array $files, array $fixture)
    {
        parent::__construct($files, $fixture);
    }

    protected function findFiles(): array
    {
        return $this->files;
    }
}

function runnerFixture(array $pairings): array
{
    return [
        'participants' => [[
            'number' => 1,
            'name' => 'Max',
            'title' => 'FM',
            'twz' => 2000,
            'gender' => 'M',
            'club' => 'SV Musterstadt',
            'country' => 'DE',
        ]],
        'ranking' => [[
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
        ]],
        'pairings' => $pairings,
        'tournament_name' => 'Stadtmeisterschaft 2026',
    ];
}

beforeEach(function () {
    $GLOBALS['wp_options'] = [
        'swisschess_author' => 1,
        'swisschess_template_static_page' => 'TemplateStatic',
        'swisschess_template_next_round_post' => 'TemplateNextRound',
        'swisschess_template_final_results_post' => 'TemplateFinalResults',
    ];

    $GLOBALS['wp_pages'] = [
        [
            'ID' => 100,
            'post_title' => 'TemplateStatic',
            'post_name' => 'template-static',
            'post_content' => 'Turnier: {{tournament_name}} {{participants}} {{ranking}} {{all_pairings}}',
            'post_type' => 'page',
            'meta' => [],
        ],
        [
            'ID' => 101,
            'post_title' => 'TemplateNextRound',
            'post_name' => 'template-next-round',
            'post_content' => 'Turnier: {{tournament_name}} {{pairings_actual_round}}',
            'post_type' => 'post',
            'meta' => [],
        ],
        [
            'ID' => 102,
            'post_title' => 'TemplateFinalResults',
            'post_name' => 'template-final-results',
            'post_content' => 'Finale: {{tournament_name}} {{ranking}}',
            'post_type' => 'post',
            'meta' => [],
        ],
    ];

    $GLOBALS['wp_meta'] = [];
    $GLOBALS['wp_inserted'] = [];
    $GLOBALS['wp_updated'] = [];
    $GLOBALS['wp_nav_menus'] = [];
    $GLOBALS['wp_nav_menu_items'] = [];
    $GLOBALS['wp_options']['swisschess_delete_after_import'] = false;
});

it('run returns failure when no files are found', function () {
    $runner = new SwissChessRunnerRunTester([], runnerFixture([]));

    $result = $runner->run();

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('No files found');
});

it('run returns failure details when static page creation returns WP_Error', function () {
    $GLOBALS['wp_options']['swisschess_template_static_page'] = '';

    $pairings = [[[
        'round' => 1,
        'board' => 1,
        'white_id' => 1,
        'white_name' => 'Max',
        'white_points' => '0.0',
        'black_id' => 2,
        'black_name' => 'Anna',
        'black_points' => '0.0',
        'result' => '-',
    ]]];

    $runner = new SwissChessRunnerRunTester(['dummy.html'], runnerFixture($pairings));

    $result = $runner->run();

    expect($result['success'])->toBeFalse();
    expect($result['error_code'])->toBe('no_template');
    expect($result['warnings'])->toBeArray();
});

it('run adds warning when next-round template is missing but continues successfully', function () {
    $GLOBALS['wp_options']['swisschess_template_next_round_post'] = '';

    $pairings = [[[
        'round' => 2,
        'board' => 1,
        'white_id' => 1,
        'white_name' => 'Max',
        'white_points' => '0.0',
        'black_id' => 2,
        'black_name' => 'Anna',
        'black_points' => '0.0',
        'result' => '-',
    ]]];

    $runner = new SwissChessRunnerRunTester(['dummy.html'], runnerFixture($pairings));

    $result = $runner->run();

    expect($result['success'])->toBeTrue();
    expect($result['warnings'])->toHaveCount(1);
    expect($result['warnings'][0])->toContain('swisschess_template_next_round_post');
});

it('run adds warning when final-results template is missing but continues successfully', function () {
    $GLOBALS['wp_options']['swisschess_template_final_results_post'] = '';

    $pairings = [[[
        'round' => 2,
        'board' => 1,
        'white_id' => 1,
        'white_name' => 'Max',
        'white_points' => '0.0',
        'black_id' => 2,
        'black_name' => 'Anna',
        'black_points' => '0.0',
        'result' => '1-0',
    ]]];

    $runner = new SwissChessRunnerRunTester(['dummy.html'], runnerFixture($pairings));

    $result = $runner->run();

    expect($result['success'])->toBeTrue();
    expect($result['warnings'])->toHaveCount(1);
    expect($result['warnings'][0])->toContain('swisschess_template_final_results_post');
});

it('run deletes imported files when delete-after-import is enabled', function () {
    $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'swisschess-delete-test-' . uniqid('', true);
    mkdir($tmpDir);

    $files = [
        $tmpDir . DIRECTORY_SEPARATOR . 'stadtmeisterschaft-teil.html',
        $tmpDir . DIRECTORY_SEPARATOR . 'stadtmeisterschaft-teilrang.html',
        $tmpDir . DIRECTORY_SEPARATOR . 'stadtmeisterschaft-paar-r1.html',
    ];

    foreach ($files as $file) {
        file_put_contents($file, '<html></html>');
    }

    $GLOBALS['wp_options']['swisschess_delete_after_import'] = true;

    $fixture = runnerFixture([[[
        'round' => 2,
        'board' => 1,
        'white_id' => 1,
        'white_name' => 'Max',
        'white_points' => '0.0',
        'black_id' => 2,
        'black_name' => 'Anna',
        'black_points' => '0.0',
        'result' => '1-0',
    ]]]);

    $runner = new SwissChessRunnerFileCleanupTester($files, $fixture);

    $result = $runner->run();

    expect($result['success'])->toBeTrue();

    foreach ($files as $file) {
        expect(file_exists($file))->toBeFalse();
    }

    @rmdir($tmpDir);
});
