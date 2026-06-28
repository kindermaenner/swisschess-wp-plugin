<?php

declare(strict_types=1);

use SwissChess\Scanner\FileScanner;

it('scans directory and finds html files', function () {
    $scanner = new FileScanner(__DIR__ . '/../../data/neue_auslosung');

    $files = $scanner->scan();

    expect($files)
        ->not->toBeEmpty()
        ->and($files[0])->toHaveKeys(['filename', 'path', 'size', 'mtime']);
});

it('only returns html files', function () {
    $scanner = new FileScanner(__DIR__ . '/../../data/neue_auslosung');

    $files = $scanner->scan();

    foreach ($files as $file) {
        expect($file['filename'])->toEndWith('.html');
    }
});

it('finds known swisschess export files', function () {
    $scanner = new FileScanner(__DIR__ . '/../../data/neue_auslosung');

    $files = $scanner->scan();

    $filenames = array_column($files, 'filename');

    expect($filenames)->toContain(
        'stadtmeisterschaft-paar-r1.html',
        'stadtmeisterschaft-teilrang.html'
    );
});

it('throws exception on invalid path', function () {
    new FileScanner('/invalid/path');
})->throws(InvalidArgumentException::class);