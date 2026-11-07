<?php

declare(strict_types=1);

namespace SwissChess\Update;

final class ReleaseNotesBuilder
{
    public function buildChangelog(array $manifest, ?string $releaseUrl): string
    {
        $releases = $manifest['releases'] ?? null;
        if (!is_array($releases)) {
            return $this->buildFallbackChangelog($manifest, $releaseUrl);
        }

        $html = '';
        foreach ($releases as $release) {
            if (is_array($release)) {
                $html .= $this->buildRelease($release);
            }
        }

        return $html !== '' ? $html : $this->buildFallbackChangelog($manifest, $releaseUrl);
    }

    public function buildCompatibilitySection(array $manifest): string
    {
        $requirements = [];
        $requiresPhp = $this->getPhpVersion($manifest['requires_php'] ?? null);
        if ($requiresPhp !== null) {
            $requirements[] = 'Requires PHP ' . esc_html($requiresPhp) . '.';
        }

        $testedPhp = $this->getPhpVersion($manifest['tested_php'] ?? null);
        if ($testedPhp !== null) {
            $requirements[] = 'Automated CI tested with PHP ' . esc_html($testedPhp) . '.';
        }

        return $requirements === [] ? '' : '<p>' . implode('<br>', $requirements) . '</p>';
    }

    public function getGitHubUrl($url): ?string
    {
        if (!is_string($url) || !wp_http_validate_url($url)) {
            return null;
        }

        $scheme = wp_parse_url($url, PHP_URL_SCHEME);
        $host = wp_parse_url($url, PHP_URL_HOST);

        return $scheme === 'https' && $host === 'github.com' ? $url : null;
    }

    public function getPhpVersion($version): ?string
    {
        return is_string($version) && preg_match('/^\d+(?:\.\d+){1,2}$/D', $version) === 1
            ? $version
            : null;
    }

    public function formatReleaseDate($date): string
    {
        if (!is_string($date)) {
            return '';
        }

        $timestamp = strtotime($date);
        return $timestamp === false ? '' : gmdate('Y-m-d H:i:s', $timestamp);
    }

    private function buildRelease(array $release): string
    {
        if (!is_string($release['version'] ?? null)) {
            return '';
        }

        $html = '<h4>' . esc_html($release['version']) . $this->formatReleaseLabels($release) . '</h4>';
        $releasedAt = $this->getReleaseDate($release['released_at'] ?? null);
        if ($releasedAt !== '') {
            $html .= '<p>Released: ' . esc_html($releasedAt) . '</p>';
        }

        $notes = $release['notes'] ?? '';
        if (is_string($notes) && $notes !== '') {
            $html .= $this->formatReleaseNotes($notes);
        }

        return $html . $this->buildReleaseLinks($release);
    }

    private function formatReleaseLabels(array $release): string
    {
        $labels = [];
        if (($release['security_fixes'] ?? false) === true) {
            $labels[] = 'Security update';
        }
        if (($release['prerelease'] ?? false) === true) {
            $labels[] = 'Pre-release';
        }

        return $labels === [] ? '' : ' <em>(' . esc_html(implode(', ', $labels)) . ')</em>';
    }

    private function buildReleaseLinks(array $release): string
    {
        $releaseLink = $this->getGitHubUrl($release['url'] ?? null);
        if ($releaseLink === null) {
            return '';
        }

        $html = '<p><a href="' . esc_url($releaseLink) . '">View release on GitHub</a>';
        $compareLink = $this->getGitHubUrl($release['compare_url'] ?? null);
        if ($compareLink !== null) {
            $html .= ' | <a href="' . esc_url($compareLink) . '">Compare changes</a>';
        }

        return $html . '</p>';
    }

    private function buildFallbackChangelog(array $manifest, ?string $releaseUrl): string
    {
        $notes = $manifest['release_notes'] ?? '';
        if (!is_string($notes) || $notes === '') {
            return 'Release notes are not available.';
        }

        $formattedNotes = $this->formatReleaseNotes($notes);
        if ($releaseUrl === null) {
            return $formattedNotes;
        }

        return $formattedNotes . '<p><a href="' . esc_url($releaseUrl) . '">View release on GitHub</a></p>';
    }

    public function formatReleaseNotes(string $notes): string
    {
        return nl2br(esc_html($notes), false);
    }

    private function getReleaseDate($date): string
    {
        if (!is_string($date)) {
            return '';
        }

        $timestamp = strtotime($date);
        return $timestamp === false ? '' : gmdate('Y-m-d', $timestamp);
    }
}
