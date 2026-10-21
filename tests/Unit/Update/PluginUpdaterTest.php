<?php

declare(strict_types=1);

use SwissChess\Update\PluginUpdater;

if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $acceptedArgs = 1)
    {
        $GLOBALS['wp_filters'][] = compact('hook', 'callback', 'priority', 'acceptedArgs');
        return true;
    }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename($file)
    {
        return basename(dirname($file)) . '/' . basename($file);
    }
}

if (!function_exists('get_site_transient')) {
    function get_site_transient($key)
    {
        return $GLOBALS['wp_site_transients'][$key] ?? false;
    }
}

if (!function_exists('set_site_transient')) {
    function set_site_transient($key, $value, $expiration = 0)
    {
        $GLOBALS['wp_site_transients'][$key] = $value;
        $GLOBALS['wp_site_transient_expiration'][$key] = $expiration;
        return true;
    }
}

if (!function_exists('delete_site_transient')) {
    function delete_site_transient($key)
    {
        unset($GLOBALS['wp_site_transients'][$key]);
        $GLOBALS['wp_deleted_site_transients'][] = $key;
        return true;
    }
}

if (!function_exists('wp_safe_remote_get')) {
    function wp_safe_remote_get($url, $args = [])
    {
        $GLOBALS['wp_remote_get_calls'][] = [$url, $args];
        return $GLOBALS['wp_remote_response'];
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response)
    {
        return $response['code'] ?? 0;
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response)
    {
        return $response['body'] ?? '';
    }
}

