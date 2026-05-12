<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Enums\Currency;

class CurrencyTest extends TestCase
{
    public function test_idr_value(): void
    {
        $this->assertSame('IDR', Currency::Idr->value);
    }

    public function test_from_resolves_idr(): void
    {
        $this->assertSame(Currency::Idr, Currency::from('IDR'));
    }

    public function test_from_throws_on_unsupported_currency(): void
    {
        $this->expectException(\ValueError::class);
        Currency::from('USD');
    }
}
