<?php

declare(strict_types=1);

namespace QDH\DurianPay\Http;

readonly class Response
{
    public function __construct(
        public int $status,
        public array $body,
    ) {}

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }
}
