<?php

declare(strict_types=1);

namespace QDH\DurianPay\Api;

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
}
