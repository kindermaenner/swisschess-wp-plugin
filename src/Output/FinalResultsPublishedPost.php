<?php

declare(strict_types=1);

namespace SwissChess\Output;

class FinalResultsPublishedPost extends WordpressOutput
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

        $existing = get_posts([
            'post_type' => 'post',
            'meta_key' => $metaKey,
            'meta_value' => '1',
            'numberposts' => 1,
        ]);

        if ($existing) {
            $postId = (int)$existing[0]->ID;

            wp_update_post([
                'ID' => $postId,
                'post_title' => $title,
                'post_name' => $postSlug,
                'post_content' => $content,
                'post_status' => 'publish',
                'post_author' => get_option('swisschess_author') ?: 1,
            ]);
        } else {
            $postId = wp_insert_post([
                'post_title' => $title,
                'post_name' => $postSlug,
                'post_content' => $content,
                'post_status' => 'publish',
                'post_type' => 'post',
                'post_author' => get_option('swisschess_author') ?: 1,
            ]);

            if ($postId > 0) {
                update_post_meta($postId, $metaKey, '1');
            }
        }

        if ($postId > 0) {
            $this->copyAllMeta((int)$templatePost->ID, $postId);
            $this->copyCategoriesWithoutTemplateCategory((int)$templatePost->ID, $postId);
        }

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

    private function resolveTemplatePost(string $templateName)
    {
        if ($templateName === '') {
            return null;
        }

        $templatePost = get_page_by_title($templateName, OBJECT, 'page');
        if ($templatePost) {
            return $templatePost;
        }

        $templatePost = get_page_by_title($templateName, OBJECT, 'post');
        if ($templatePost) {
            return $templatePost;
        }

        if (function_exists('get_page_by_path')) {
            $slug = sanitize_title($templateName);

            $templatePost = get_page_by_path($slug, OBJECT, 'page');
            if ($templatePost) {
                return $templatePost;
            }

            $templatePost = get_page_by_path($slug, OBJECT, 'post');
            if ($templatePost) {
                return $templatePost;
            }
        }

        return null;
    }
}
