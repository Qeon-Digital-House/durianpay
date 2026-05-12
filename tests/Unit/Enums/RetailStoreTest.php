<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Enums\RetailStore;

class RetailStoreTest extends TestCase
{
    /** @dataProvider caseProvider */
    public function test_case_value(RetailStore $case, string $expected): void
    {
        $this->assertSame($expected, $case->value);
    }

    public static function caseProvider(): array
    {
        return [
            [RetailStore::Alfamart,  'ALFAMART'],
            [RetailStore::Indomaret, 'INDOMARET'],
        ];
    }

    public function test_from_throws_on_unknown_value(): void
    {
        $this->expectException(\ValueError::class);
        RetailStore::from('UNKNOWN_STORE');
    }
}
