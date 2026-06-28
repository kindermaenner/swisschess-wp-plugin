<?php

declare(strict_types=1);

namespace Tests\Unit\Helper;

use SwissChess\Output\StaticTournamentPage;

/**
 * Testklasse
 */
class StaticTournamentPageTester extends StaticTournamentPage
{
    public function callCreate(array $participants, array $ranking, array $pairings, string $name)
    {
        return $this->createOrUpdateStaticPage($participants, $ranking, $pairings, $name);
    }
}