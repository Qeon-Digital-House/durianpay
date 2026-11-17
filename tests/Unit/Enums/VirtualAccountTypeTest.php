<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Enums\VirtualAccountType;

class VirtualAccountTypeTest extends TestCase
{
    /** @dataProvider caseProvider */
    public function test_case_value(VirtualAccountType $case, string $expected): void
    {
        $this->assertSame($expected, $case->value);
    }

    public static function caseProvider(): array
    {
        return [
            [VirtualAccountType::Closed, 'C'],
            [VirtualAccountType::Open,   'O'],
        ];
    }

    public function test_from_throws_on_unknown_value(): void
    {
        $this->expectException(\ValueError::class);
        VirtualAccountType::from('X');
    }
}
