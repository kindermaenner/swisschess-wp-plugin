<?php

declare(strict_types=1);

namespace SwissChess\Update;

final class PluginUpdater
{
    public const CACHE_KEY = 'swisschess_plugin_update';

    private static ?self $instance = null;

    private string $pluginBasename;

    private string $pluginSlug;

    private string $currentVersion;

    private string $manifestUrl;

    private ReleaseNotesBuilder $releaseNotesBuilder;

    private string $cacheKey = self::CACHE_KEY;

    public function __construct(string $pluginFile, string $currentVersion, string $manifestUrl)
    {
        self::$instance = $this;

        $this->pluginBasename = plugin_basename($pluginFile);
        $this->pluginSlug = dirname($this->pluginBasename);
        $this->currentVersion = $currentVersion;
        $this->manifestUrl = $manifestUrl;
        $this->releaseNotesBuilder = new ReleaseNotesBuilder();

        add_filter('site_transient_update_plugins', [$this, 'checkForUpdate']);
        add_filter('plugins_api', [$this, 'pluginInformation'], 20, 3);
        add_filter('upgrader_pre_download', [$this, 'verifyDownload'], 10, 2);
        add_action('upgrader_process_complete', function (...$args): void {
            $this->clearCache($args[1]);
        }, 10, 2);
        add_action('load-update-core.php', [$this, 'clearManifestCache']);
    }

    public function checkForUpdate($transient)
    {
        if (!is_object($transient) || empty($transient->checked)) {
            return $transient;
        }

        $manifest = $this->getManifest();
        if ($manifest === null || version_compare($manifest['version'], $this->currentVersion, '<=')) {
            return $transient;
        }

        $update = (object) [
            'id' => $this->manifestUrl,
            'slug' => $this->pluginSlug,
            'plugin' => $this->pluginBasename,
            'new_version' => $manifest['version'],
            'url' => $this->releaseNotesBuilder->getGitHubUrl($manifest['release_url'] ?? null) ?? $this->manifestUrl,
            'package' => $manifest['download_url'],
        ];
        $releaseNotes = $manifest['release_notes'] ?? '';
        if (is_string($releaseNotes) && $releaseNotes !== '') {
            $update->upgrade_notice = $this->releaseNotesBuilder->formatReleaseNotes($releaseNotes);
            $noticeLabels = [];
            if (($manifest['security_fixes'] ?? false) === true) {
                $noticeLabels[] = 'Security update';
            }
            if (($manifest['prerelease'] ?? false) === true) {
                $noticeLabels[] = 'Pre-release';
            }
            if ($noticeLabels !== []) {
                $update->upgrade_notice = '<strong>' . esc_html(implode(' · ', $noticeLabels)) . ':</strong> '
                    . $update->upgrade_notice;
            }
        }
        $requiresPhp = $this->releaseNotesBuilder->getPhpVersion($manifest['requires_php'] ?? null);
        if ($requiresPhp !== null) {
            $update->requires_php = $requiresPhp;
        }

        $transient->response[$this->pluginBasename] = $update;

        return $transient;
    }

    public function pluginInformation($result, string $action, $args)
    {
        if ($action !== 'plugin_information' || !is_object($args) || ($args->slug ?? '') !== $this->pluginSlug) {
            return $result;
        }

        $manifest = $this->getManifest();
        if ($manifest === null) {
            return $result;
        }

        $homepage = $this->releaseNotesBuilder->getGitHubUrl($manifest['homepage'] ?? null) ?? $this->manifestUrl;
        $releaseUrl = $this->releaseNotesBuilder->getGitHubUrl($manifest['release_url'] ?? null);
        $description = sprintf(
            'Plugin updates are provided by <a href="%s">the project on GitHub</a>.',
            esc_url($homepage)
        );
        $compatibility = $this->releaseNotesBuilder->buildCompatibilitySection($manifest);
        if ($compatibility !== '') {
            $description .= $compatibility;
        }

        return (object) [
            'name' => is_string($manifest['name'] ?? null) && $manifest['name'] !== ''
                ? $manifest['name']
                : $this->pluginSlug,
            'slug' => $this->pluginSlug,
            'version' => $manifest['version'],
            'author' => 'Kindermaenner',
            'homepage' => $homepage,
            'download_link' => $manifest['download_url'],
            'requires_php' => $this->releaseNotesBuilder->getPhpVersion($manifest['requires_php'] ?? null),
            'last_updated' => $this->releaseNotesBuilder->formatReleaseDate($manifest['released_at'] ?? null),
            'sections' => [
                'description' => $description,
                'changelog' => $this->releaseNotesBuilder->buildChangelog($manifest, $releaseUrl),
            ],
        ];
    }

    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    public function getCurrentVersion(): string
    {
        return $this->currentVersion;
    }

