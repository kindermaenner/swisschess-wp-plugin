<?php

declare(strict_types=1);

namespace Tests\Unit\Helper;

/**
 * Dummy Parser Mocks
 */
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