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

    public function test_generate_calls_qr_mpm_generate(): void
    {
        $info     = ['expiryDate' => '2026-12-01T10:00:00+07:00', 'customerInfo' => ['given_name' => 'Jane']];
        $response = ['responseCode' => '2004700', 'referenceNo' => 'pay_1', 'qrContent' => '000201...'];

        $this->http->expects($this->once())
            ->method('post')
            ->with('qr/qr-mpm-generate', [
                'partnerReferenceNo' => 'ref_1',
                'amount'             => ['value' => '10000.00', 'currency' => 'IDR'],
                'additionalInfo'     => $info,
            ])
            ->willReturn($response);

        $this->assertSame($response, $this->qris->generate('ref_1', 10000, $info));
    }

    public function test_generate_sends_empty_additional_info_as_object(): void
    {
        $this->http->expects($this->once())
            ->method('post')
            ->with('qr/qr-mpm-generate', $this->callback(
                fn (array $body): bool => $body['additionalInfo'] instanceof \stdClass
            ))
            ->willReturn([]);

        $this->qris->generate('ref_1', 10000);
    }

    public function test_query_uses_service_code_47(): void
    {
        $response = ['latestTransactionStatus' => '00'];

        $this->http->expects($this->once())
            ->method('post')
            ->with('qr/qr-mpm-query', ['originalReferenceNo' => 'pay_1', 'serviceCode' => '47'])
            ->willReturn($response);

        $this->assertSame($response, $this->qris->query('pay_1'));
    }

    public function test_status_returns_typed_enum(): void
    {
        $this->http->method('post')->willReturn(['latestTransactionStatus' => '08']);

        $this->assertSame(PaymentStatus::Expired, $this->qris->status('pay_1'));
    }

    public function test_status_throws_on_unknown_status(): void
    {
        $this->http->method('post')->willReturn(['latestTransactionStatus' => '99']);

        $this->expectException(DurianPayException::class);
        $this->expectExceptionMessageMatches('/99/');

        $this->qris->status('pay_1');
    }

    public function test_cancel_calls_qr_mpm_cancel(): void
    {
        $this->http->expects($this->once())
            ->method('post')
            ->with('qr/qr-mpm-cancel', [
                'merchantId'                 => 'mer_1',
                'originalReferenceNo'        => 'pay_1',
                'originalPartnerReferenceNo' => 'ref_1',
                'reason'                     => 'customer cancelled',
            ])
            ->willReturn(['responseCode' => '2007700']);

        $this->qris->cancel('pay_1', 'ref_1', 'mer_1', 'customer cancelled');
    }

    public function test_refund_calls_qr_mpm_refund(): void
    {
        $this->http->expects($this->once())
            ->method('post')
            ->with('qr/qr-mpm-refund', [
                'originalReferenceNo'        => 'pay_1',
                'originalPartnerReferenceNo' => 'ref_1',
                'partnerRefundNo'            => 'rf_1',
                'refundAmount'               => ['value' => '5000.00', 'currency' => 'IDR'],
            ])
            ->willReturn(['responseCode' => '2007800']);

        $this->qris->refund('pay_1', 'ref_1', 'rf_1', 5000);
    }
}
