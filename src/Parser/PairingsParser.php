<?php

declare(strict_types=1);

namespace SwissChess\Parser;

class PairingsParser extends SwissChessParser
{
    public function parsePairings(string $html): array
    {
        $xpath = $this->parseHtml($html);
        $round = $this->detectRoundFromHtml($html);
        $rows = $xpath->query('//tr');

        $pairings = [];

        /** @var DOMElement $row */
        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');

            // Es werden Spalten bis Index 10 gelesen.
            if ($cells->length < 11) {
                continue;
            }

            $boardRaw = trim($cells->item(0)->textContent);
            if (!is_numeric($boardRaw)) {
                continue;
            }

            $pairings[] = [
                'round'        => $round,
                'board'        => (int) trim($cells->item(0)->textContent),

                'white_id'     => (int) trim($cells->item(1)->textContent),
                'white_name'   => $this->normalizeText($cells->item(2)->textContent),
                'white_title'  => $this->normalizeText($cells->item(3)->textContent),
                'white_points' => $this->normalizePointsBefore($cells->item(4)->textContent),

                // Spalte 5 ist nur ein Bindestrich
                // -> ignorieren

                'black_id'     => (int) trim($cells->item(6)->textContent),
                'black_name'   => $this->normalizeText($cells->item(7)->textContent),
                'black_title'  => $this->normalizeText($cells->item(8)->textContent),
                'black_points' => $this->normalizePointsBefore($cells->item(9)->textContent),

                'result'       => $this->normalizeText($cells->item(10)->textContent),
            ];
        }

        return $pairings;
    }

    private function detectRoundFromHtml(string $html): int
    {
        // Entities entfernen
        $clean = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Alles in Kleinbuchstaben
        $clean = strtolower($clean);

        // Runde extrahieren
        if (preg_match('/paarungsliste[^0-9]*([0-9]+)\.?[^0-9]*runde/', $clean, $m)) {
            return (int)$m[1];
        }

        return 0;
    }
}
