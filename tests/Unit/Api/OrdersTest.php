<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Api\Orders;
use QDH\DurianPay\Http\HttpClient;

class OrdersTest extends TestCase
{
    private HttpClient $http;
    private Orders $orders;

    protected function setUp(): void
    {
        $this->http   = $this->createMock(HttpClient::class);
        $this->orders = new Orders($this->http);
    }

    public function test_create_posts_to_orders(): void
    {
        $params   = ['amount' => '50000.00', 'currency' => 'IDR', 'order_ref_id' => 'ref-1'];
        $response = ['data' => ['id' => 'ord_123']];

        $this->http->expects($this->once())
            ->method('post')
            ->with('orders', $params)
            ->willReturn($response);

        $this->assertSame($response, $this->orders->create($params));
    }

    public function test_fetch_gets_single_order(): void
    {
        $response = ['data' => ['id' => 'ord_123']];

        $this->http->expects($this->once())
            ->method('get')
            ->with('orders/ord_123', [])
            ->willReturn($response);

        $this->assertSame($response, $this->orders->fetch('ord_123'));
    }

    public function test_list_uses_default_pagination(): void
    {
        $response = ['data' => []];

        $this->http->expects($this->once())
            ->method('get')
            ->with('orders', ['skip' => 0, 'limit' => 25])
            ->willReturn($response);

        $this->assertSame($response, $this->orders->list());
    }

    public function test_list_accepts_custom_pagination(): void
    {
        $this->http->expects($this->once())
            ->method('get')
            ->with('orders', ['skip' => 10, 'limit' => 50])
            ->willReturn([]);

        $this->orders->list(skip: 10, limit: 50);
    }

    public function test_fetch_items_gets_order_line_items(): void
    {
        $response = ['data' => [['sku' => 'SKU-1']]];

        $this->http->expects($this->once())
            ->method('get')
            ->with('orders/ord_123/items', [])
            ->willReturn($response);

        $this->assertSame($response, $this->orders->fetchItems('ord_123'));
    }
}
