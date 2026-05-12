<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Api\Qris;
use QDH\DurianPay\Http\HttpClient;

class QrisTest extends TestCase
{
    private HttpClient $http;
    private Qris $qris;

    protected function setUp(): void
    {
        $this->http = $this->createMock(HttpClient::class);
        $this->qris = new Qris($this->http);
    }

    public function test_charge_sends_correct_payload(): void
    {
        $expected = [
            'type'    => 'QRIS',
            'request' => [
                'order_id' => 'ord_123',
                'name'     => 'John Doe',
                'amount'   => 50000,
            ],
        ];
        $response = ['data' => ['qr_code_string' => 'qr-data']];

        $this->http->expects($this->once())
            ->method('post')
            ->with('payments', $expected)
            ->willReturn($response);

        $result = $this->qris->charge(orderId: 'ord_123', name: 'John Doe', amount: 50000);

        $this->assertSame($response, $result);
    }

    public function test_charge_type_is_always_qris(): void
    {
        $this->http->expects($this->once())
            ->method('post')
            ->with('payments', $this->callback(
                fn (array $body): bool => $body['type'] === 'QRIS'
            ))
            ->willReturn([]);

        $this->qris->charge('ord_123', 'Jane', 10000);
    }

    public function test_fetch_gets_payment_by_id(): void
    {
        $response = ['data' => ['id' => 'pay_123', 'status' => 'PROCESSING']];

        $this->http->expects($this->once())
            ->method('get')
            ->with('payments/pay_123', [])
            ->willReturn($response);

        $this->assertSame($response, $this->qris->fetch('pay_123'));
    }
}
