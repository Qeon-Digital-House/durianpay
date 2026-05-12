<?php

declare(strict_types=1);

namespace QDH\DurianPay\Api;

use QDH\DurianPay\Http\HttpClient;

abstract class AbstractApi
{
    public function __construct(protected readonly HttpClient $client) {}

    protected function get(string $path, array $query = []): array
    {
        return $this->client->get($path, $query);
    }

    protected function post(string $path, array $body = []): array
    {
        return $this->client->post($path, $body);
    }
}
