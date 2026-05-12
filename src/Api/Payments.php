<?php

declare(strict_types=1);

namespace QDH\DurianPay\Api;

use QDH\DurianPay\Enums\PaymentType;

class Payments extends AbstractApi
{
    public function charge(PaymentType $type, array $request): array
    {
        return $this->post('payments', [
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

    public function verify(string $id, array $params): array
    {
        return $this->post("payments/{$id}/verify", $params);
    }

    public function capture(string $id): array
    {
        return $this->post("payments/{$id}/capture");
    }
}
