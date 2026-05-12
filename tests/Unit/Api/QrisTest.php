<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Api\Qris;
use QDH\DurianPay\Enums\PaymentStatus;
use QDH\DurianPay\Exceptions\DurianPayException;
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

        $this->assertSame($response, $this->qris->charge(orderId: 'ord_123', name: 'John Doe', amount: 50000));
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

    /** @dataProvider statusProvider */
    public function test_status_returns_typed_enum(string $raw, PaymentStatus $expected): void
    {
        $this->http->expects($this->once())
            ->method('get')
            ->with('payments/pay_123', [])
            ->willReturn(['data' => ['status' => $raw]]);

        $this->assertSame($expected, $this->qris->status('pay_123'));
    }

    public static function statusProvider(): array
    {
        return [
            [PaymentStatus::Started->value,    PaymentStatus::Started],
            [PaymentStatus::Processing->value, PaymentStatus::Processing],
            [PaymentStatus::Completed->value,  PaymentStatus::Completed],
            [PaymentStatus::Failed->value,     PaymentStatus::Failed],
            [PaymentStatus::Cancelled->value,  PaymentStatus::Cancelled],
            [PaymentStatus::Expired->value,    PaymentStatus::Expired],
            [PaymentStatus::Pending->value,    PaymentStatus::Pending],
        ];
    }

    public function test_status_throws_on_unknown_status(): void
    {
        $this->http->expects($this->once())
            ->method('get')
            ->willReturn(['data' => ['status' => 'UNKNOWN_STATUS']]);

        $this->expectException(DurianPayException::class);
        $this->expectExceptionMessageMatches('/UNKNOWN_STATUS/');

        $this->qris->status('pay_123');
    }
}
