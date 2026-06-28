<?php

declare(strict_types=1);

namespace Tests\Unit\Helper;

class FakeRequest {
    public function __construct(private array $headers, private array $params = []) {}

    public function get_header(string $key) {
        return $this->headers[$key] ?? null;
    }

    public function get_param(string $key) {
        return $this->params[$key] ?? null;
    }
}
