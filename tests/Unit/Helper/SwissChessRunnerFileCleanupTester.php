<?php

declare(strict_types=1);

namespace Tests\Unit\Helper;

use Tests\Unit\Helper\SwissChessRunnerRunTester;

class SwissChessRunnerFileCleanupTester extends SwissChessRunnerRunTester
{
    public function __construct(private array $files, array $fixture)
    {
        parent::__construct($files, $fixture);
    }

    protected function findFiles(): array
    {
        return $this->files;
    }
}