<?php

declare(strict_types=1);

use SwissChess\Parser\ParticipantsParser;
use SwissChess\Runner\SwissChessRunner;

class SwissChessRunnerScenarioTester extends SwissChessRunner
{
    public function __construct(private string $dataDir)
    {
    }

    protected function findFiles(): array
    {
        $files = glob($this->dataDir . '/*.html') ?: [];
        sort($files);
        return $files;
    }
}

function prepareRunnerScenarioGlobals(string $dataDir): void
{
    $participantHtml = file_get_contents($dataDir . '/stadtmeisterschaft-teil.html');
    $parser = new ParticipantsParser();
    $tournamentName = $parser->extractTournamentName($participantHtml);

    $slug = sanitize_title(str_replace(' ', '-', $tournamentName));
    $staticMetaKey = '_' . $slug . '_static_page';

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
            'post_content' => 'Turnier: {{tournament_name}} Runde {{round_no_actual}} {{pairings_actual_round}}',
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
        [
            'ID' => 500,
            'post_title' => $slug,
            'post_name' => $slug,
            'post_content' => 'ALT',
            'post_type' => 'page',
            'meta' => [],
        ],
    ];

    $GLOBALS['wp_meta'] = [
        500 => [
            $staticMetaKey => ['1'],
        ],
        100 => ['layout' => ['fullwidth']],
        101 => ['layout' => ['hero']],
        102 => ['layout' => ['hero-final']],
    ];

    $GLOBALS['wp_inserted'] = [];
    $GLOBALS['wp_updated'] = [];
}

it('neue_auslosung updates static page and creates next-round post', function () {
    $dataDir = __DIR__ . '/../../data/neue_auslosung';
    prepareRunnerScenarioGlobals($dataDir);

    $runner = new SwissChessRunnerScenarioTester($dataDir);
    $result = $runner->run();

    expect($result['success'])->toBeTrue();

    expect($GLOBALS['wp_updated'])->toHaveCount(1);
    expect($GLOBALS['wp_updated'][0]['ID'])->toBe(500);

    $nextRoundMetaFound = false;
    foreach ($GLOBALS['wp_meta'] as $metaByKey) {
        foreach ($metaByKey as $key => $values) {
            if (str_ends_with((string)$key, '_next_round_post') && in_array('1', $values, true)) {
                $nextRoundMetaFound = true;
                break 2;
            }
        }
    }

    expect($nextRoundMetaFound)->toBeTrue();
});

it('turnier_beendet updates static page and creates final-results post', function () {
    $dataDir = __DIR__ . '/../../data/turnier_beendet';
    prepareRunnerScenarioGlobals($dataDir);

    $runner = new SwissChessRunnerScenarioTester($dataDir);
    $result = $runner->run();

    expect($result['success'])->toBeTrue();

    expect($GLOBALS['wp_updated'])->toHaveCount(1);
    expect($GLOBALS['wp_updated'][0]['ID'])->toBe(500);

    $finalMetaFound = false;
    foreach ($GLOBALS['wp_meta'] as $metaByKey) {
        foreach ($metaByKey as $key => $values) {
            if (str_ends_with((string)$key, '_final_results_post') && in_array('1', $values, true)) {
                $finalMetaFound = true;
                break 2;
            }
        }
    }

    expect($finalMetaFound)->toBeTrue();
});

it('zwischenergebnisse only updates static page and creates no round/final post', function () {
    $dataDir = __DIR__ . '/../../data/zwischenergebnisse';
    prepareRunnerScenarioGlobals($dataDir);

    $runner = new SwissChessRunnerScenarioTester($dataDir);
    $result = $runner->run();

    expect($result['success'])->toBeTrue();

    expect($GLOBALS['wp_updated'])->toHaveCount(1);
    expect($GLOBALS['wp_updated'][0]['ID'])->toBe(500);

    $nextRoundMetaFound = false;
    $finalMetaFound = false;

    foreach ($GLOBALS['wp_meta'] as $metaByKey) {
        foreach ($metaByKey as $key => $values) {
            if (str_ends_with((string)$key, '_next_round_post') && in_array('1', $values, true)) {
                $nextRoundMetaFound = true;
            }

            if (str_ends_with((string)$key, '_final_results_post') && in_array('1', $values, true)) {
                $finalMetaFound = true;
            }
        }
    }

    expect($nextRoundMetaFound)->toBeFalse();
    expect($finalMetaFound)->toBeFalse();
});
