<?php

declare(strict_types=1);

namespace SwissChess\Config;

class Config {
    public static function getImportPath(): string {
        $upload = wp_upload_dir();
        return $upload['basedir'] . '/swisschess';
    }
}