<?php

/**
 * Plugin Name: SwissChess
 * Plugin URI: https://github.com/kindermaenner/swisschess-wp-plugin
 * Description: Import von Swiss‑Chess‑Turnierdaten in WordPress.
 * Version: 1.0
 * Requires PHP: 8.1
 * Author: Nina Kindermann
 * Author URI: https://kindermaenner.de
 * License: GPL-2.0-or-later
 * License URI: https://github.com/kindermaenner/swisschess-wp-plugin/blob/main/LICENSE
 * Update URI: https://kindermaenner.de/plugin-updates/swisschess/
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

define('SWISSCHESS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SWISSCHESS_PLUGIN_URL', plugin_dir_url(__FILE__));

$pluginData = get_file_data(__FILE__, ['Version' => 'Version']);
define('SWISSCHESS_VERSION', $pluginData['Version'] ?? '1.0');
new \SwissChess\Update\PluginUpdater(
    __FILE__,
    SWISSCHESS_VERSION,
    'https://kindermaenner.de/plugin-updates/swisschess/manifest.json'
);

// Admin-Bereich aktivieren
\SwissChess\Admin\Admin::init();

// REST-API aktivieren
\SwissChess\Api\Api::init();

// Frontend-Styles fuer die generierten Tabellen laden
add_action('wp_enqueue_scripts', [\SwissChess\Output\WordpressOutput::class, 'enqueueStyles']);
