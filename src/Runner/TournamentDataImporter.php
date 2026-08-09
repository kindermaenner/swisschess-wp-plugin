<?php

declare(strict_types=1);

namespace SwissChess\Runner;

use SwissChess\Parser\ParticipantsParser;
use SwissChess\Parser\PairingsParser;
use SwissChess\Parser\RankingParser;

class TournamentDataImporter
{
    public function findFiles(): array
    {
        $dir = WP_CONTENT_DIR . '/uploads/swisschess';

        if (defined('SWISSCHESS_TEST_MODE') && SWISSCHESS_TEST_MODE === true) {
            $dir = __DIR__ . '/../tests/data';
        }

        if (!is_dir($dir)) {
            return [];
        }

        return glob($dir . '/*.html') ?: [];
    }

    public function parseFiles(array $files, array &$participants, array &$ranking, array &$pairings, string &$tournamentName): void
    {
        foreach ($files as $file) {
            $html = file_get_contents($file);

            if ($html === false) {
                continue;
            }

            if (str_contains($file, 'teilrang')) {
                $ranking = (new RankingParser())->parseRanking($html);
            } elseif (str_contains($file, 'teil')) {
                $parser = new ParticipantsParser();
                $participants = $parser->parseParticipants($html);
                $tournamentName = $parser->extractTournamentName($html);
            } elseif (str_contains($file, 'paar')) {
                $pairings[] = (new PairingsParser())->parsePairings($html);
            }
        }
    }

    public function cleanupImportedFiles(array $files): void
    {
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
