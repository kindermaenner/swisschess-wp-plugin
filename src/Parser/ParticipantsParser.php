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
            $participant = $this->parseParticipantRow($row);
            if ($participant === null) {
                continue;
            }

            $participants[] = $participant;
        }

        return $participants;
    }

    private function parseParticipantRow(\DOMElement $row): ?array
    {
        $cells = $row->getElementsByTagName('td');
        if (!$this->hasRequiredColumns($cells)) {
            return null;
        }

        $numberRaw = trim($cells->item(0)->textContent);
        if (!is_numeric($numberRaw)) {
            return null;
        }

        return $this->buildParticipant($cells, $numberRaw);
    }

    private function hasRequiredColumns(\DOMNodeList $cells): bool
    {
        return $cells->length >= 8;
    }

    private function buildParticipant(\DOMNodeList $cells, string $numberRaw): array
    {
        return [
            'number'    => (int) $numberRaw,
            'name'      => $this->normalizeText($cells->item(1)->textContent),
            'title'     => $this->normalizeText($cells->item(2)->textContent) ?: '',
            'twz'       => $this->normalizeText($cells->item(3)->textContent) ?: '',
            'gender'    => $this->normalizeText($cells->item(4)->textContent) ?: '',
            'club'      => $this->normalizeText($cells->item(5)->textContent) ?: '',
            'country'   => $this->normalizeText($cells->item(6)->textContent) ?: '',
            'birthyear' => $this->normalizeText($cells->item(7)->textContent) ?: '',
        ];
    }
}
