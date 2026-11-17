<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Support\Amount;

class AmountTest extends TestCase
{
    /** @dataProvider valueProvider */
    public function test_formats_value_with_two_decimals(int|float|string $input, string $expected): void
    {
        $this->assertSame(['value' => $expected, 'currency' => 'IDR'], Amount::of($input));
    }

    public static function valueProvider(): array
    {
        return [
            [10000, '10000.00'],
            [10000.5, '10000.50'],
            ['25000', '25000.00'],
            [1500000, '1500000.00'],
        ];
    }
}
