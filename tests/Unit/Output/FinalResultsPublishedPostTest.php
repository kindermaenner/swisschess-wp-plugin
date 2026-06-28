<?php

declare(strict_types=1);

use SwissChess\Output\FinalResultsPublishedPost;

beforeEach(function () {
    $GLOBALS['wp_options'] = [
        'swisschess_template_final_results_post' => 'FinalResultsTemplate',
        'swisschess_author' => 2,
    ];

    $GLOBALS['wp_pages'] = [
        [
            'ID' => 400,
            'post_title' => 'FinalResultsTemplate',
            'post_name' => 'final-results-template',
            'post_content' => 'Finale: {{tournament_name}} {{ranking}}',
            'post_type' => 'post',
            'meta' => [],
        ],
    ];

    $GLOBALS['wp_meta'] = [
        400 => [
            'layout' => ['fullwidth'],
            '_thumbnail_id' => ['888'],
            '_edit_lock' => ['12345'],
        ],
    ];

    $GLOBALS['wp_inserted'] = [];
    $GLOBALS['wp_updated'] = [];
    $GLOBALS['wp_set_terms'] = [];
    $GLOBALS['wp_terms_by_post'][400]['category'] = [
        (object)['term_id' => 20, 'name' => 'template'],
        (object)['term_id' => 21, 'name' => 'Archiv'],
    ];
});

it('copies featured image from final results template on create', function () {
    $output = new FinalResultsPublishedPost();

    $participants = [[
        'number' => 1,
        'name' => 'Max',
        'title' => 'FM',
        'twz' => 2000,
        'gender' => 'M',
        'club' => 'SV Musterstadt',
        'country' => 'DE',
    ]];

    $ranking = [[
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
    ]];

    $pairings = [[[
        'round' => 1,
        'board' => 1,
        'white_id' => 1,
        'white_name' => 'Max',
        'white_points' => '0.0',
        'black_id' => 2,
        'black_name' => 'Anna',
        'black_points' => '0.0',
        'result' => '1-0',
    ]]];

    $postId = $output->createFinalResultsNews($participants, $ranking, $pairings, 'Stadtmeisterschaft 2026');

    expect($postId)->toBeGreaterThan(0);
    expect($GLOBALS['wp_meta'][$postId]['_thumbnail_id'][0])->toBe('888');
    expect(isset($GLOBALS['wp_meta'][$postId]['_edit_lock']))->toBeFalse();
    expect($GLOBALS['wp_set_terms'][$postId]['category'])->toBe([21]);
});
