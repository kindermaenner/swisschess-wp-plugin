<?php

declare(strict_types=1);

namespace Tests\Unit\Helper;

use SwissChess\Runner\SwissChessRunner;

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
