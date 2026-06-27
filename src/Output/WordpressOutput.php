<?php

declare(strict_types=1);

namespace SwissChess\Output;

if (!defined('ABSPATH')) {
    exit;
}

class WordpressOutput
{
    protected function participantsToHtmlTable(array $participants): string
    {
        if (empty($participants)) {
            return '<p>Keine Teilnehmerdaten vorhanden.</p>';
        }

        // Tabellenkopf
        $html  = '<table>';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Nr</th>';
        $html .= '<th>Name</th>';
        $html .= '<th>Titel</th>';
        $html .= '<th>TWZ</th>';
        $html .= '<th>Geschlecht</th>';
        $html .= '<th>Verein</th>';
        $html .= '<th>Land</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        // Tabellenkörper
        $html .= '<tbody>';

        foreach ($participants as $p) {
            $html .= '<tr>';
            $html .= '<td>' . esc_html($p['number']) . '</td>';
            $html .= '<td>' . esc_html($p['name']) . '</td>';
            $html .= '<td>' . esc_html($p['title']) . '</td>';
            $html .= '<td>' . esc_html($p['twz']) . '</td>';
            $html .= '<td>' . esc_html($p['gender']) . '</td>';
            $html .= '<td>' . esc_html($p['club']) . '</td>';
            $html .= '<td>' . esc_html($p['country']) . '</td>';
            $html .= '</tr>';
        }
                
        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    protected function rankingToHtmlTable(array $ranking): string
    {
        if (empty($ranking)) {
            return '<p>Keine Ranglistendaten vorhanden.</p>';
        }

        $html  = '<table>';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Rang</th>';
        $html .= '<th>Name</th>';
        $html .= '<th>Titel</th>';
        $html .= '<th>TWZ</th>';
        $html .= '<th>Geschlecht</th>';
        $html .= '<th>Punkte</th>';
        $html .= '<th>Siege</th>';
        $html .= '<th>Remis</th>';
        $html .= '<th>Niederl.</th>';
        $html .= '<th>Buchholz</th>';
        $html .= '<th>Sonneborn</th>';
        $html .= '<th>Verein</th>';
        $html .= '<th>Land</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($ranking as $r) {
            $html .= '<tr>';
            $html .= '<td>' . esc_html($r['rank']) . '</td>';
            $html .= '<td>' . esc_html($r['name']) . '</td>';
            $html .= '<td>' . esc_html($r['title']) . '</td>';
            $html .= '<td>' . esc_html($r['twz']) . '</td>';
            $html .= '<td>' . esc_html($r['gender']) . '</td>';
            $html .= '<td>' . esc_html($r['points']) . '</td>';
            $html .= '<td>' . esc_html($r['wins']) . '</td>';
            $html .= '<td>' . esc_html($r['draws']) . '</td>';
            $html .= '<td>' . esc_html($r['losses']) . '</td>';
            $html .= '<td>' . esc_html($r['buchholz']) . '</td>';
            $html .= '<td>' . esc_html($r['sonneborn']) . '</td>';
            $html .= '<td>' . esc_html($r['club']) . '</td>';
            $html .= '<td>' . esc_html($r['country']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    protected function pairingsToHtmlTable(array $pairings): string
    {
        if (empty($pairings)) {
            return '<p>Keine Paarungsdaten vorhanden.</p>';
        }

        $html = '';

        foreach ($pairings as $roundPairings) {

            if (empty($roundPairings)) {
                continue;
            }

            $round = $roundPairings[0]['round'] ?? '?';

            $html .= '<h3>Runde ' . intval($round) . '</h3>';

            $html .= '<table class="swiss-pairings">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Tisch</th>';
            $html .= '<th>TNr</th>';
            $html .= '<th>Teilnehmer</th>';
            $html .= '<th>Punkte</th>';
            $html .= '<th class="separator" style="background:#ddd;"></th>';
            $html .= '<th>TNr</th>';
            $html .= '<th>Teilnehmer</th>';
            $html .= '<th>Punkte</th>';
            $html .= '<th>Ergebnis</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            foreach ($roundPairings as $p) {

                $html .= '<tr>';

                // Brett
                $html .= '<td>' . esc_html($p['board']) . '</td>';

                // Weiß
                $html .= '<td>' . esc_html($p['white_id']) . '</td>';
                $html .= '<td>' . esc_html($p['white_name']) . '</td>';
                $html .= '<td>' . esc_html($p['white_points']) . '</td>';

                // Trenner (grau)
                $html .= '<td class="separator" style="background:#ddd;"></td>';

                // Schwarz
                $html .= '<td>' . esc_html($p['black_id']) . '</td>';
                $html .= '<td>' . esc_html($p['black_name']) . '</td>';
                $html .= '<td>' . esc_html($p['black_points']) . '</td>';

                // Ergebnis
                $html .= '<td>' . esc_html($p['result']) . '</td>';

                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }

        return $html;
    }

    protected function copyAllMeta(int $from_page_id, int $to_page_id): void
    {
        $blacklist_meta = [
            '_thumbnail_id',
            '_edit_last',
            '_edit_lock',
            '_wp_old_slug',
            '_wp_trash_meta_status',
            '_wp_trash_meta_time',
            '_menu_item_menu_item_parent',
            '_menu_item_object_id',
            '_menu_item_object',
            '_menu_item_type',
            '_menu_item_url',
        ];
        
        $all_meta = get_post_meta($from_page_id);

        foreach ($all_meta as $key => $values) {

            // WP interne Keys überspringen
            if (in_array($key, $blacklist_meta, true)) {
                continue;
            }

            foreach ($values as $value) {
                update_post_meta($to_page_id, $key, maybe_unserialize($value));
            }
        }
    }
}