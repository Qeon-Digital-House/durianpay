<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Api\Orders;
use QDH\DurianPay\Api\Payments;
use QDH\DurianPay\Api\Qris;
use QDH\DurianPay\DurianPay;
use QDH\DurianPay\Exceptions\DurianPayException;

class DurianPayTest extends TestCase
{
    private const ENV_KEY     = 'QDH_DP_UNIT_TEST_KEY';
    private const FIXTURE_KEY = 'QDH_DP_FIXTURE_KEY';

    protected function tearDown(): void
    {
        foreach ([self::ENV_KEY, self::FIXTURE_KEY] as $key) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }

    public function test_constructor_stores_api_key(): void
    {
        $dp = new DurianPay('my-key');

        $this->assertSame('my-key', $dp->apiKey);
    }

    public function test_orders_returns_orders_instance(): void
    {
        $this->assertInstanceOf(Orders::class, (new DurianPay('k'))->orders());
    }

    public function test_orders_is_lazily_cached(): void
    {
        $dp = new DurianPay('k');

        $this->assertSame($dp->orders(), $dp->orders());
    }

    public function test_payments_returns_payments_instance(): void
    {
        $this->assertInstanceOf(Payments::class, (new DurianPay('k'))->payments());
    }

    public function test_payments_is_lazily_cached(): void
    {
        $dp = new DurianPay('k');

        $this->assertSame($dp->payments(), $dp->payments());
    }

    public function test_qris_returns_qris_instance(): void
    {
        $this->assertInstanceOf(Qris::class, (new DurianPay('k'))->qris());
    }

    public function test_qris_is_lazily_cached(): void
    {
        $dp = new DurianPay('k');

        $this->assertSame($dp->qris(), $dp->qris());
    }

    public function test_from_env_reads_env_variable(): void
    {
        putenv(self::ENV_KEY . '=my-env-key');
        $_ENV[self::ENV_KEY] = 'my-env-key';

        $dp = DurianPay::fromEnv(envKey: self::ENV_KEY);

        $this->assertSame('my-env-key', $dp->apiKey);
    }

    public function test_from_env_supports_custom_key_name(): void
    {
        putenv(self::ENV_KEY . '=custom-key');
        $_ENV[self::ENV_KEY] = 'custom-key';

        $dp = DurianPay::fromEnv(envKey: self::ENV_KEY);

        $this->assertSame('custom-key', $dp->apiKey);
    }

    public function test_from_env_throws_when_variable_is_missing(): void
    {
        putenv(self::ENV_KEY);
        unset($_ENV[self::ENV_KEY], $_SERVER[self::ENV_KEY]);

        $this->expectException(DurianPayException::class);
        $this->expectExceptionMessageMatches('/"' . self::ENV_KEY . '"/');

        DurianPay::fromEnv(envKey: self::ENV_KEY);
    }

    public function test_from_env_throws_when_variable_is_empty(): void
    {
        putenv(self::ENV_KEY . '=');
        $_ENV[self::ENV_KEY] = '';

        $this->expectException(DurianPayException::class);

        DurianPay::fromEnv(envKey: self::ENV_KEY);
    }

    public function test_from_env_loads_dotenv_file(): void
    {
        // Ensure key is absent so phpdotenv (immutable) can set it.
        putenv(self::FIXTURE_KEY);
        unset($_ENV[self::FIXTURE_KEY], $_SERVER[self::FIXTURE_KEY]);

        $dp = DurianPay::fromEnv(
            envKey:  self::FIXTURE_KEY,
            envPath: dirname(__DIR__) . '/fixtures',
        );

        $this->assertSame('fixture-api-key', $dp->apiKey);
    }
}
