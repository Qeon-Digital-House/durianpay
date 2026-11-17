<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Api\Ewallet;
use QDH\DurianPay\Enums\PaymentStatus;
use QDH\DurianPay\Enums\WalletType;
use QDH\DurianPay\Http\HttpClient;

class EwalletTest extends TestCase
{
    private HttpClient $http;
    private Ewallet $ewallet;

    protected function setUp(): void
    {
        $this->http    = $this->createMock(HttpClient::class);
        $this->ewallet = new Ewallet($this->http);
    }

    public function test_create_calls_payment_host_to_host(): void
    {
        $response = ['responseCode' => '2005400', 'referenceNo' => 'pay_1', 'webRedirectUrl' => 'https://pay'];

        $this->http->expects($this->once())
            ->method('post')
            ->with('debit/payment-host-to-host', [
                'partnerReferenceNo' => 'ref_1',
                'amount'             => ['value' => '10000.00', 'currency' => 'IDR'],
                'additionalInfo'     => [
                    'customerInfo' => ['given_name' => 'Jane'],
                    'phoneNumber'  => '085103885747',
                    'channelCode'  => 'SHOPEEPAY',
                ],
            ])
            ->willReturn($response);

        $this->assertSame($response, $this->ewallet->create(
            'ref_1',
            10000,
            WalletType::ShopeePay,
            '085103885747',
            ['customerInfo' => ['given_name' => 'Jane']],
        ));
    }

    public function test_query_uses_service_code_54(): void
    {
        $this->http->expects($this->once())
            ->method('post')
            ->with('debit/status', ['originalReferenceNo' => 'pay_1', 'serviceCode' => '54'])
            ->willReturn(['latestTransactionStatus' => '00']);

        $this->ewallet->query('pay_1');
    }

    public function test_status_returns_typed_enum(): void
    {
        $this->http->method('post')->willReturn(['latestTransactionStatus' => '00']);

        $this->assertSame(PaymentStatus::Completed, $this->ewallet->status('pay_1'));
    }

    public function test_cancel_omits_reason_when_null(): void
    {
        $this->http->expects($this->once())
            ->method('post')
            ->with('debit/cancel', ['originalReferenceNo' => 'pay_1', 'originalPartnerReferenceNo' => 'ref_1'])
            ->willReturn(['responseCode' => '2005700']);

        $this->ewallet->cancel('pay_1', 'ref_1');
    }

    public function test_cancel_sends_reason(): void
    {
        $this->http->expects($this->once())
            ->method('post')
            ->with('debit/cancel', $this->callback(fn (array $b): bool => $b['reason'] === 'timeout'))
            ->willReturn([]);

        $this->ewallet->cancel('pay_1', 'ref_1', 'timeout');
    }

    public function test_refund_calls_debit_refund(): void
    {
        $this->http->expects($this->once())
            ->method('post')
            ->with('debit/refund', [
                'originalReferenceNo'        => 'pay_1',
                'originalPartnerReferenceNo' => 'ref_1',
                'partnerRefundNo'            => 'rf_1',
                'refundAmount'               => ['value' => '2500.00', 'currency' => 'IDR'],
            ])
            ->willReturn(['responseCode' => '2005800']);

        $this->ewallet->refund('pay_1', 'ref_1', 'rf_1', 2500);
    }
}
