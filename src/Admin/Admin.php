<?php

declare(strict_types=1);

namespace SwissChess\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Admin {

    public static function init() {
        add_action('admin_menu', [self::class, 'register_menu']);
        add_action('admin_init', function() {
            register_setting('swisschess', 'swisschess_author');
            register_setting('swisschess', 'swisschess_template_static_page');
            register_setting('swisschess', 'swisschess_template_next_round_post');
            register_setting('swisschess', 'swisschess_template_final_results_post');
            register_setting('swisschess', 'swisschess_api_key');
            register_setting('swisschess', 'swisschess_delete_after_import');
        });
    }

    public static function register_menu() {
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

    public static function render_settings_page() { 

        // Aktuelle Werte laden
        $author    = get_option('swisschess_author', '');
        $template_static_page = get_option('swisschess_template_static_page', '');
        $template_next_round_post = get_option('swisschess_template_next_round_post', '');
        $template_final_results_post = get_option('swisschess_template_final_results_post', '');
        $api_key   = get_option('swisschess_api_key', '');
        $delete_after_import = get_option('swisschess_delete_after_import', false);
        // Wenn "Neuen Key generieren" gedrückt wurde (eigenes Formular!)
        if (isset($_POST['swisschess_generate_key'])) {

            check_admin_referer('swisschess_generate_key_action');

            $new_key = wp_generate_password(32, false, false);
            update_option('swisschess_api_key', $new_key);
            $api_key = $new_key;

            echo '<div class="updated"><p>Neuer API‑Key wurde generiert.</p></div>';
        }

        // Userliste
        $users = get_users([
            'fields' => ['ID', 'user_login']
        ]);

        echo '<div class="wrap">';
        echo '<h1>Swiss Chess – Einstellungen</h1>';

        /*
        * FORMULAR 1: API‑Key generieren (NICHT über options.php)
        */
        echo '<form method="post">';
        wp_nonce_field('swisschess_generate_key_action');

        echo '<table class="form-table">';
        echo '<tr><th scope="row">API‑Key</th><td>';
        echo '<input type="text" value="' . esc_attr($api_key) . '" class="regular-text" style="width:350px;" readonly>';
        echo '<p class="description">Diesen Key muss die Blitzabend‑App im Header <code>X-MB-Key</code> mitsenden.</p>';
        echo '<p><input type="submit" name="swisschess_generate_key" class="button" value="Neuen API‑Key generieren"></p>';
        echo '</td></tr>';
        echo '</table>';

        echo '</form>';

        /*
        * FORMULAR 2: Normale Einstellungen (läuft über options.php)
        */
        echo '<form method="post" action="options.php">';
        settings_fields('swisschess');

        echo '<table class="form-table">';

        // API‑Key auch hier einfügen, damit WordPress ihn speichert
        echo '<tr><th scope="row">API‑Key (gespeichert)</th><td>';
        echo '<input type="text" name="swisschess_api_key" value="' . esc_attr($api_key) . '" class="regular-text" style="width:350px;" readonly>';
        echo '<p class="description">Wird automatisch aktualisiert, wenn ein neuer Key generiert wird.</p>';
        echo '</td></tr>';

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
        echo '<input type="text" name="swisschess_template_static_page" value="' . esc_attr($template_static_page) . '" class="regular-text">';
        echo '</td></tr>';

        // Template für Beitrag zur neu ausgelosten Runde
        echo '<tr><th scope="row">Template: Beitrag neu ausgeloste Runde</th><td>';
        echo '<input type="text" name="swisschess_template_next_round_post" value="' . esc_attr($template_next_round_post) . '" class="regular-text">';
        echo '</td></tr>';

        // Template für Beitrag über Turnierergebnis
        echo '<tr><th scope="row">Template: Beitrag Turnierergebnis</th><td>';
        echo '<input type="text" name="swisschess_template_final_results_post" value="' . esc_attr($template_final_results_post) . '" class="regular-text">';
        echo '</td></tr>';

        // Dateien nach Import löschen
        echo '<tr><th scope="row">Dateien nach Import löschen</th><td>';
        echo '<label>';
        echo '<input type="checkbox" name="swisschess_delete_after_import" value="1" ' . checked(1, get_option('swisschess_delete_after_import'), false) . '>';
        echo ' Nach erfolgreichem Import die HTML‑Dateien löschen';
        echo '</label>';
        echo '<p class="description">Wenn aktiviert, werden die eingelesenen Swiss‑Chess‑Dateien nach dem Import entfernt.</p>';
        echo '</td></tr>';

        echo '</table>';

        submit_button();
        echo '</form>';

        echo '</div>';
    }
}