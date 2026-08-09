<?php

declare(strict_types=1);

namespace SwissChess\Output;

class NextRoundPublishedPost extends PublishedPostOutput {

    public function createNextRoundNews(array $pairings, string $tournament_name = ''): int|\WP_Error
    {
        if (empty($pairings)) {
            return 0;
        }

        $actualRoundNo = $this->detectMaxRoundNumber($pairings);
        if ($actualRoundNo === 0) {
            return 0;
        }

        $actualRoundPairings = $this->extractRoundByNumber($pairings, $actualRoundNo);
        $lastRoundNo = max(0, $actualRoundNo - 1);
        $lastRoundPairings = $lastRoundNo > 0
            ? $this->extractRoundByNumber($pairings, $lastRoundNo)
            : [];

        $tournamentLabel = $tournament_name !== '' ? $tournament_name : 'Turnier';
        $title = sprintf('%s - Runde %d ausgelost', $tournamentLabel, $actualRoundNo);
        $postSlug = sanitize_title(str_replace(' ', '-', $tournamentLabel . '-runde-' . $actualRoundNo . '-ausgelost'));
        $metaKey = '_' . $postSlug . '_next_round_post';

        $templateName = trim((string)get_option('swisschess_template_next_round_post', ''));
        if ($templateName === '') {
            return new \WP_Error(
                'no_next_round_template',
                'Kein Template in swisschess_template_next_round_post definiert.'
            );
        }

        $templatePage = $this->resolveTemplatePost($templateName);
        if (!$templatePage) {
            return new \WP_Error(
                'next_round_template_missing',
                sprintf(
                    'Template fuer den Beitrag zur neu ausgelosten Runde wurde nicht gefunden: "%s".',
                    $templateName
                )
            );
        }

        $templateContent = (string)($templatePage->post_content ?? '');

        $replacements = [
            '{{tournament_name}}' => $tournament_name,
            '{{round_no_actual}}' => (string)$actualRoundNo,
            '{{round_no_last}}' => $lastRoundNo > 0 ? (string)$lastRoundNo : '',
            '{{pairings_actual_round}}' => $this->pairingsToHtmlTable([$actualRoundPairings]),
            '{{pairings_last_round}}' => $lastRoundNo > 0 && !empty($lastRoundPairings)
                ? $this->pairingsToHtmlTable([$lastRoundPairings])
                : '<p>Keine vorherige Runde vorhanden.</p>',
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $templateContent);

        $postId = $this->createOrUpdatePostFromTemplate(
            $title,
            $postSlug,
            $metaKey,
            $content,
            (int)$templatePage->ID
        );

        return $postId;
    }

    private function detectMaxRoundNumber(array $pairings): int
    {
        $maxRound = 0;

        foreach ($pairings as $round) {
            if (empty($round) || !isset($round[0]['round'])) {
                continue;
            }

            $roundNo = (int)$round[0]['round'];
            if ($roundNo > $maxRound) {
                $maxRound = $roundNo;
            }
        }

        return $maxRound;
    }

    private function extractRoundByNumber(array $pairings, int $roundNo): array
    {
        foreach ($pairings as $round) {
            if (!empty($round) && (int)($round[0]['round'] ?? 0) === $roundNo) {
                return $round;
            }
        }

        return [];
    }

}