    public function clearManifestCache(): void
    {
        delete_site_transient($this->cacheKey);
    }

    public function clearCache(array $options): void
    {
        if (($options['action'] ?? '') === 'update' && ($options['type'] ?? '') === 'plugin') {
            delete_site_transient($this->cacheKey);
        }
    }

    public function purgeCache(): void
    {
        delete_site_transient($this->cacheKey);
        delete_site_transient('update_plugins');
        if (function_exists('wp_update_plugins')) {
            wp_update_plugins();
        }
    }

    public static function purge(): void
    {
        if (self::$instance !== null) {
            self::$instance->purgeCache();
        } else {
            delete_site_transient(self::CACHE_KEY);
            delete_site_transient('update_plugins');
            if (function_exists('wp_update_plugins')) {
                wp_update_plugins();
            }
        }
    }

    public function checkUpdateNow(): array
    {
        $this->purgeCache();
        $manifest = $this->getManifest();

        $latestVersion = is_array($manifest) && is_string($manifest['version'] ?? null)
            ? $manifest['version']
            : null;

        $hasUpdate = $latestVersion !== null && version_compare($latestVersion, $this->currentVersion, '>');

        return [
            'current_version' => $this->currentVersion,
            'latest_version'  => $latestVersion,
            'has_update'      => $hasUpdate,
            'release_url'     => is_array($manifest) ? ($manifest['release_url'] ?? null) : null,
            'download_url'    => is_array($manifest) ? ($manifest['download_url'] ?? null) : null,
        ];
    }

    public function verifyDownload($reply, string $package)
    {
        if ($reply !== false) {
            return $reply;
        }

        $manifest = $this->getManifest();
        if ($manifest === null || $manifest['download_url'] !== $package) {
            return $reply;
        }

        $download = download_url($package, 300);
        if (is_wp_error($download)) {
            return $download;
        }

        $checksum = hash_file('sha256', $download);
        if (!is_string($checksum) || !hash_equals($manifest['checksum_sha256'], $checksum)) {
            wp_delete_file($download);
            $this->log('The downloaded package checksum did not match the manifest.');

            return new \WP_Error(
                'swisschess_update_checksum_mismatch',
                'The SwissChess update package failed its checksum verification.'
            );
        }

        return $download;
    }

    private function getManifest(): ?array
    {
        $cached = get_site_transient($this->cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $response = wp_safe_remote_get($this->manifestUrl, [
            'timeout' => 10,
            'headers' => ['Accept' => 'application/json'],
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            $this->log('Unable to retrieve the update manifest.');
            return null;
        }

        $manifest = json_decode(wp_remote_retrieve_body($response), true);
        if (!$this->isValidManifest($manifest)) {
            $this->log('The update manifest is invalid.');
            return null;
        }

        set_site_transient($this->cacheKey, $manifest, 6 * HOUR_IN_SECONDS);

        return $manifest;
    }

    private function isValidManifest($manifest): bool
    {
        if (!is_array($manifest) || ($manifest['slug'] ?? '') !== $this->pluginSlug) {
            return false;
        }

        if (!is_string($manifest['version'] ?? null) || preg_match('/^\d+\.\d+\.\d+$/D', $manifest['version']) !== 1) {
            return false;
        }

        if (!is_string($manifest['download_url'] ?? null) || !wp_http_validate_url($manifest['download_url'])) {
            return false;
        }

        if (!is_string($manifest['checksum_sha256'] ?? null) || preg_match('/^[a-f0-9]{64}$/iD', $manifest['checksum_sha256']) !== 1) {
            return false;
        }

        return $this->downloadUrlUsesManifestHost($manifest['download_url']);
    }

    private function downloadUrlUsesManifestHost(string $downloadUrl): bool
    {
        $manifestHost = wp_parse_url($this->manifestUrl, PHP_URL_HOST);
        $downloadHost = wp_parse_url($downloadUrl, PHP_URL_HOST);
        $downloadScheme = wp_parse_url($downloadUrl, PHP_URL_SCHEME);

        return $downloadScheme === 'https' && is_string($manifestHost) && $manifestHost === $downloadHost;
    }

    private function log(string $message): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SwissChess updater: ' . $message);
        }
    }
}
