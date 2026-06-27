<?php

declare(strict_types=1);

namespace SwissChess\Parser;

use DOMDocument;
use DOMXPath;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

class SwissChessParser
{
    public function parseHtml(string $html): DOMXPath
    {
        // wichtig wegen Umlauten & Entities
        $html = '<?xml encoding="UTF-8">' . $html;

        $dom = new DOMDocument();

        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    public function extractTournamentName(string $html): string
    {
        preg_match('/<H2>(.*?)<\/H2>/i', $html, $matches);

        $raw = $matches[1] ?? '';

        $decoded = html_entity_decode(strip_tags($raw));

        // NBSP → normales Leerzeichen
        $normalized = str_replace("\xC2\xA0", ' ', $decoded);

        return trim($normalized);
    }

    protected function normalizeText(string $text): string
    {
        $text = html_entity_decode($text);
        $text = str_replace("\xC2\xA0", ' ', $text);

        return trim($text);
    }

    protected function normalizePointsBefore(string $text): string
    {
        $text = trim($text);

        // Entferne Klammern: (XYZ) -> XYZ
        $text = preg_replace('/[()]/', '', $text);

        $text = trim($text);

        // Leerer Inhalt bedeutet 0 Punkte
        if ($text === '') {
            return '0';
        }

        return $text;
    }

}
