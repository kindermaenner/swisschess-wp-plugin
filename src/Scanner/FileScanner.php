<?php

declare(strict_types=1);

namespace SwissChess\Scanner;

if (!defined('ABSPATH')) {
    exit;
}

class FileScanner
{
    private string $path;

    public function __construct(string $path)
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException("Invalid directory: {$path}");
        }

        $this->path = rtrim($path, DIRECTORY_SEPARATOR);
    }

    public function scan(): array
    {
        $files = [];

        foreach (scandir($this->path) as $file) {
            if ($this->shouldSkip($file)) {
                continue;
            }

            if (!$this->isHtml($file)) {
                continue;
            }

            $fullPath = $this->path . DIRECTORY_SEPARATOR . $file;

            $files[] = [
                'filename' => $file,
                'path'     => $fullPath,
                'size'     => filesize($fullPath),
                'mtime'    => filemtime($fullPath),
            ];
        }

        return $files;
    }

    private function shouldSkip(string $file): bool
    {
        return in_array($file, ['.', '..'], true);
    }

    private function isHtml(string $file): bool
    {
        return str_ends_with(strtolower($file), '.html');
    }
}
