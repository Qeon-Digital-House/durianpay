<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Api\Payments;
use QDH\DurianPay\Enums\PaymentStatus;
use QDH\DurianPay\Enums\PaymentType;
use QDH\DurianPay\Exceptions\DurianPayException;
use QDH\DurianPay\Http\HttpClient;

class PaymentsTest extends TestCase
{
    private HttpClient $http;
    private Payments $payments;

    protected function setUp(): void
    {
        $this->http     = $this->createMock(HttpClient::class);
        $this->payments = new Payments($this->http);
    }

    /** @dataProvider paymentTypeProvider */
    public function test_charge_sends_correct_type_value(PaymentType $type, string $expected): void
    {
        $this->http->expects($this->once())
            ->method('post')
            ->with('payments', $this->callback(
                fn (array $body): bool => $body['type'] === $expected
            ))
            ->willReturn([]);

        $this->payments->charge($type, []);
    }

    public static function paymentTypeProvider(): array
    {
        return [
            'ewallet'         => [PaymentType::EWallet,        'EWALLET'],
            'virtual_account' => [PaymentType::VirtualAccount, 'VA'],
            'retail_store'    => [PaymentType::RetailStore,    'RETAILSTORE'],
            'online_banking'  => [PaymentType::OnlineBanking,  'ONLINE_BANKING'],
            'bnpl'            => [PaymentType::BuyNowPayLater, 'BNPL'],
            'qris'            => [PaymentType::Qris,           'QRIS'],
        ];
    }

    public function test_charge_wraps_request_in_payload(): void
    {
        $request  = ['order_id' => 'ord_123', 'amount' => '50000.00', 'wallet_type' => 'DANA'];
        $response = ['data' => ['id' => 'pay_123']];

        $this->http->expects($this->once())
            ->method('post')
            ->with('payments', ['type' => 'EWALLET', 'request' => $request])
            ->willReturn($response);

        $this->assertSame($response, $this->payments->charge(PaymentType::EWallet, $request));
    }

    public function test_fetch_gets_single_payment(): void
    {
        $response = ['data' => ['id' => 'pay_123']];

        $this->http->expects($this->once())
            ->method('get')
            ->with('payments/pay_123', [])
            ->willReturn($response);

        $this->assertSame($response, $this->payments->fetch('pay_123'));
    }

    public function test_list_uses_default_pagination(): void
    {
        $this->http->expects($this->once())
            ->method('get')
            ->with('payments', ['skip' => 0, 'limit' => 25])
            ->willReturn([]);

        $this->payments->list();
    }

    public function test_list_accepts_custom_pagination(): void
    {
        $this->http->expects($this->once())
            ->method('get')
            ->with('payments', ['skip' => 5, 'limit' => 10])
            ->willReturn([]);

        $this->payments->list(skip: 5, limit: 10);
    }

    /** @dataProvider statusProvider */
    public function test_status_returns_typed_enum(string $raw, PaymentStatus $expected): void
    {
        $this->http->expects($this->once())
            ->method('get')
            ->with('payments/pay_123', [])
            ->willReturn(['data' => ['status' => $raw]]);

        $this->assertSame($expected, $this->payments->status('pay_123'));
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

        $this->payments->status('pay_123');
    }

    public function test_verify_posts_to_verify_endpoint(): void
    {
        $params   = ['verification_signature' => 'sig-abc'];
        $response = ['data' => ['verified' => true]];

        $this->http->expects($this->once())
            ->method('post')
            ->with('payments/pay_123/verify', $params)
            ->willReturn($response);

        $this->assertSame($response, $this->payments->verify('pay_123', $params));
    }

    public function test_capture_posts_to_capture_endpoint(): void
    {
        $response = ['data' => ['status' => 'captured']];

        $this->http->expects($this->once())
            ->method('post')
            ->with('payments/pay_123/capture', [])
            ->willReturn($response);

        $this->assertSame($response, $this->payments->capture('pay_123'));
    }
}
