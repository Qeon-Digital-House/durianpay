<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Enums\BnplType;

class BnplTypeTest extends TestCase
{
    /** @dataProvider caseProvider */
    public function test_case_value(BnplType $case, string $expected): void
    {
        $this->assertSame($expected, $case->value);
    }

    public static function caseProvider(): array
    {
        return [
            [BnplType::Kredivo,   'KREDIVO'],
            [BnplType::Akulaku,   'AKULAKU'],
            [BnplType::Indodana,  'INDODANA'],
            [BnplType::SpayLater, 'SPAYLATER'],
        ];
    }

    public function test_from_throws_on_unknown_value(): void
    {
        $this->expectException(\ValueError::class);
        BnplType::from('UNKNOWN_BNPL');
    }
}
