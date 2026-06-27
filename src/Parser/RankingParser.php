<?php

declare(strict_types=1);

namespace SwissChess\Parser;

class RankingParser extends SwissChessParser
{
    public function parseRanking(string $html): array
    {
        $xpath = $this->parseHtml($html);
        $rows = $xpath->query('//tr');

        $ranking = [];

        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');

            // 13 Spalten laut SwissChess
            if ($cells->length < 13) {
                continue;
            }

            $rankRaw = trim($cells->item(0)->textContent);
            if (!is_numeric($rankRaw)) {
                continue;
            }

            $ranking[] = [
                'rank'      => (int) $rankRaw,
                'name'      => $this->normalizeText($cells->item(1)->textContent),
                'title'     => $this->normalizeText($cells->item(2)->textContent) ?? '',
                'twz'       => $this->normalizeText($cells->item(3)->textContent) ?? '',
                'gender'    => $this->normalizeText($cells->item(4)->textContent) ?? '',
                'club'      => $this->normalizeText($cells->item(5)->textContent) ?? '',
                'country'   => $this->normalizeText($cells->item(6)->textContent) ?? '',
                'wins'      => $this->normalizeText($cells->item(7)->textContent) ?? '',
                'draws'     => $this->normalizeText($cells->item(8)->textContent) ?? '',
                'losses'    => $this->normalizeText($cells->item(9)->textContent) ?? '',
                'points'    => $this->normalizeText($cells->item(10)->textContent) ?? '',
                'buchholz'  => $this->normalizeText($cells->item(11)->textContent) ?? '',
                'sonneborn' => $this->normalizeText($cells->item(12)->textContent) ?? '',
            ];
        }

        return $ranking;
    }
}
