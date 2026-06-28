<?php

declare(strict_types=1);

namespace Tests\Unit\Helper;

/**
 * Dummy Parser Mocks
 */
class DummyRankingParser {
    public function parseRanking($html) {
        return [
            ['rank' => 1, 'name' => 'Max', 'twz' => 2000],
            ['rank' => 2, 'name' => 'Ann', 'twz' => 2100], // abgeschnitten
        ];
    }
}
