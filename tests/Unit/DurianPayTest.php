<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Api\Ewallet;
use QDH\DurianPay\Api\Qris;
use QDH\DurianPay\Api\VirtualAccounts;
use QDH\DurianPay\DurianPay;
use QDH\DurianPay\Enums\Environment;
use QDH\DurianPay\Exceptions\DurianPayException;

class DurianPayTest extends TestCase
{
    private const VARS = [
        'DURIANPAY_CLIENT_KEY',
        'DURIANPAY_CLIENT_SECRET',
        'DURIANPAY_PRIVATE_KEY',
        'DURIANPAY_PRODUCTION',
        'DURIANPAY_CHANNEL_ID',
    ];

    private array $savedEnv = [];

    protected function setUp(): void
    {
        foreach (self::VARS as $key) {
            $this->savedEnv[$key] = getenv($key);
            $this->unsetEnv($key);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->savedEnv as $key => $value) {
            if ($value === false) {
                $this->unsetEnv($key);
            } else {
                $this->setEnv($key, $value);
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

    private function fixturePath(string $file = ''): string
    {
        return dirname(__DIR__) . '/fixtures' . ($file === '' ? '' : "/{$file}");
    }

    private function setCredentials(): void
    {
        $this->setEnv('DURIANPAY_CLIENT_KEY', 'env-client-key');
        $this->setEnv('DURIANPAY_CLIENT_SECRET', 'env-secret');
        $this->setEnv('DURIANPAY_PRIVATE_KEY', $this->fixturePath('private_key.pem'));
    }

    private function make(Environment $env = Environment::Sandbox): DurianPay
    {
        return new DurianPay('ck', 'secret', 'pem', $env);
    }

    // ── constructor ──────────────────────────────────────────────────────

    public function test_constructor_stores_client_key(): void
    {
        $this->assertSame('ck', $this->make()->clientKey);
    }

    public function test_constructor_defaults_to_sandbox_and_default_channel(): void
    {
        $dp = new DurianPay('ck', 'secret', 'pem');

        $this->assertSame(Environment::Sandbox, $dp->environment);
        $this->assertSame('95221', $dp->channelId);
    }

    public function test_constructor_accepts_live_environment(): void
    {
        $this->assertSame(Environment::Live, $this->make(Environment::Live)->environment);
    }

    // ── lazy API instances ──────────────────────────────────────────────

    public function test_virtual_accounts_is_lazily_cached(): void
    {
        $dp = $this->make();

        $this->assertInstanceOf(VirtualAccounts::class, $dp->virtualAccounts());
        $this->assertSame($dp->virtualAccounts(), $dp->virtualAccounts());
    }

    public function test_ewallet_is_lazily_cached(): void
    {
        $dp = $this->make();

        $this->assertInstanceOf(Ewallet::class, $dp->ewallet());
        $this->assertSame($dp->ewallet(), $dp->ewallet());
    }

    public function test_qris_is_lazily_cached(): void
    {
        $dp = $this->make();

        $this->assertInstanceOf(Qris::class, $dp->qris());
        $this->assertSame($dp->qris(), $dp->qris());
    }

    // ── fromEnv ──────────────────────────────────────────────────────────

    public function test_from_env_reads_credentials(): void
    {
        $this->setCredentials();

        $dp = DurianPay::fromEnv();

        $this->assertSame('env-client-key', $dp->clientKey);
        $this->assertSame(Environment::Sandbox, $dp->environment);
    }

    public function test_from_env_accepts_inline_pem_with_escaped_newlines(): void
    {
        $this->setCredentials();
        $pem = (string) file_get_contents($this->fixturePath('private_key.pem'));
        $this->setEnv('DURIANPAY_PRIVATE_KEY', str_replace("\n", '\n', trim($pem)));

        $this->assertSame('env-client-key', DurianPay::fromEnv()->clientKey);
    }

    public function test_from_env_reads_channel_id(): void
    {
        $this->setCredentials();
        $this->setEnv('DURIANPAY_CHANNEL_ID', '95521');

        $this->assertSame('95521', DurianPay::fromEnv()->channelId);
    }

    /** @dataProvider productionProvider */
    public function test_from_env_production_flag(string $value, Environment $expected): void
    {
        $this->setCredentials();
        $this->setEnv('DURIANPAY_PRODUCTION', $value);

        $this->assertSame($expected, DurianPay::fromEnv()->environment);
    }

    public static function productionProvider(): array
    {
        return [
            ['true', Environment::Live],
            ['1', Environment::Live],
            ['false', Environment::Sandbox],
            ['0', Environment::Sandbox],
        ];
    }

    /** @dataProvider requiredVarProvider */
    public function test_from_env_throws_when_required_var_missing(string $var): void
    {
        $this->setCredentials();
        $this->unsetEnv($var);

        $this->expectException(DurianPayException::class);
        $this->expectExceptionMessageMatches("/{$var}/");

        DurianPay::fromEnv();
    }

    public static function requiredVarProvider(): array
    {
        return [['DURIANPAY_CLIENT_KEY'], ['DURIANPAY_CLIENT_SECRET'], ['DURIANPAY_PRIVATE_KEY']];
    }

    public function test_from_env_throws_when_private_key_unreadable(): void
    {
        $this->setCredentials();
        $this->setEnv('DURIANPAY_PRIVATE_KEY', '/nonexistent/key.pem');

        $this->expectException(DurianPayException::class);
        $this->expectExceptionMessageMatches('/DURIANPAY_PRIVATE_KEY/');

        DurianPay::fromEnv();
    }

    public function test_from_env_loads_dotenv_file_and_resolves_key_relative_to_it(): void
    {
        $dp = DurianPay::fromEnv(envPath: $this->fixturePath());

        $this->assertSame('fixture-client-key', $dp->clientKey);
        $this->assertSame(Environment::Sandbox, $dp->environment);
    }
}
