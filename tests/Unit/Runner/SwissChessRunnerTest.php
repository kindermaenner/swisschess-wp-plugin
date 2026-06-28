<?php

declare(strict_types=1);

use SwissChess\Runner\SwissChessRunner;

/**
 * Dummy Parser Mocks
 */
class DummyParticipantsParser {
    public function parseParticipants($html) {
        return [
            ['number' => 1, 'name' => 'Max', 'twz' => 2000],
            ['number' => 2, 'name' => 'Anna', 'twz' => 2100],
        ];
    }
    public function extractTournamentName($html) {
        return 'Testturnier';
    }
}

class DummyRankingParser {
    public function parseRanking($html) {
        return [
            ['rank' => 1, 'name' => 'Max', 'twz' => 2000],
            ['rank' => 2, 'name' => 'Ann', 'twz' => 2100], // abgeschnitten
        ];
    }
}

class DummyPairingsParser {
    public function parsePairings($html) {
        return [
            [
                'round' => 1,
                'board' => 1,
                'white_id' => 1,
                'black_id' => 2,
                'result' => '-'
            ]
        ];
    }
}

/**
 * Override Runner to inject mocks
 */
class SwissChessRunnerTester extends SwissChessRunner
{
    public function testShouldPublishNextRound(array $pairings) {
        return $this->shouldPublishNextRound($pairings);
    }

    public function testRoundHasResults(array $round) {
        return $this->roundHasResults($round);
    }

    public function testRoundHasNoResults(array $round) {
        return $this->roundHasNoResults($round);
    }

    public function testAllRoundsComplete(array $pairings) {
        return $this->allRoundsComplete($pairings);
    }

    public function testFixPairingNames(array $pairings, array $participants) {
        return $this->fixPairingNames($pairings, $participants);
    }

    public function testFixRankingNames(array $ranking, array $participants) {
        return $this->fixRankingNames($ranking, $participants);
    }

    public function testGroupPairingsByRound(array $pairings) {
        return $this->groupPairingsByRound($pairings);
    }
}

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
