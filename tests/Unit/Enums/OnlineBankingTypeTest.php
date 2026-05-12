<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Enums\OnlineBankingType;

class OnlineBankingTypeTest extends TestCase
{
    /** @dataProvider caseProvider */
    public function test_case_value(OnlineBankingType $case, string $expected): void
    {
        $this->assertSame($expected, $case->value);
    }

    public static function caseProvider(): array
    {
        return [
            [OnlineBankingType::BriEpay,         'BRI_EPAY'],
            [OnlineBankingType::CimbClicks,      'CIMB_CLICKS'],
            [OnlineBankingType::DanamonOnline,   'DANAMON_ONLINE'],
            [OnlineBankingType::MandiriClickpay, 'MANDIRI_CLICKPAY'],
            [OnlineBankingType::PermataNet,      'PERMATA_NET'],
        ];
    }

    public function test_from_throws_on_unknown_value(): void
    {
        $this->expectException(\ValueError::class);
        OnlineBankingType::from('UNKNOWN_BANKING');
    }
}
