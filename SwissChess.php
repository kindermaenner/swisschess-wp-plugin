<?php

/**
 * Plugin Name: SwissChess
 * Description: Import von Swiss‑Chess‑Turnierdaten in WordPress.
 * Version: 1.0
 * Author: Nina Kindermann
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

define('SWISSCHESS_PLUGIN_PATH', plugin_dir_path(__FILE__));

$pluginData = get_file_data(__FILE__, ['Version' => 'Version']);
new \SwissChess\Update\PluginUpdater(
    __FILE__,
    $pluginData['Version'],
    'https://kindermaenner.de/plugin-updates/swisschess/manifest.json'
);

// Admin-Bereich aktivieren
\SwissChess\Admin\Admin::init();

// REST-API aktivieren
\SwissChess\Api\Api::init();
