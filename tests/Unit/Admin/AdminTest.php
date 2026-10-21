<?php

use SwissChess\Admin\Admin;

it('registers admin hooks on init', function () {
    Admin::init();

    expect($GLOBALS['wp_actions'])->toHaveCount(2);
    expect($GLOBALS['wp_actions'][0]['hook'])->toBe('admin_menu');
    expect($GLOBALS['wp_actions'][1]['hook'])->toBe('admin_init');
    expect($GLOBALS['wp_actions'][0]['callback'][0])->toBe(Admin::class);
    expect($GLOBALS['wp_actions'][0]['callback'][1])->toBe('register_menu');
});

it('registers main menu', function () {
    Admin::register_menu();

    expect($GLOBALS['wp_menu_pages'])->toHaveCount(1);
    expect($GLOBALS['wp_menu_pages'][0]['menu_slug'])->toBe('swisschess');
});

it('renders settings page with stored values', function () {
    $GLOBALS['wp_options']['swisschess_author'] = 2;
    $GLOBALS['wp_options']['swisschess_template_static_page'] = 'Template A';
    $GLOBALS['wp_options']['swisschess_template_next_round_post'] = 'Template B';
    $GLOBALS['wp_options']['swisschess_template_final_results_post'] = 'Template C';
    $GLOBALS['wp_options']['swisschess_api_key'] = 'key-123';

    ob_start();
    Admin::render_settings_page();
    $html = ob_get_clean();

    expect($html)->toContain('Swiss Chess');
    expect($html)->toContain('Template A');
    expect($html)->toContain('Template B');
    expect($html)->toContain('Template C');
    expect($html)->toContain('key-123');
    expect($html)->toContain('name="swisschess_author"');
    expect($html)->toContain('name="swisschess_template_static_page"');
    expect($html)->toContain('name="swisschess_template_next_round_post"');
    expect($html)->toContain('name="swisschess_template_final_results_post"');
});

it('generates and stores a new api key from settings page', function () {
    $_POST['swisschess_generate_key'] = '1';

    ob_start();
    Admin::render_settings_page();
    ob_end_clean();

    expect($GLOBALS['wp_updated_options'])->toHaveKey('swisschess_api_key');
    expect(strlen($GLOBALS['wp_updated_options']['swisschess_api_key']))->toBe(32);
});



it('renders update section on settings page', function () {
    ob_start();
    Admin::render_settings_page();
    $html = ob_get_clean();

    expect($html)->toContain('Plugin-Aktualisierung');
    expect($html)->toContain('name="swisschess_check_update"');
    expect($html)->toContain('Auf Aktualisierungen prüfen');
});

it('triggers update check and displays message when check button is submitted without updater instance', function () {
    $_POST['swisschess_check_update'] = '1';

    ob_start();
    Admin::render_settings_page();
    $html = ob_get_clean();

    expect($GLOBALS['wp_deleted_site_transients'])->toContain('swisschess_plugin_update');
    expect($html)->toContain('Update-Cache wurde erfolgreich geleert');
});

it('triggers update check and displays version status when updater instance is present', function () {
    $updater = new \SwissChess\Update\PluginUpdater(
        '/var/www/wp-content/plugins/swisschess/SwissChess.php',
        '0.9.0',
        'https://kindermaenner.de/plugin-updates/swisschess/manifest.json'
    );
    $GLOBALS['wp_remote_response'] = [
        'code' => 200,
        'body' => json_encode([
            'slug' => 'swisschess',
            'version' => '0.9.0',
            'download_url' => 'https://kindermaenner.de/plugin-updates/swisschess/versions/v0.9.0/swisschess.zip',
            'checksum_sha256' => str_repeat('b', 64),
        ]),
    ];
    $_POST['swisschess_check_update'] = '1';

    ob_start();
    Admin::render_settings_page();
    $html = ob_get_clean();

    expect($html)->toContain('Das Plugin ist auf dem neuesten Stand');
});
