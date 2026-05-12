<?php

declare(strict_types=1);

namespace QDH\DurianPay\Api;

class Orders extends AbstractApi
{
    public function create(array $params): array
    {
        return $this->post('orders', $params);
    }

    public function fetch(string $id): array
    {
        return $this->get("orders/{$id}");
    }

    public function list(int $skip = 0, int $limit = 25): array
    {
        return $this->get('orders', ['skip' => $skip, 'limit' => $limit]);
    }

    public function fetchItems(string $id): array
    {
        return $this->get("orders/{$id}/items");
    }
}
