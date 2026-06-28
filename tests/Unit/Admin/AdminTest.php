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
    $GLOBALS['wp_options']['swisschess_template'] = 'Template A';
    $GLOBALS['wp_options']['swisschess_api_key'] = 'key-123';

    ob_start();
    Admin::render_settings_page();
    $html = ob_get_clean();

    expect($html)->toContain('Swiss Chess');
    expect($html)->toContain('Template A');
    expect($html)->toContain('key-123');
    expect($html)->toContain('name="swisschess_author"');
    expect($html)->toContain('name="swisschess_template"');
});

it('generates and stores a new api key from settings page', function () {
    $_POST['swisschess_generate_key'] = '1';

    ob_start();
    Admin::render_settings_page();
    ob_end_clean();

    expect($GLOBALS['wp_updated_options'])->toHaveKey('swisschess_api_key');
    expect(strlen($GLOBALS['wp_updated_options']['swisschess_api_key']))->toBe(32);
});

