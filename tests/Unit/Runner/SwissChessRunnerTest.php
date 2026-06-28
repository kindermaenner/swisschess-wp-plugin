<?php

declare(strict_types=1);

use Tests\Unit\Helper\DummyPairingsParser;
use Tests\Unit\Helper\DummyRankingParser;
use Tests\Unit\Helper\DummyParticipantsParser;
use Tests\Unit\Helper\SwissChessRunnerTester;

# ---------------------------------------------------------
# shouldPublishNextRound()
# ---------------------------------------------------------

it('returns false when pairings empty', function () {
    $r = new SwissChessRunnerTester();
    expect($r->testShouldPublishNextRound([]))->toBeFalse();
});

it('returns true when last round has no results', function () {
    $r = new SwissChessRunnerTester();

    $pairings = [
        [
            ['round' => 1, 'result' => '-']
        ],
        [
            ['round' => 2, 'result' => '-']
        ]
    ];

    expect($r->testShouldPublishNextRound($pairings))->toBeTrue();
});

it('returns true when last round only has bye results and dashes', function () {
    $r = new SwissChessRunnerTester();

    $pairings = [
        [
            ['round' => 1, 'result' => '1-0']
        ],
        [
            ['round' => 2, 'result' => '+ - -'],
            ['round' => 2, 'result' => '-']
        ]
    ];

    expect($r->testShouldPublishNextRound($pairings))->toBeTrue();
});

it('returns false when last round has results', function () {
    $r = new SwissChessRunnerTester();

    $pairings = [
        [
            ['round' => 1, 'result' => '-']
        ],
        [
            ['round' => 2, 'result' => '1-0']
        ]
    ];

    expect($r->testShouldPublishNextRound($pairings))->toBeFalse();
});

# ---------------------------------------------------------
# roundHasResults()
# ---------------------------------------------------------

it('roundHasResults returns true when any board has result', function () {
    $r = new SwissChessRunnerTester();

    $round = [
        ['result' => '-'],
        ['result' => '1-0']
    ];

    expect($r->testRoundHasResults($round))->toBeTrue();
});

it('roundHasResults returns false when no board has result', function () {
    $r = new SwissChessRunnerTester();

    $round = [
        ['result' => '-'],
        ['result' => '-']
    ];

    expect($r->testRoundHasResults($round))->toBeFalse();
});

# ---------------------------------------------------------
# roundHasNoResults()
# ---------------------------------------------------------

it('roundHasNoResults returns true when all boards have no result', function () {
    $r = new SwissChessRunnerTester();

    $round = [
        ['result' => '-'],
        ['result' => '-']
    ];

    expect($r->testRoundHasNoResults($round))->toBeTrue();
});

it('roundHasNoResults returns false when any board has result', function () {
    $r = new SwissChessRunnerTester();

    $round = [
        ['result' => '-'],
        ['result' => '1-0']
    ];

    expect($r->testRoundHasNoResults($round))->toBeFalse();
});

# ---------------------------------------------------------
# allRoundsComplete()
# ---------------------------------------------------------

it('allRoundsComplete returns false when any board has no result', function () {
    $r = new SwissChessRunnerTester();

    $pairings = [
        [
            ['result' => '1-0'],
            ['result' => '-']
        ]
    ];

    expect($r->testAllRoundsComplete($pairings))->toBeFalse();
});

it('allRoundsComplete returns true when all boards have results', function () {
    $r = new SwissChessRunnerTester();

    $pairings = [
        [
            ['result' => '1-0'],
            ['result' => '0-1']
        ]
    ];

    expect($r->testAllRoundsComplete($pairings))->toBeTrue();
});

# ---------------------------------------------------------
# fixPairingNames()
# ---------------------------------------------------------

it('fixPairingNames replaces white_name and black_name correctly', function () {
    $r = new SwissChessRunnerTester();

    $participants = [
        ['number' => 1, 'name' => 'Max'],
        ['number' => 2, 'name' => 'Anna'],
    ];

    $pairings = [
        [
            ['white_id' => 1, 'black_id' => 2]
        ]
    ];

    $fixed = $r->testFixPairingNames($pairings, $participants);

    expect($fixed[0][0]['white_name'])->toBe('Max');
    expect($fixed[0][0]['black_name'])->toBe('Anna');
});

# ---------------------------------------------------------
# fixRankingNames()
# ---------------------------------------------------------

it('fixRankingNames restores truncated names based on TWZ', function () {
    $r = new SwissChessRunnerTester();

    $participants = [
        ['twz' => 2000, 'name' => 'Max Mustermann'],
        ['twz' => 2100, 'name' => 'Anna Beispiel'],
    ];

    $ranking = [
        ['twz' => 2100, 'name' => 'Ann'], // abgeschnitten
    ];

    $fixed = $r->testFixRankingNames($ranking, $participants);

    expect($fixed[0]['name'])->toBe('Anna Beispiel');
});

# ---------------------------------------------------------
# groupPairingsByRound()
# ---------------------------------------------------------

it('groupPairingsByRound groups and sorts rounds descending', function () {
    $r = new SwissChessRunnerTester();

    $pairings = [
        ['round' => 1],
        ['round' => 3],
        ['round' => 2],
    ];

    $grouped = $r->testGroupPairingsByRound($pairings);

    expect(array_keys($grouped))->toBe([3, 2, 1]);
});
