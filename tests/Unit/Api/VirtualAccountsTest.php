<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Api\VirtualAccounts;
use QDH\DurianPay\Enums\BankCode;
use QDH\DurianPay\Http\HttpClient;

class VirtualAccountsTest extends TestCase
{
    private HttpClient $http;
    private VirtualAccounts $va;

    protected function setUp(): void
    {
        $this->http = $this->createMock(HttpClient::class);
        $this->va   = new VirtualAccounts($this->http);
    }

    public function test_create_with_amount_is_closed_va(): void
    {
        $response = ['responseCode' => '2002700', 'virtualAccountData' => ['virtualAccountNo' => '123']];

        $this->http->expects($this->once())
            ->method('post')
            ->with('transfer-va/create-va', [
                'virtualAccountName'    => 'Jane Doe',
                'virtualAccountTrxType' => 'C',
                'trxId'                 => 'trx_1',
                'additionalInfo'        => ['bankCode' => 'BCA'],
                'totalAmount'           => ['value' => '150000.00', 'currency' => 'IDR'],
            ])
            ->willReturn($response);

        $this->assertSame($response, $this->va->create(BankCode::Bca, 'trx_1', 'Jane Doe', 150000));
    }

    public function test_create_without_amount_is_open_va(): void
    {
        $this->http->expects($this->once())
            ->method('post')
            ->with('transfer-va/create-va', [
                'virtualAccountName'    => 'Jane Doe',
                'virtualAccountTrxType' => 'O',
                'trxId'                 => 'trx_1',
                'additionalInfo'        => ['bankCode' => 'BRI'],
            ])
            ->willReturn([]);

        $this->va->create(BankCode::Bri, 'trx_1', 'Jane Doe');
    }

    public function test_create_merges_options_and_additional_info(): void
    {
        $this->http->expects($this->once())
            ->method('post')
            ->with('transfer-va/create-va', [
                'customerNo'            => '12345678',
                'expiredDate'           => '2026-12-01T10:00:00+07:00',
                'virtualAccountName'    => 'Jane Doe',
                'virtualAccountTrxType' => 'C',
                'trxId'                 => 'trx_1',
                'additionalInfo'        => ['isReusable' => false, 'bankCode' => 'MANDIRI'],
                'totalAmount'           => ['value' => '10000.00', 'currency' => 'IDR'],
            ])
            ->willReturn([]);

        $this->va->create(BankCode::Mandiri, 'trx_1', 'Jane Doe', 10000, [
            'customerNo'     => '12345678',
            'expiredDate'    => '2026-12-01T10:00:00+07:00',
            'additionalInfo' => ['isReusable' => false],
        ]);
    }

    public function test_inquiry_calls_inquiry_va(): void
    {
        $this->http->expects($this->once())
            ->method('post')
            ->with('transfer-va/inquiry-va', [
                'partnerServiceId' => '   88899',
                'customerNo'       => '12345678',
                'virtualAccountNo' => '   8889912345678',
                'trxId'            => 'trx_1',
            ])
            ->willReturn(['responseCode' => '2003000']);

        $this->va->inquiry('   88899', '12345678', '   8889912345678', 'trx_1');
    }

    public function test_update_uses_put(): void
    {
        $params = ['trxId' => 'trx_1', 'expiredDate' => '2026-12-01T10:00:00+07:00'];

        $this->http->expects($this->once())
            ->method('put')
            ->with('transfer-va/update-va', $params)
            ->willReturn(['responseCode' => '2002800']);

        $this->va->update($params);
    }
}
