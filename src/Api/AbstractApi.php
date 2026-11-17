<?php

declare(strict_types=1);

namespace QDH\DurianPay\Api;

use QDH\DurianPay\Enums\PaymentStatus;
use QDH\DurianPay\Exceptions\DurianPayException;
use QDH\DurianPay\Http\HttpClient;

abstract class AbstractApi
{
    public function __construct(protected readonly HttpClient $client) {}

    protected function post(string $path, array $body = []): array
    {
        return $this->client->post($path, $body);
    }

    protected function put(string $path, array $body = []): array
    {
        return $this->client->put($path, $body);
    }

    protected function toStatus(array $response): PaymentStatus
    {
        $raw    = (string) ($response['latestTransactionStatus'] ?? '');
        $status = PaymentStatus::tryFrom($raw);

        if ($status === null) {
            throw new DurianPayException("Unknown payment status: \"{$raw}\"");
        }

        return $status;
    }
}
