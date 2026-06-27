<?php

declare(strict_types=1);

/**
 * Plugin Name: SwissChess
 * Description: Import von Swiss‑Chess‑Turnierdaten in WordPress.
 * Version: 1.0
 * Author: Nina Kindermann
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

// Admin-Bereich aktivieren
\SwissChess\Admin\Admin::init();

// REST-API aktivieren
\SwissChess\Api\Api::init();
