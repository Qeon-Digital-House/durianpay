<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Enums\WalletType;

class WalletTypeTest extends TestCase
{
    /** @dataProvider caseProvider */
    public function test_case_value(WalletType $case, string $expected): void
    {
        $this->assertSame($expected, $case->value);
    }

    public static function caseProvider(): array
    {
        return [
            [WalletType::Dana,      'DANA'],
            [WalletType::Ovo,       'OVO'],
            [WalletType::LinkAja,   'LINKAJA'],
            [WalletType::GoPay,     'GOPAY'],
            [WalletType::ShopeePay, 'SHOPEEPAY'],
            [WalletType::JeniusPay, 'JENIUSPAY'],
            [WalletType::AstraPay,  'ASTRAPAY'],
        ];
    }

    public function test_from_throws_on_unknown_value(): void
    {
        $this->expectException(\ValueError::class);
        WalletType::from('UNKNOWN_WALLET');
    }
}
