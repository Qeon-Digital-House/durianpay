<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Enums\Environment;

class EnvironmentTest extends TestCase
{
    public function test_sandbox_value(): void
    {
        $this->assertSame('sandbox', Environment::Sandbox->value);
    }

    public function test_live_value(): void
    {
        $this->assertSame('live', Environment::Live->value);
    }

    public function test_from_resolves_sandbox(): void
    {
        $this->assertSame(Environment::Sandbox, Environment::from('sandbox'));
    }

    public function test_from_resolves_live(): void
    {
        $this->assertSame(Environment::Live, Environment::from('live'));
    }

    public function test_from_throws_on_unknown_value(): void
    {
        $this->expectException(\ValueError::class);
        Environment::from('production');
    }

    public function test_try_from_returns_null_on_unknown_value(): void
    {
        $this->assertNull(Environment::tryFrom('staging'));
    }
}