if (!function_exists('wp_http_validate_url')) {
    function wp_http_validate_url($url)
    {
        return is_string($url) && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

if (!function_exists('wp_parse_url')) {
    function wp_parse_url($url, $component = -1)
    {
        return parse_url($url, $component);
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url)
    {
        return (string) $url;
    }
}

if (!function_exists('download_url')) {
    function download_url($url, $timeout = 300)
    {
        $GLOBALS['wp_download_url_calls'][] = [$url, $timeout];
        return $GLOBALS['wp_download_result'];
    }
}

if (!function_exists('wp_delete_file')) {
    function wp_delete_file($file)
    {
        $GLOBALS['wp_deleted_files'][] = $file;
        if (is_file($file)) {
            unlink($file);
        }
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($value)
    {
        return $value instanceof WP_Error;
    }
}

beforeEach(function () {
    $GLOBALS['wp_filters'] = [];
    $GLOBALS['wp_site_transients'] = [];
    $GLOBALS['wp_site_transient_expiration'] = [];
    $GLOBALS['wp_deleted_site_transients'] = [];
    $GLOBALS['wp_remote_get_calls'] = [];
    $GLOBALS['wp_remote_response'] = [
        'code' => 200,
        'body' => '',
    ];
    $GLOBALS['wp_download_result'] = '';
    $GLOBALS['wp_download_url_calls'] = [];
    $GLOBALS['wp_deleted_files'] = [];

    $this->updater = new PluginUpdater(
        '/var/www/wp-content/plugins/swisschess-wp-plugin/SwissChess.php',
        '0.9.0',
        'https://kindermaenner.de/plugin-updates/swisschess/manifest.json'
    );
});

function swissChessManifest(array $overrides = []): array
{
    return array_merge([
        'slug' => 'swisschess-wp-plugin',
        'version' => '1.0.0',
        'download_url' => 'https://kindermaenner.de/plugin-updates/swisschess/versions/v1.0.0/swisschess-wp-plugin.zip',
        'checksum_sha256' => str_repeat('a', 64),
    ], $overrides);
}

function swissChessCacheManifest(array $manifest): void
{
    $GLOBALS['wp_site_transients']['swisschess_plugin_update'] = $manifest;
}

test('registers WordPress updater hooks with the required argument counts', function () {
    expect($GLOBALS['wp_filters'])->toHaveCount(3);
    expect(array_column($GLOBALS['wp_filters'], 'hook'))->toBe([
        'site_transient_update_plugins',
        'plugins_api',
        'upgrader_pre_download',
    ]);
    expect($GLOBALS['wp_filters'][2]['acceptedArgs'])->toBe(2);
});

test('adds an update when the manifest version is newer', function () {
    $GLOBALS['wp_remote_response']['body'] = json_encode(swissChessManifest([
        'release_notes' => 'New features and fixes',
        'prerelease' => true,
        'security_fixes' => true,
        'requires_php' => '8.1',
    ]));
    $transient = (object) [
        'checked' => ['swisschess-wp-plugin/SwissChess.php' => '0.9.0'],
        'response' => [],
    ];

    $result = $this->updater->checkForUpdate($transient);

    expect($result->response['swisschess-wp-plugin/SwissChess.php']->new_version)->toBe('1.0.0')
        ->and($result->response['swisschess-wp-plugin/SwissChess.php']->package)
        ->toBe(swissChessManifest()['download_url'])
        ->and($result->response['swisschess-wp-plugin/SwissChess.php']->upgrade_notice)
        ->toContain('Security update')
        ->and($result->response['swisschess-wp-plugin/SwissChess.php']->upgrade_notice)
        ->toContain('Pre-release')
        ->and($result->response['swisschess-wp-plugin/SwissChess.php']->requires_php)->toBe('8.1');
    expect($GLOBALS['wp_site_transient_expiration']['swisschess_plugin_update'])->toBe(6 * HOUR_IN_SECONDS);
});

test('does not add an update when the manifest version is not newer', function () {
    $GLOBALS['wp_remote_response']['body'] = json_encode(swissChessManifest(['version' => '0.9.0']));
    $transient = (object) [
        'checked' => ['swisschess-wp-plugin/SwissChess.php' => '0.9.0'],
        'response' => [],
    ];

    expect($this->updater->checkForUpdate($transient)->response)->toBeEmpty();
});

test('ignores invalid manifests', function () {
    $GLOBALS['wp_remote_response']['body'] = json_encode(swissChessManifest(['slug' => 'other-plugin']));
    $transient = (object) [
        'checked' => ['swisschess-wp-plugin/SwissChess.php' => '0.9.0'],
        'response' => [],
    ];

    expect($this->updater->checkForUpdate($transient)->response)->toBeEmpty();
    expect($GLOBALS['wp_site_transients'])->toBeEmpty();
});

test('returns plugin information only for this plugin', function () {
    swissChessCacheManifest(swissChessManifest());
    $otherResult = (object) ['unchanged' => true];

    expect($this->updater->pluginInformation($otherResult, 'plugin_information', (object) ['slug' => 'another-plugin']))
        ->toBe($otherResult);
    expect($this->updater->pluginInformation(null, 'plugin_information', (object) ['slug' => 'swisschess-wp-plugin'])->version)
        ->toBe('1.0.0');
});

test('shows GitHub release details, changelog, and PHP compatibility', function () {
    $releaseUrl = 'https://github.com/kindermaenner/swisschess-wp-plugin/releases/tag/v1.0.0';
    $compareUrl = 'https://github.com/kindermaenner/swisschess-wp-plugin/compare/v0.9.0...v1.0.0';
    swissChessCacheManifest(swissChessManifest([
        'name' => 'SwissChess',
        'homepage' => 'https://github.com/kindermaenner/swisschess-wp-plugin',
        'release_url' => $releaseUrl,
        'released_at' => '2026-10-20T22:25:45Z',
        'requires_php' => '8.1',
        'tested_php' => '8.4',
        'releases' => [[
            'version' => '1.0.0',
            'released_at' => '2026-10-20T22:25:45Z',
            'notes' => "Security:\n- Improved output handling",
            'url' => $releaseUrl,
            'compare_url' => $compareUrl,
            'prerelease' => false,
            'security_fixes' => true,
        ], [
            'version' => '0.9.0-beta.1',
            'released_at' => '2026-10-19T22:25:45Z',
            'notes' => 'Added import preview',
            'url' => 'https://github.com/kindermaenner/swisschess-wp-plugin/releases/tag/v0.9.0-beta.1',
            'compare_url' => $compareUrl,
            'prerelease' => true,
            'security_fixes' => false,
        ]],
    ]));

    $info = $this->updater->pluginInformation(null, 'plugin_information', (object) ['slug' => 'swisschess-wp-plugin']);

    expect($info->name)->toBe('SwissChess')
        ->and($info->homepage)->toBe('https://github.com/kindermaenner/swisschess-wp-plugin')
        ->and($info->requires_php)->toBe('8.1')
        ->and($info->last_updated)->toBe('2026-10-20 22:25:45')
        ->and($info->sections['changelog'])->toContain('Security:')
        ->and($info->sections['changelog'])->toContain('Security update')
        ->and($info->sections['changelog'])->toContain('Pre-release')
        ->and($info->sections['changelog'])->toContain($releaseUrl)
        ->and($info->sections['changelog'])->toContain($compareUrl)
        ->and($info->sections['description'])->toContain('Requires PHP 8.1')
        ->and($info->sections['description'])->toContain('tested with PHP 8.4');
});

test('clears its update manifest cache after plugin updates', function () {
    swissChessCacheManifest(swissChessManifest());

    $this->updater->clearCache(['action' => 'update', 'type' => 'plugin']);
    expect($GLOBALS['wp_site_transients'])->toBeEmpty();
    expect($GLOBALS['wp_deleted_site_transients'])->toBe(['swisschess_plugin_update']);
});

test('verifies a downloaded package checksum', function () {
    swissChessCacheManifest(swissChessManifest());
    $file = tempnam(sys_get_temp_dir(), 'swisschess-update-');
    file_put_contents($file, 'release package');
    $GLOBALS['wp_download_result'] = $file;
    $manifest = swissChessManifest(['checksum_sha256' => hash_file('sha256', $file)]);

    try {
        swissChessCacheManifest($manifest);
        expect($this->updater->verifyDownload(false, $manifest['download_url']))->toBe($file);
    } finally {
        if (is_file($file)) {
            unlink($file);
        }
    }
});

test('rejects a downloaded package with a checksum mismatch', function () {
    $manifest = swissChessManifest();
    swissChessCacheManifest($manifest);
    $file = tempnam(sys_get_temp_dir(), 'swisschess-update-');
    file_put_contents($file, 'tampered package');
    $GLOBALS['wp_download_result'] = $file;

    $result = $this->updater->verifyDownload(false, $manifest['download_url']);

    expect($result)->toBeInstanceOf(WP_Error::class)
        ->and($result->get_error_code())->toBe('swisschess_update_checksum_mismatch')
        ->and(is_file($file))->toBeFalse();
});
