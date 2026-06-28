<?php

declare(strict_types=1);

namespace SwissChess\Output;

class StaticTournamentPage extends WordpressOutput {
    
    public function createOrUpdateStaticPage(array $participants, array $ranking, array $pairings, string $tournament_name): int|\WP_Error
    {
        // 1. Template laden
        $template_name = get_option('swisschess_template_static_page');
        if (!$template_name) {
            return new \WP_Error('no_template', 'Kein Template in swisschess_template_static_page definiert.');
        }

        $template_page = get_page_by_title($template_name, OBJECT, 'page');
        if (!$template_page) {
            return new \WP_Error(
                'template_missing',
                sprintf('Template-Seite wurde nicht gefunden: "%s".', (string)$template_name)
            );
        }

        $template_content = $template_page->post_content;

        // 2. Platzhalter ersetzen
        $replacements = [
            '{{tournament_name}}' => $tournament_name,
            '{{participants}}'    => $this->participantsToHtmlTable($participants) ?? '',
            '{{ranking}}'         => $this->rankingToHtmlTable($ranking) ?? '',
            '{{all_pairings}}'    => $this->pairingsToHtmlTable($pairings) ?? '',
        ];

        $final_content = str_replace(array_keys($replacements), array_values($replacements), $template_content);

        // 3. Slug & Titel erzeugen
        $slug = sanitize_title(str_replace(' ', '-', $tournament_name));
        $title = $slug;

        // 4. Meta-Key erzeugen
        $meta_key = '_' . $slug . '_static_page';

        // 5. Prüfen, ob Seite existiert
        $existing = get_posts([
            'post_type'  => 'page',
            'meta_key'   => $meta_key,
            'meta_value' => '1',
            'numberposts'=> 1,
        ]);

        if ($existing) {
            // Update bestehende Seite
            $page_id = $existing[0]->ID;

            wp_update_post([
                'ID'           => $page_id,
                'post_title'   => $title,
                'post_name'    => $slug,
                'post_content' => $final_content,
            ]);

            $this->copyAllMeta($template_page->ID, $page_id);

            return $page_id;
        }

        // 6. Neue Seite erstellen
        $page_id = wp_insert_post([
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_content' => $final_content,
            'post_author'  => get_option('swisschess_author') ?: 1,
        ]);

        $this->copyAllMeta($template_page->ID, $page_id);

        // 7. Meta-Key setzen
        update_post_meta($page_id, $meta_key, '1');

        return $page_id;
    }
}