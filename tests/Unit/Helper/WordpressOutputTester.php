<?php

declare(strict_types=1);

namespace Tests\Unit\Helper;

use SwissChess\Output\WordpressOutput;

/**
 * Test-Wrapper für protected Methoden
 */
class WordpressOutputTester extends WordpressOutput
{
    public function participants(array $p): string
    {
        return $this->participantsToHtmlTable($p);
    }

    public function ranking(array $r): string
    {
        return $this->rankingToHtmlTable($r);
    }

    public function pairings(array $p): string
    {
        return $this->pairingsToHtmlTable($p);
    }

    public function copyMeta(int $from, int $to)
    {
        return $this->copyAllMeta($from, $to);
    }
}

