<?php

declare(strict_types=1);

namespace SwissChess\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Admin
{
    public static function init()
    {
        add_action('admin_menu', [self::class, 'register_menu']);
        add_action('admin_init', function () {
            register_setting('swisschess', 'swisschess_author');
            register_setting('swisschess', 'swisschess_template_static_page');
            register_setting('swisschess', 'swisschess_template_next_round_post');
            register_setting('swisschess', 'swisschess_template_final_results_post');
            register_setting('swisschess', 'swisschess_api_key');
            register_setting('swisschess', 'swisschess_delete_after_import');
        });
    }

    public static function register_menu()
    {
        add_menu_page(
            'Swiss Chess',
            'Swiss Chess',
            'manage_options',
            'swisschess',
            [self::class, 'render_settings_page'],
            'dashicons-chart-bar',
            26
        );
    }

    public static function render_settings_page()
    {
        $author    = get_option('swisschess_author', '');
        $template_static_page = get_option('swisschess_template_static_page', '');
        $template_next_round_post = get_option('swisschess_template_next_round_post', '');
        $template_final_results_post = get_option('swisschess_template_final_results_post', '');
        $api_key   = get_option('swisschess_api_key', '');
        $delete_after_import = get_option('swisschess_delete_after_import', false);
        $api_key = self::generateApiKeyIfRequested($api_key);
        [$updateResult, $cachePurgedNotice] = self::checkForUpdateIfRequested();
        $users = get_users([
            'fields' => ['ID', 'user_login']
        ]);

        echo '<div class="wrap">';
        echo '<h1>Swiss Chess – Einstellungen</h1>';

        self::renderUpdateNotice($updateResult, $cachePurgedNotice);
        self::renderUpdateSection(self::getInstalledVersion());
        echo '<h2 style="margin-top:30px;">⚙️ Einstellungen</h2>';
        self::renderApiKeyForm($api_key);
        self::renderOptionsForm(
            $author,
            $template_static_page,
            $template_next_round_post,
            $template_final_results_post,
            $api_key,
            $delete_after_import,
            $users
        );
        echo '</div>';
    }

    private static function generateApiKeyIfRequested(string $apiKey): string
    {
        if (!isset($_POST['swisschess_generate_key'])) {
            return $apiKey;
        }

        check_admin_referer('swisschess_generate_key_action');
        $apiKey = wp_generate_password(32, false, false);
        update_option('swisschess_api_key', $apiKey);
        echo '<div class="updated"><p>Neuer API‑Key wurde generiert.</p></div>';

        return $apiKey;
    }

    private static function checkForUpdateIfRequested(): array
    {
        $updateResult = null;
        $cachePurgedNotice = false;
        if (!isset($_POST['swisschess_check_update'])) {
            return [$updateResult, $cachePurgedNotice];
        }

        check_admin_referer('swisschess_check_update_action');
        if (!class_exists(\SwissChess\Update\PluginUpdater::class)) {
            return [$updateResult, $cachePurgedNotice];
        }

        $updater = \SwissChess\Update\PluginUpdater::getInstance();
        if ($updater !== null) {
            return [$updater->checkUpdateNow(), $cachePurgedNotice];
        }

        \SwissChess\Update\PluginUpdater::purge();

        return [$updateResult, true];
    }

