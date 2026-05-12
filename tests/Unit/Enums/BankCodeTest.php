<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Enums\BankCode;

class BankCodeTest extends TestCase
{
    /** @dataProvider caseProvider */
    public function test_case_value(BankCode $case, string $expected): void
    {
        $this->assertSame($expected, $case->value);
    }

    public static function caseProvider(): array
    {
        return [
            [BankCode::Bca,     'BCA'],
            [BankCode::Bni,     'BNI'],
            [BankCode::Bri,     'BRI'],
            [BankCode::Mandiri, 'MANDIRI'],
            [BankCode::Permata, 'PERMATA'],
            [BankCode::Bsi,     'BSI'],
            [BankCode::Cimb,    'CIMB'],
            [BankCode::Danamon, 'DANAMON'],
            [BankCode::Btn,     'BTN'],
            [BankCode::Maybank, 'MAYBANK'],
        ];
    }

    public function test_from_throws_on_unknown_value(): void
    {
        $this->expectException(\ValueError::class);
        BankCode::from('UNKNOWN_BANK');
    }
}
