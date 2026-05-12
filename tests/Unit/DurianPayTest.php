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
    private const API_KEY_VAR    = 'DURIANPAY_API_KEY';
    private const PRODUCTION_VAR = 'DURIANPAY_PRODUCTION';

    private array $savedEnv = [];

    protected function setUp(): void
    {
        foreach ([self::API_KEY_VAR, self::PRODUCTION_VAR] as $key) {
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

    // ── constructor ──────────────────────────────────────────────────────

    public function test_constructor_stores_api_key(): void
    {
        $this->assertSame('my-key', (new DurianPay('my-key'))->apiKey);
    }

    public function test_constructor_defaults_to_sandbox(): void
    {
        $this->assertSame(Environment::Sandbox, (new DurianPay('k'))->environment);
    }

    public function test_constructor_accepts_live_environment(): void
    {
        $dp = new DurianPay('k', Environment::Live);

        $this->assertSame(Environment::Live, $dp->environment);
    }

    public function test_constructor_accepts_sandbox_environment(): void
    {
        $dp = new DurianPay('k', Environment::Sandbox);

        $this->assertSame(Environment::Sandbox, $dp->environment);
    }

    // ── lazy API instances ──────────────────────────────────────────────

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

    // ── fromEnv ──────────────────────────────────────────────────────────

    public function test_from_env_reads_api_key(): void
    {
        $this->setEnv(self::API_KEY_VAR, 'env-api-key');

        $this->assertSame('env-api-key', DurianPay::fromEnv()->apiKey);
    }

    public function test_from_env_production_true_gives_live(): void
    {
        $this->setEnv(self::API_KEY_VAR, 'key');
        $this->setEnv(self::PRODUCTION_VAR, 'true');

        $this->assertSame(Environment::Live, DurianPay::fromEnv()->environment);
    }

    public function test_from_env_production_false_gives_sandbox(): void
    {
        $this->setEnv(self::API_KEY_VAR, 'key');
        $this->setEnv(self::PRODUCTION_VAR, 'false');

        $this->assertSame(Environment::Sandbox, DurianPay::fromEnv()->environment);
    }

    public function test_from_env_production_missing_defaults_to_sandbox(): void
    {
        $this->setEnv(self::API_KEY_VAR, 'key');
        $this->unsetEnv(self::PRODUCTION_VAR);

        $this->assertSame(Environment::Sandbox, DurianPay::fromEnv()->environment);
    }

    public function test_from_env_production_one_gives_live(): void
    {
        $this->setEnv(self::API_KEY_VAR, 'key');
        $this->setEnv(self::PRODUCTION_VAR, '1');

        $this->assertSame(Environment::Live, DurianPay::fromEnv()->environment);
    }

    public function test_from_env_production_zero_gives_sandbox(): void
    {
        $this->setEnv(self::API_KEY_VAR, 'key');
        $this->setEnv(self::PRODUCTION_VAR, '0');

        $this->assertSame(Environment::Sandbox, DurianPay::fromEnv()->environment);
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
        $this->unsetEnv(self::API_KEY_VAR);
        $this->unsetEnv(self::PRODUCTION_VAR);

        $dp = DurianPay::fromEnv(envPath: dirname(__DIR__) . '/fixtures');

        $this->assertSame('fixture-api-key', $dp->apiKey);
        $this->assertSame(Environment::Sandbox, $dp->environment);
    }
}