    private static function renderUpdateNotice(?array $updateResult, bool $cachePurgedNotice): void
    {
        if ($updateResult !== null) {
            self::renderUpdateResultNotice($updateResult);
        } elseif ($cachePurgedNotice) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ <strong>Update-Cache wurde erfolgreich geleert.</strong></p></div>';
        }
    }

    private static function renderUpdateResultNotice(array $updateResult): void
    {
        if ($updateResult['has_update']) {
            $updateUrl = function_exists('admin_url') ? admin_url('plugins.php') : 'plugins.php';
            echo '<div class="notice notice-warning"><p><strong>🚀 Neue Version ' . esc_html($updateResult['latest_version'] ?? '') . ' verfügbar!</strong> Aktuell installiert: ' . esc_html($updateResult['current_version']) . '. <a href="' . esc_url($updateUrl) . '">Jetzt auf der Plugin-Seite aktualisieren &rarr;</a></p></div>';
            return;
        }

        $displayVersion = $updateResult['latest_version'] ?? $updateResult['current_version'];
        echo '<div class="notice notice-success is-dismissible"><p>✅ <strong>Das Plugin ist auf dem neuesten Stand</strong> (Version ' . esc_html($displayVersion) . ').</p></div>';
    }

    private static function getInstalledVersion(): string
    {
        if (class_exists(\SwissChess\Update\PluginUpdater::class)) {
            $updater = \SwissChess\Update\PluginUpdater::getInstance();
            if ($updater !== null) {
                return $updater->getCurrentVersion();
            }
        }

        return defined('SWISSCHESS_VERSION') ? SWISSCHESS_VERSION : '1.0';
    }

    private static function renderUpdateSection(string $installedVersion): void
    {
        echo '<div class="card" style="max-width:600px; margin:20px 0; padding:16px 20px;">';
        echo '<h2 style="margin-top:0;">🔄 Plugin-Aktualisierung</h2>';
        echo '<p>Installierte Version: <strong>' . esc_html($installedVersion) . '</strong></p>';
        echo '<form method="post" style="margin-top:12px;">';
        wp_nonce_field('swisschess_check_update_action');
        echo '<p><input type="submit" name="swisschess_check_update" class="button button-secondary" value="🔄 Auf Aktualisierungen prüfen"></p>';
        echo '<p class="description">Leert den internen 6-Stunden-Cache und prüft sofort beim Update-Server auf neue Releases.</p>';
        echo '</form>';
        echo '</div>';
    }

    private static function renderApiKeyForm(string $apiKey): void
    {
        echo '<form method="post" style="margin-bottom:0;">';
        wp_nonce_field('swisschess_generate_key_action');
        echo '<table class="form-table" style="margin-bottom:0;">';
        echo '<tr><th scope="row">API‑Key</th><td>';
        echo '<input type="text" value="' . esc_attr($apiKey) . '" class="regular-text" style="width:350px;" readonly> ';
        echo '<input type="submit" name="swisschess_generate_key" class="button" value="Neuen API‑Key generieren">';
        echo '<p class="description">Dieser Key muss bei Nutzung der REST-API im Header <code>X-MB-Key</code> mitgesendet werden.</p>';
        echo '</td></tr>';
        echo '</table>';
        echo '</form>';
    }

    private static function renderOptionsForm(
        $author,
        string $templateStaticPage,
        string $templateNextRoundPost,
        string $templateFinalResultsPost,
        string $apiKey,
        $deleteAfterImport,
        array $users
    ): void {
        echo '<form method="post" action="options.php">';
        settings_fields('swisschess');
        echo '<input type="hidden" name="swisschess_api_key" value="' . esc_attr($apiKey) . '">';

        echo '<table class="form-table">';

        // Autor-Auswahl
        echo '<tr><th scope="row">Autor des Beitrags</th><td>';
        echo '<select name="swisschess_author">';
        echo '<option value="">— Bitte wählen —</option>';

        foreach ($users as $u) {
            $selected = selected($author, $u->ID, false);
            echo "<option value='{$u->ID}' $selected>{$u->user_login}</option>";
        }

        echo '</select>';
        echo '</td></tr>';

        // Template für statische Turnierseite
        echo '<tr><th scope="row">Template: Statische Turnierseite</th><td>';
        echo '<input type="text" name="swisschess_template_static_page" value="' . esc_attr($templateStaticPage) . '" class="regular-text">';
        echo '</td></tr>';

        // Template für Beitrag zur neu ausgelosten Runde
        echo '<tr><th scope="row">Template: Beitrag neu ausgeloste Runde</th><td>';
        echo '<input type="text" name="swisschess_template_next_round_post" value="' . esc_attr($templateNextRoundPost) . '" class="regular-text">';
        echo '</td></tr>';

        // Template für Beitrag über Turnierergebnis
        echo '<tr><th scope="row">Template: Beitrag Turnierergebnis</th><td>';
        echo '<input type="text" name="swisschess_template_final_results_post" value="' . esc_attr($templateFinalResultsPost) . '" class="regular-text">';
        echo '</td></tr>';

        // Dateien nach Import löschen
        echo '<tr><th scope="row">Dateien nach Import löschen</th><td>';
        echo '<label>';
        echo '<input type="checkbox" name="swisschess_delete_after_import" value="1" ' . checked(1, $deleteAfterImport, false) . '>';
        echo ' Nach erfolgreichem Import die HTML‑Dateien löschen';
        echo '</label>';
        echo '<p class="description">Wenn aktiviert, werden die eingelesenen Swiss‑Chess‑Dateien nach dem Import entfernt.</p>';
        echo '</td></tr>';

        echo '</table>';

        submit_button();
        echo '</form>';
    }
}
