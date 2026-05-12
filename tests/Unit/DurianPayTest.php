<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Api\Orders;
use QDH\DurianPay\Api\Payments;
use QDH\DurianPay\Api\Qris;
use QDH\DurianPay\DurianPay;
use QDH\DurianPay\Enums\Environment;
use QDH\DurianPay\Exceptions\DurianPayException;

class DurianPayTest extends TestCase
{
    private const API_KEY_VAR = 'DURIANPAY_API_KEY';
    private const ENV_VAR     = 'DURIANPAY_ENV';

    /** Saved env state restored in tearDown. */
    private array $savedEnv = [];

    protected function setUp(): void
    {
        foreach ([self::API_KEY_VAR, self::ENV_VAR] as $key) {
            $this->savedEnv[$key] = getenv($key);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->savedEnv as $key => $value) {
            if ($value === false) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }
    }

    // ── constructor ────────────────────────────────────────────────────────

    public function test_constructor_stores_api_key(): void
    {
        $dp = new DurianPay('my-key');

        $this->assertSame('my-key', $dp->apiKey);
    }

    public function test_constructor_defaults_to_live_environment(): void
    {
        $dp = new DurianPay('my-key');

        $this->assertSame(Environment::Live, $dp->environment);
    }

    public function test_constructor_accepts_sandbox_environment(): void
    {
        $dp = new DurianPay('my-key', Environment::Sandbox);

        $this->assertSame(Environment::Sandbox, $dp->environment);
    }

    // ── lazy API instances ─────────────────────────────────────────────────

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

    // ── fromEnv ────────────────────────────────────────────────────────────

    private function setEnv(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
    }

    private function unsetEnv(string $key): void
    {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }

    public function test_from_env_reads_api_key(): void
    {
        $this->setEnv(self::API_KEY_VAR, 'env-api-key');
        $this->setEnv(self::ENV_VAR, 'live');

        $dp = DurianPay::fromEnv();

        $this->assertSame('env-api-key', $dp->apiKey);
    }

    public function test_from_env_reads_sandbox_environment(): void
    {
        $this->setEnv(self::API_KEY_VAR, 'env-api-key');
        $this->setEnv(self::ENV_VAR, 'sandbox');

        $dp = DurianPay::fromEnv();

        $this->assertSame(Environment::Sandbox, $dp->environment);
    }

    public function test_from_env_reads_live_environment(): void
    {
        $this->setEnv(self::API_KEY_VAR, 'env-api-key');
        $this->setEnv(self::ENV_VAR, 'live');

        $dp = DurianPay::fromEnv();

        $this->assertSame(Environment::Live, $dp->environment);
    }

    public function test_from_env_defaults_to_live_when_env_var_missing(): void
    {
        $this->setEnv(self::API_KEY_VAR, 'env-api-key');
        $this->unsetEnv(self::ENV_VAR);

        $dp = DurianPay::fromEnv();

        $this->assertSame(Environment::Live, $dp->environment);
    }

    public function test_from_env_defaults_to_live_on_invalid_env_value(): void
    {
        $this->setEnv(self::API_KEY_VAR, 'env-api-key');
        $this->setEnv(self::ENV_VAR, 'production');

        $dp = DurianPay::fromEnv();

        $this->assertSame(Environment::Live, $dp->environment);
    }

    public function test_from_env_throws_when_api_key_missing(): void
    {
        $this->unsetEnv(self::API_KEY_VAR);

        $this->expectException(DurianPayException::class);
        $this->expectExceptionMessageMatches('/DURIANPAY_API_KEY/');

        DurianPay::fromEnv();
    }

    public function test_from_env_throws_when_api_key_is_empty(): void
    {
        $this->setEnv(self::API_KEY_VAR, '');

        $this->expectException(DurianPayException::class);

        DurianPay::fromEnv();
    }

    public function test_from_env_loads_dotenv_file(): void
    {
        // Clear vars so phpdotenv (immutable mode) can populate them.
        $this->unsetEnv(self::API_KEY_VAR);
        $this->unsetEnv(self::ENV_VAR);

        $dp = DurianPay::fromEnv(envPath: dirname(__DIR__) . '/fixtures');

        $this->assertSame('fixture-api-key', $dp->apiKey);
        $this->assertSame(Environment::Sandbox, $dp->environment);
    }
}
