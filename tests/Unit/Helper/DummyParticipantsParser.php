<?php

declare(strict_types=1);

namespace Tests\Unit\Helper;

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