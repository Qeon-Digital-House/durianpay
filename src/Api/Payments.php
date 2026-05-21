<?php

declare(strict_types=1);

namespace QDH\DurianPay\Api;

use QDH\DurianPay\Enums\PaymentStatus;
use QDH\DurianPay\Enums\PaymentType;
use QDH\DurianPay\Exceptions\DurianPayException;

class Payments extends AbstractApi
{
    public function charge(PaymentType $type, array $request): array
    {
        return $this->post('payments/charge', [
            'type'    => $type->value,
            'request' => $request,
        ]);
    }

    public function fetch(string $id): array
    {
        return $this->get("payments/{$id}");
    }

    public function list(int $skip = 0, int $limit = 25): array
    {
        return $this->get('payments', ['skip' => $skip, 'limit' => $limit]);
    }

    /** GET /payments/{id}/status — returns the raw API response. */
    public function checkStatus(string $id): array
    {
        return $this->get("payments/{$id}/status");
    }

    /** Returns a typed PaymentStatus enum for the given payment ID. */
    public function status(string $id): PaymentStatus
    {
        $response = $this->checkStatus($id);
        $raw      = $response['data']['status'] ?? $response['status'] ?? '';
        $status   = PaymentStatus::tryFrom($raw);

        if ($status === null) {
            throw new DurianPayException("Unknown payment status: \"{$raw}\"");
        }

        return $status;
    }

    public function verify(string $id, array $params): array
    {
        return $this->post("payments/{$id}/verify", $params);
    }

    public function capture(string $id): array
    {
        return $this->post("payments/{$id}/capture");
    }
}
