<?php

declare(strict_types=1);

namespace SwissChess\Runner;

use SwissChess\Output\StaticTournamentPage;
use SwissChess\Output\NextRoundPublishedPost;
use SwissChess\Output\FinalResultsPublishedPost;

class SwissChessRunner
{
    private array $participants = [];
    private array $ranking = [];
    private array $pairings = [];
    private string $tournament_name = '';
    private TournamentDataImporter $importer;
    private TournamentStateEvaluator $stateEvaluator;
    private ParticipantNameResolver $nameResolver;

    public function __construct()
    {
        $this->importer = new TournamentDataImporter();
        $this->stateEvaluator = new TournamentStateEvaluator();
        $this->nameResolver = new ParticipantNameResolver();
    }

    public function run(): array
    {
        $warnings = [];

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

        if ($post_id instanceof \WP_Error) {
            return [
                'success' => false,
                'message' => $post_id->get_error_message(),
                'error_code' => $post_id->get_error_code(),
                'participants'=> $this->participants,
                'ranking'     => $this->ranking,
                'pairings'    => $this->pairings,
                'warnings'    => $warnings,
            ];
        }

        // 1) Unfertige Runde → neue Auslosung veröffentlichen
        if ($this->shouldPublishNextRound($this->pairings)) {
            $nextRoundPost = new NextRoundPublishedPost();
            $nextRoundResult = $nextRoundPost->createNextRoundNews($this->pairings, $this->tournament_name);

            if ($nextRoundResult instanceof \WP_Error) {
                $warnings[] = $nextRoundResult->get_error_message();
            }
        }

        // 2) Alle Runden fertig → Gesamtergebnis veröffentlichen
        if ($this->allRoundsComplete($this->pairings)) {
            $finalResultsPost = new FinalResultsPublishedPost();
            $finalResults = $finalResultsPost->createFinalResultsNews(
                $this->participants,
                $this->ranking,
                $this->pairings,
                $this->tournament_name
            );

            if ($finalResults instanceof \WP_Error) {
                $warnings[] = $finalResults->get_error_message();
            }
        }

        if (get_option('swisschess_delete_after_import')) {
            $this->cleanupImportedFiles($files);
        }

        return [
            'success'     => true,
            'post_id'     => $post_id,
            'participants'=> $this->participants,
            'ranking'     => $this->ranking,
            'pairings'    => $this->pairings,
            'warnings'    => $warnings,
        ];
    }

    protected function findFiles(): array
    {
        return $this->importer->findFiles();
    }

    protected function parseFiles(array $files): void
    {
        $this->importer->parseFiles($files, $this->participants, $this->ranking, $this->pairings, $this->tournament_name);

        $this->ranking = $this->fixRankingNames($this->ranking, $this->participants);
        $this->pairings = $this->fixPairingNames($this->pairings, $this->participants);
    }

    protected function shouldPublishNextRound(array $pairings): bool
    {
        return $this->stateEvaluator->shouldPublishNextRound($pairings);
    }

    protected function roundHasResults(array $round): bool
    {
        return $this->stateEvaluator->roundHasResults($round);
    }

    protected function roundHasNoResults(array $round): bool
    {
        return $this->stateEvaluator->roundHasNoResults($round);
    }

    protected function allRoundsComplete(array $pairings): bool
    {
        return $this->stateEvaluator->allRoundsComplete($pairings);
    }

    protected function fixPairingNames(array $pairings, array $participants): array
    {
        return $this->nameResolver->fixPairingNames($pairings, $participants);
    }

    protected function fixRankingNames(array $ranking, array $participants): array
    {
        return $this->nameResolver->fixRankingNames($ranking, $participants);
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

    protected function cleanupImportedFiles(array $files): void
    {
        $this->importer->cleanupImportedFiles($files);
    }

}
