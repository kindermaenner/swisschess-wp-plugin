<?php

declare(strict_types=1);

namespace SwissChess\Runner;

class TournamentStateEvaluator
{
    public function shouldPublishNextRound(array $pairings): bool
    {
        if (empty($pairings)) {
            return false;
        }

        $maxRound = $this->findMaxRound($pairings);
        if ($maxRound === 0) {
            return false;
        }

        $lastRound = $this->findRound($pairings, $maxRound);
        if (empty($lastRound)) {
            return false;
        }

        return $this->roundHasNoResults($lastRound);
    }

    public function roundHasResults(array $round): bool
    {
        foreach ($round as $board) {
            if (!empty($board['result']) && $board['result'] !== '-') {
                return true;
            }
        }

        return false;
    }

    public function roundHasNoResults(array $round): bool
    {
        foreach ($round as $board) {
            $result = trim((string)($board['result'] ?? ''));

            if ($result !== '' && $result !== '-' && !$this->isByeResult($result)) {
                return false;
            }
        }

        return true;
    }

    public function allRoundsComplete(array $pairings): bool
    {
        foreach ($pairings as $round) {
            foreach ($round as $board) {
                if (empty($board['result']) || $board['result'] === '-') {
                    return false;
                }
            }
        }

        return true;
    }

    private function findMaxRound(array $pairings): int
    {
        $maxRound = 0;

        foreach ($pairings as $round) {
            foreach ($round as $board) {
                $roundNumber = (int)($board['round'] ?? 0);
                if ($roundNumber > $maxRound) {
                    $maxRound = $roundNumber;
                }
            }
        }

        return $maxRound;
    }

    private function findRound(array $pairings, int $roundNumber): array
    {
        foreach ($pairings as $round) {
            if (!empty($round) && (int)$round[0]['round'] === $roundNumber) {
                return $round;
            }
        }

        return [];
    }

    private function isByeResult(string $result): bool
    {
        return preg_match('/^[+\-\s]+$/', $result) === 1 && str_contains($result, '+');
    }
}
