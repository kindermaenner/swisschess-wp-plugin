<?php

declare(strict_types=1);

namespace SwissChess\Api;

use SwissChess\Runner\SwissChessRunner;

if (!defined('ABSPATH')) {
    exit;
}

class Api {

    public static function init() {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    public static function register_routes() {

        register_rest_route('swisschess/v1', '/scan', [
            'methods'  => 'POST',
            'callback' => [self::class, 'scan'],
            'permission_callback' => [self::class, 'verify_api_key'],
        ]);
    }

    public static function verify_api_key($request) {
        $api_key = get_option('swisschess_api_key');

        // Header-Variante (App)
        $header_key = $request->get_header('x-mb-key');

        // GET-Variante (Cronjob)
        $query_key = $request->get_param('key');

        if (!$api_key) {
            return new \WP_Error(
                'rest_misconfigured',
                'API key not configured',
                ['status' => 500]
            );
        }

        // Header hat Priorität, GET ist Fallback
        $provided_key = $header_key ?: $query_key;

        if ($provided_key !== $api_key) {
            return new \WP_Error(
                'rest_forbidden',
                'Unauthorized',
                ['status' => 403]
            );
        }

        return true;
    }


    public static function scan($request) {
        $runner = new SwissChessRunner();

        return rest_ensure_response([
            'success' => true,
            'data' => $runner->run(),
        ]);
    }
}
