<?php

declare(strict_types=1);

use SwissChess\Config\Config;

beforeEach(function () {
    $GLOBALS['wp_upload_dir'] = [
        'basedir' => '/var/www/wp-content/uploads',
    ];
});

it('builds import path from wordpress upload basedir', function () {
    $path = Config::getImportPath();

    expect($path)->toBe('/var/www/wp-content/uploads/swisschess');
});

it('reflects changed upload basedir', function () {
    $GLOBALS['wp_upload_dir']['basedir'] = '/custom/uploads';

    $path = Config::getImportPath();

    expect($path)->toBe('/custom/uploads/swisschess');
});
