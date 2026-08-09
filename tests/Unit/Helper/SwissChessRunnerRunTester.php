<?php

declare(strict_types=1);

namespace Tests\Unit\Helper;

use SwissChess\Runner\SwissChessRunner;

class SwissChessRunnerRunTester extends SwissChessRunner
{
    public function __construct(private array $files, private array $fixture)
    {
        parent::__construct();
    }

    protected function findFiles(): array
    {
        return $this->files;
    }

    protected function parseFiles(array $files): void
    {
        $ref = new \ReflectionClass(SwissChessRunner::class);

        foreach (['participants', 'ranking', 'pairings', 'tournament_name'] as $prop) {
            if (!$ref->hasProperty($prop)) {
                continue;
            }

            $property = $ref->getProperty($prop);
            $property->setValue($this, $this->fixture[$prop]);
        }
    }
}