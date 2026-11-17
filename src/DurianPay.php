<?php

declare(strict_types=1);

namespace QDH\DurianPay;

use Dotenv\Dotenv;
use QDH\DurianPay\Api\Ewallet;
use QDH\DurianPay\Api\Qris;
use QDH\DurianPay\Api\VirtualAccounts;
use QDH\DurianPay\Enums\Environment;
use QDH\DurianPay\Exceptions\DurianPayException;
use QDH\DurianPay\Http\HttpClient;

class DurianPay
{
    private const BASE_URLS = [
        'live'    => 'https://api.durianpay.id',
        'sandbox' => 'https://api-sandbox.durianpay.id',
    ];

    private readonly HttpClient $http;
    private ?VirtualAccounts $virtualAccounts = null;
    private ?Ewallet $ewallet                 = null;
    private ?Qris $qris                       = null;

    /**
     * @param string $clientKey    Client Key from the dashboard (also sent as X-PARTNER-ID)
     * @param string $clientSecret Client Secret Key — HMAC key for transaction signatures
     * @param string $privateKey   Merchant RSA private key (PEM) whose public half is uploaded to the dashboard
     */
    public function __construct(
        public readonly string $clientKey,
        #[\SensitiveParameter] string $clientSecret,
        #[\SensitiveParameter] string $privateKey,
        public readonly Environment $environment = Environment::Sandbox,
        public readonly string $channelId = HttpClient::DEFAULT_CHANNEL,
        ?HttpClient $http = null,
    ) {
        $this->http = $http ?? new HttpClient(
            $this->clientKey,
            $clientSecret,
            $privateKey,
            self::BASE_URLS[$this->environment->value],
            $this->channelId,
        );
    }

    /**
     * Instantiate from environment variables.
     *
     * Reads DURIANPAY_CLIENT_KEY, DURIANPAY_CLIENT_SECRET, DURIANPAY_PRIVATE_KEY (PEM contents or a file path),
     * DURIANPAY_PRODUCTION (true = Live, false/missing = Sandbox) and optional DURIANPAY_CHANNEL_ID.
     * Pass $envPath to auto-load a .env file from that directory first.
     */
    public static function fromEnv(?string $envPath = null): static
    {
        if ($envPath !== null) {
            Dotenv::createImmutable($envPath)->load();
        }

        $environment = filter_var(self::env('DURIANPAY_PRODUCTION') ?? 'false', FILTER_VALIDATE_BOOLEAN)
            ? Environment::Live
            : Environment::Sandbox;

        return new static(
            self::requireEnv('DURIANPAY_CLIENT_KEY'),
            self::requireEnv('DURIANPAY_CLIENT_SECRET'),
            self::loadKey(self::requireEnv('DURIANPAY_PRIVATE_KEY'), $envPath),
            $environment,
            self::env('DURIANPAY_CHANNEL_ID') ?? HttpClient::DEFAULT_CHANNEL,
        );
    }

    public function virtualAccounts(): VirtualAccounts
    {
        return $this->virtualAccounts ??= new VirtualAccounts($this->http);
    }

    public function ewallet(): Ewallet
    {
        return $this->ewallet ??= new Ewallet($this->http);
    }

    public function qris(): Qris
    {
        return $this->qris ??= new Qris($this->http);
    }

    private static function env(string $key): ?string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            $value = $_ENV[$key] ?? '';
        }

        return $value === '' ? null : (string) $value;
    }

    private static function requireEnv(string $key): string
    {
        return self::env($key) ?? throw new DurianPayException(
            "DurianPay credential not found. Set the \"{$key}\" environment variable."
        );
    }

    private static function loadKey(string $value, ?string $envPath): string
    {
        if (str_contains($value, '-----BEGIN')) {
            // Single-line .env values usually carry the PEM newlines as literal "\n".
            return str_replace('\n', "\n", $value);
        }

        foreach ([$value, $envPath !== null ? rtrim($envPath, '/') . '/' . $value : null] as $path) {
            if ($path !== null && is_file($path)) {
                return (string) file_get_contents($path);
            }
        }

        throw new DurianPayException("DURIANPAY_PRIVATE_KEY is neither a PEM key nor a readable file: \"{$value}\"");
    }
}
