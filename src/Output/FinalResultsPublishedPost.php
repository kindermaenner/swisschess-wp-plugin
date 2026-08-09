<?php

declare(strict_types=1);

namespace SwissChess\Output;

class FinalResultsPublishedPost extends PublishedPostOutput
{
    public function createFinalResultsNews(array $participants, array $ranking, array $pairings, string $tournament_name = ''): int|\WP_Error
    {
        $tournamentLabel = $tournament_name !== '' ? $tournament_name : 'Turnier';

        $templateName = trim((string)get_option('swisschess_template_final_results_post', ''));
        if ($templateName === '') {
            return new \WP_Error(
                'no_final_results_template',
                'Kein Template in swisschess_template_final_results_post definiert.'
            );
        }

        $templatePost = $this->resolveTemplatePost($templateName);
        if (!$templatePost) {
            return new \WP_Error(
                'final_results_template_missing',
                sprintf('Template fuer den Ergebnis-Beitrag wurde nicht gefunden: "%s".', $templateName)
            );
        }

        $templateContent = (string)($templatePost->post_content ?? '');

        $lastRound = $this->detectLastRound($pairings);
        if ($lastRound < 5) {
            return new \WP_Error(
                'final_results_round_too_low',
                sprintf('Ergebnis-Beitrag wird erst ab Runde 5 erzeugt (aktuell: %d).', $lastRound)
            );
        }

        $replacements = [
            '{{tournament_name}}' => $tournamentLabel,
            '{{participants}}' => $this->participantsToHtmlTable($participants),
            '{{ranking}}' => $this->rankingToHtmlTable($ranking),
            '{{all_pairings}}' => $this->pairingsToHtmlTable($pairings),
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $templateContent);

        $title = sprintf('%s - Turnier beendet', $tournamentLabel);
        $postSlug = sanitize_title(str_replace(' ', '-', $tournamentLabel . '-turnier-beendet'));
        $metaKey = '_' . $postSlug . '_final_results_post';

        $postId = $this->createOrUpdatePostFromTemplate(
            $title,
            $postSlug,
            $metaKey,
            $content,
            (int)$templatePost->ID
        );

        return $postId;
    }

    private function detectLastRound(array $pairings): int
    {
        $maxRound = 0;

        foreach ($pairings as $roundPairings) {
            if (!is_array($roundPairings)) {
                continue;
            }

            foreach ($roundPairings as $pairing) {
                $round = (int)($pairing['round'] ?? 0);
                if ($round > $maxRound) {
                    $maxRound = $round;
                }
            }
        }

        return $maxRound;
    }

}
