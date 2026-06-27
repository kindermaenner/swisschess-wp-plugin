<?php

declare(strict_types=1);

namespace SwissChess\Parser;

use DOMDocument;
use DOMXPath;

if (!defined('ABSPATH')) {
    exit;
}

class ParticipantsParser extends SwissChessParser
{
    public function parseParticipants(string $html): array
    {
        $xpath = $this->parseHtml($html);

        // Alle Tabellenzeilen holen
        $rows = $xpath->query('//tr');

        $participants = [];

        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');

            // Wir brauchen mindestens 8 Spalten (laut SwissChess-Format)
            if ($cells->length < 8) {
                continue;
            }

            // Erste Zelle muss numerisch sein → echte Datenzeile
            $numberRaw = trim($cells->item(0)->textContent);
            if (!is_numeric($numberRaw)) {
                continue;
            }

            $participants[] = [
                'number'     => (int) $numberRaw,
                'name'       => $this->normalizeText($cells->item(1)->textContent),
                'title'      => $this->normalizeText($cells->item(2)->textContent) ?: '',
                'twz'        => $this->normalizeText($cells->item(3)->textContent) ?: '',
                'gender'     => $this->normalizeText($cells->item(4)->textContent) ?: '',
                'club'       => $this->normalizeText($cells->item(5)->textContent) ?: '',
                'country'    => $this->normalizeText($cells->item(6)->textContent) ?: '',
                'birthyear'  => $this->normalizeText($cells->item(7)->textContent) ?: '',
            ];
        }

        return $participants;
    }
}