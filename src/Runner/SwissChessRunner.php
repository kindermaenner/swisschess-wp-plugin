<?php

declare(strict_types=1);

namespace SwissChess\Runner;

use SwissChess\Parser\ParticipantsParser;
use SwissChess\Parser\RankingParser;
use SwissChess\Parser\PairingsParser;
use SwissChess\Output\StaticTournamentPage;
use SwissChess\Output\NextRoundPublishedPost;

class SwissChessRunner
{
    private array $participants = [];
    private array $ranking = [];
    private array $pairings = [];
    private string $tournament_name = '';

    public function run(): array
    {
        $files = $this->findFiles();

        if (empty($files)) {
            return [
                'success' => false,
                'message' => 'No files found',
            ];
        }

        $this->parseFiles($files);

        $staticPageUpdater = new StaticTournamentPage();
        $post_id = $staticPageUpdater->createOrUpdateStaticPage($this->participants, $this->ranking, $this->pairings, $this->tournament_name);

        // 1) Unfertige Runde → neue Auslosung veröffentlichen
        if ($this->shouldPublishNextRound($this->pairings)) {
            $nextRoundPost = new NextRoundPublishedPost();
            $nextRoundPost->createNextRoundNews($this->pairings);
        }

        // 2) Alle Runden fertig → Gesamtergebnis veröffentlichen
        if ($this->allRoundsComplete($this->pairings)) {
           //$this->createWinnerNews($this->pairings, $this->participants);
        }

        //$this->cleanup($files);

        return [
            'success'     => true,
            'post_id'     => $post_id,
            'participants'=> $this->participants,
            'ranking'     => $this->ranking,
            'pairings'    => $this->pairings,
        ];
    }

    protected function findFiles(): array
    {
        // Für WordPress
        $dir = WP_CONTENT_DIR . '/uploads/swisschess';

        // Für Tests
        if (defined('SWISSCHESS_TEST_MODE') && SWISSCHESS_TEST_MODE === true) {
            $dir = __DIR__ . '/../tests/data';
        }

        if (!is_dir($dir)) {
            return [];
        }

        return glob($dir . '/*.html') ?: [];
    }

    protected function parseFiles(array $files): void
    {
        foreach ($files as $file) {
            $html = file_get_contents($file);

            if (str_contains($file, 'teilrang')) {
                $this->ranking = (new RankingParser())->parseRanking($html);
            }
            elseif (str_contains($file, 'teil')) {
                $parser = new ParticipantsParser();
                $this->participants = $parser->parseParticipants($html);
                $this->tournament_name = $parser->extractTournamentName($html);
            }
            elseif (str_contains($file, 'paar')) {
                $this->pairings[] = (new PairingsParser())->parsePairings($html);
            }
        }

        $this->ranking = $this->fixRankingNames($this->ranking, $this->participants);
        $this->pairings = $this->fixPairingNames($this->pairings, $this->participants);
    }

    protected function shouldPublishNextRound(array $pairings): bool
    {
        if (empty($pairings)) {
            return false;
        }

        // 1. Höchste Rundennummer finden
        $maxRound = 0;

        foreach ($pairings as $round) {
            foreach ($round as $board) {
                $roundNumber = (int)($board['round'] ?? 0);
                if ($roundNumber > $maxRound) {
                    $maxRound = $roundNumber;
                }
            }
        }

        if ($maxRound === 0) {
            return false;
        }

        // 2. Runde mit maxRound extrahieren
        $lastRound = [];

        foreach ($pairings as $round) {
            if (!empty($round) && (int)$round[0]['round'] === $maxRound) {
                $lastRound = $round;
                break;
            }
        }

        if (empty($lastRound)) {
            return false;
        }

        // 3. Prüfen, ob diese Runde keine Ergebnisse hat
        return $this->roundHasNoResults($lastRound);
    }

    protected function roundHasResults(array $round): bool
    {
        foreach ($round as $board) {
            if (!empty($board['result']) && $board['result'] !== '-') {
                return true;
            }
        }
        return false;
    }

    protected function roundHasNoResults(array $round): bool
    {
        foreach ($round as $board) {
            if (!empty($board['result']) && $board['result'] !== '-') {
                return false;
            }
        }
        return true;
    }

    protected function allRoundsComplete(array $pairings): bool
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

    protected function buildParticipantIndexByTwz(array $participants): array
    {
        $index = [];

        foreach ($participants as $p) {
            // Schlüssel: TWZ + normalisierter Name
            $key = $p['twz'] . '|' . strtolower($p['name']);
            $index[$key] = $p['name'];
        }

        return $index;
    }

    protected function buildParticipantIndexById(array $participants): array
    {
        $index = [];

        foreach ($participants as $p) {
            if (!isset($p['number']) || !isset($p['name'])) {
                continue;
            }

            $key = (int)$p['number'];
            $index[$key] = $p['name'];
        }

        return $index;
    }

    protected function fixPairingNames(array $pairings, array $participants): array
    {
        // Teilnehmer-Index: number → name
        $index = [];
        foreach ($participants as $p) {
            if (isset($p['number'], $p['name'])) {
                $index[(int)$p['number']] = $p['name'];
            }
        }

        // 2D-Struktur durchlaufen
        foreach ($pairings as $roundIndex => $roundPairings) {
            foreach ($roundPairings as $i => $p) {

                $whiteId = (int)($p['white_id'] ?? 0);
                if (isset($index[$whiteId])) {
                    $pairings[$roundIndex][$i]['white_name'] = $index[$whiteId];
                }

                $blackId = (int)($p['black_id'] ?? 0);
                if (isset($index[$blackId])) {
                    $pairings[$roundIndex][$i]['black_name'] = $index[$blackId];
                }
            }
        }

        return $pairings;
    }

    protected function fixRankingNames(array $ranking, array $participants): array
    {
        $index = $this->buildParticipantIndexByTwz($participants);

        foreach ($ranking as &$r) {

            $key = $r['twz'] . '|' . strtolower($r['name']);

            // Wenn SwissChess den Namen abgeschnitten hat
            foreach ($index as $idxKey => $fullName) {
                // Match: gleiche TWZ + abgeschnittener Anfang
                if (str_starts_with($idxKey, $r['twz'] . '|' . strtolower($r['name']))) {
                    $r['name'] = $fullName;
                    break;
                }
            }
        }

        return $ranking;
    }

    protected function groupPairingsByRound(array $pairings): array
    {
        $grouped = [];

        foreach ($pairings as $p) {
            $round = $p['round'] ?? 1;
            $grouped[$round][] = $p;
        }

        // Runden absteigend sortieren (letzte Runde zuerst)
        krsort($grouped);

        return $grouped;
    }

}