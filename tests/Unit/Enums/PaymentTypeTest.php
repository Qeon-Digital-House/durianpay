<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Enums\PaymentType;

class PaymentTypeTest extends TestCase
{
    /** @dataProvider caseProvider */
    public function test_case_value(PaymentType $case, string $expected): void
    {
        $this->assertSame($expected, $case->value);
    }

    public static function caseProvider(): array
    {
        return [
            [PaymentType::EWallet,        'EWALLET'],
            [PaymentType::VirtualAccount, 'VA'],
            [PaymentType::RetailStore,    'RETAILSTORE'],
            [PaymentType::OnlineBanking,  'ONLINE_BANKING'],
            [PaymentType::BuyNowPayLater, 'BNPL'],
            [PaymentType::Qris,           'QRIS'],
        ];
    }

    public function test_from_resolves_known_value(): void
    {
        $this->assertSame(PaymentType::EWallet, PaymentType::from('EWALLET'));
        $this->assertSame(PaymentType::Qris, PaymentType::from('QRIS'));
    }

    public function test_from_throws_on_unknown_value(): void
    {
        $this->expectException(\ValueError::class);
        PaymentType::from('UNKNOWN');
    }

    public function test_try_from_returns_null_on_unknown_value(): void
    {
        $this->assertNull(PaymentType::tryFrom('INVALID'));
    }
}
