<?php

declare(strict_types=1);

use SwissChess\Output\NextRoundPublishedPost;

beforeEach(function () {
    $GLOBALS['wp_options'] = [
        'swisschess_template_next_round_post' => 'NextRoundTemplate',
        'swisschess_author' => 2,
    ];

    $GLOBALS['wp_pages'] = [
        [
            'ID' => 300,
            'post_title' => 'NextRoundTemplate',
            'post_name' => 'next-round-template',
            'post_content' => 'Turnier: {{tournament_name}} | Aktuell: {{round_no_actual}} | Letzte: {{round_no_last}} {{pairings_actual_round}} {{pairings_last_round}}',
            'meta' => [],
        ],
    ];

    $GLOBALS['wp_meta'] = [
        300 => [
            'layout' => ['fullwidth'],
            '_edit_lock' => ['12345'],
        ],
    ];

    $GLOBALS['wp_inserted'] = [];
    $GLOBALS['wp_updated'] = [];
});

it('creates next round post from template with placeholders and copies allowed meta', function () {
    $output = new NextRoundPublishedPost();

    $pairings = [
        [
            [
                'round' => 1,
                'board' => 1,
                'white_id' => 1,
                'white_name' => 'Max',
                'white_points' => '1.0',
                'black_id' => 2,
                'black_name' => 'Anna',
                'black_points' => '0.0',
                'result' => '1-0',
            ],
        ],
        [
            [
                'round' => 2,
                'board' => 1,
                'white_id' => 3,
                'white_name' => 'Chris',
                'white_points' => '0.0',
                'black_id' => 4,
                'black_name' => 'Lea',
                'black_points' => '0.0',
                'result' => '-',
            ],
        ],
    ];

    $postId = $output->createNextRoundNews($pairings, 'Stadtmeisterschaft 2026');

    expect($postId)->toBeGreaterThan(0);
    expect($GLOBALS['wp_inserted'])->toHaveCount(1);

    $insert = $GLOBALS['wp_inserted'][0];

    expect($insert['post_title'])->toContain('Stadtmeisterschaft 2026');
    expect($insert['post_title'])->toContain('Runde 2');
    expect($insert['post_content'])->toContain('Turnier: Stadtmeisterschaft 2026');
    expect($insert['post_content'])->toContain('Aktuell: 2');
    expect($insert['post_content'])->toContain('Letzte: 1');
    expect($insert['post_content'])->toContain('Runde 2');
    expect($insert['post_content'])->toContain('Runde 1');
    expect($insert['post_content'])->not->toContain('Viel Erfolg allen Teilnehmern!');

    expect($GLOBALS['wp_meta'][$postId]['layout'][0])->toBe('fullwidth');
    expect(isset($GLOBALS['wp_meta'][$postId]['_edit_lock']))->toBeFalse();
});

it('updates existing next round post when marker meta key already exists', function () {
    $output = new NextRoundPublishedPost();

    $pairings = [
        [
            [
                'round' => 1,
                'board' => 1,
                'white_id' => 1,
                'white_name' => 'Max',
                'white_points' => '1.0',
                'black_id' => 2,
                'black_name' => 'Anna',
                'black_points' => '0.0',
                'result' => '1-0',
            ],
        ],
        [
            [
                'round' => 2,
                'board' => 1,
                'white_id' => 3,
                'white_name' => 'Chris',
                'white_points' => '0.0',
                'black_id' => 4,
                'black_name' => 'Lea',
                'black_points' => '0.0',
                'result' => '-',
            ],
        ],
    ];

    $existingId = 901;
    $slug = 'stadtmeisterschaft-2026-runde-2-ausgelost';
    $metaKey = '_' . $slug . '_next_round_post';

    $GLOBALS['wp_pages'][] = [
        'ID' => $existingId,
        'post_title' => 'Alt',
        'post_name' => $slug,
        'post_content' => 'ALT',
        'post_type' => 'post',
        'meta' => [],
    ];
    $GLOBALS['wp_meta'][$existingId][$metaKey] = ['1'];

    $postId = $output->createNextRoundNews($pairings, 'Stadtmeisterschaft 2026');

    expect($postId)->toBe($existingId);
    expect($GLOBALS['wp_inserted'])->toHaveCount(0);
    expect($GLOBALS['wp_updated'])->toHaveCount(1);
    expect($GLOBALS['wp_updated'][0]['ID'])->toBe($existingId);
    expect($GLOBALS['wp_updated'][0]['post_name'])->toBe($slug);
    expect($GLOBALS['wp_updated'][0]['post_content'])->toContain('Turnier: Stadtmeisterschaft 2026');

    expect($GLOBALS['wp_meta'][$postId]['layout'][0])->toBe('fullwidth');
    expect(isset($GLOBALS['wp_meta'][$postId]['_edit_lock']))->toBeFalse();
});

it('returns error when next round template is not configured', function () {
    $output = new NextRoundPublishedPost();

    $GLOBALS['wp_options']['swisschess_template_next_round_post'] = '';

    $pairings = [
        [
            [
                'round' => 2,
                'board' => 1,
                'white_id' => 3,
                'white_name' => 'Chris',
                'white_points' => '0.0',
                'black_id' => 4,
                'black_name' => 'Lea',
                'black_points' => '0.0',
                'result' => '-',
            ],
        ],
    ];

    $result = $output->createNextRoundNews($pairings, 'Stadtmeisterschaft 2026');

    expect($result)->toBeInstanceOf(WP_Error::class);
    expect($result->get_error_code())->toBe('no_next_round_template');
    expect($GLOBALS['wp_inserted'])->toHaveCount(0);
});

it('returns error when configured next round template cannot be found', function () {
    $output = new NextRoundPublishedPost();

    $GLOBALS['wp_options']['swisschess_template_next_round_post'] = 'NichtVorhanden';

    $pairings = [
        [
            [
                'round' => 2,
                'board' => 1,
                'white_id' => 3,
                'white_name' => 'Chris',
                'white_points' => '0.0',
                'black_id' => 4,
                'black_name' => 'Lea',
                'black_points' => '0.0',
                'result' => '-',
            ],
        ],
    ];

    $result = $output->createNextRoundNews($pairings, 'Stadtmeisterschaft 2026');

    expect($result)->toBeInstanceOf(WP_Error::class);
    expect($result->get_error_code())->toBe('next_round_template_missing');
    expect($GLOBALS['wp_inserted'])->toHaveCount(0);
});
