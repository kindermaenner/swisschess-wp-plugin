<?php

declare(strict_types=1);

namespace Tests\Unit\Helper;

use SwissChess\Runner\SwissChessRunner;

class SwissChessRunnerScenarioTester extends SwissChessRunner
{
    public function __construct(private string $dataDir)
    {
    }

    protected function findFiles(): array
    {
        $files = glob($this->dataDir . '/*.html') ?: [];
        sort($files);
        return $files;
    }
}