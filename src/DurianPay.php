<?php

declare(strict_types=1);

namespace QDH\DurianPay;

use Dotenv\Dotenv;
use QDH\DurianPay\Api\Orders;
use QDH\DurianPay\Api\Payments;
use QDH\DurianPay\Api\Qris;
use QDH\DurianPay\Enums\Environment;
use QDH\DurianPay\Exceptions\DurianPayException;
use QDH\DurianPay\Http\HttpClient;

class DurianPay
{
    private const BASE_URLS = [
        'live'    => 'https://api.durianpay.id/v1/',
        'sandbox' => 'https://api-sandbox.durianpay.id/v1/',
    ];

    private readonly HttpClient $http;
    private ?Orders $orders     = null;
    private ?Payments $payments = null;
    private ?Qris $qris         = null;

    public function __construct(
        public readonly string $apiKey,
        public readonly Environment $environment = Environment::Sandbox,
    ) {
        $this->http = new HttpClient($this->apiKey, self::BASE_URLS[$this->environment->value]);
    }

    /**
     * Instantiate from environment variables.
     *
     * Reads DURIANPAY_API_KEY and DURIANPAY_PRODUCTION (true = Live, false/missing = Sandbox).
     * Pass $envPath to auto-load a .env file from that directory first.
     */
    public static function fromEnv(?string $envPath = null): static
    {
        if ($envPath !== null) {
            Dotenv::createImmutable($envPath)->load();
        }

        $apiKey = getenv('DURIANPAY_API_KEY');
        if ($apiKey === false || $apiKey === '') {
            $apiKey = $_ENV['DURIANPAY_API_KEY'] ?? '';
        }

        if ($apiKey === '') {
            throw new DurianPayException(
                'DurianPay API key not found. Set the "DURIANPAY_API_KEY" environment variable.'
            );
        }

        $production = getenv('DURIANPAY_PRODUCTION');
        if ($production === false || $production === '') {
            $production = $_ENV['DURIANPAY_PRODUCTION'] ?? 'false';
        }

        $environment = filter_var($production, FILTER_VALIDATE_BOOLEAN)
            ? Environment::Live
            : Environment::Sandbox;

        return new static($apiKey, $environment);
    }

    public function orders(): Orders
    {
        return $this->orders ??= new Orders($this->http);
    }

    public function payments(): Payments
    {
        return $this->payments ??= new Payments($this->http);
    }

    public function qris(): Qris
    {
        return $this->qris ??= new Qris($this->http);
    }
}
