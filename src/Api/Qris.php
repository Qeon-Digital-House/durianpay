<?php

declare(strict_types=1);

namespace QDH\DurianPay\Api;

use QDH\DurianPay\Enums\PaymentStatus;
use QDH\DurianPay\Exceptions\DurianPayException;

class Qris extends AbstractApi
{
    public function charge(string $orderId, string $name, int $amount): array
    {
        return $this->post('payments', [
            'type'    => 'QRIS',
            'request' => [
                'order_id' => $orderId,
                'name'     => $name,
                'amount'   => $amount,
            ],
        ]);
    }

    public function fetch(string $id): array
    {
        return $this->get("payments/{$id}");
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
}
