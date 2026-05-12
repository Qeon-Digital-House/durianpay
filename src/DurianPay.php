<?php

declare(strict_types=1);

namespace QDH\DurianPay;

use Dotenv\Dotenv;
use QDH\DurianPay\Api\Orders;
use QDH\DurianPay\Api\Payments;
use QDH\DurianPay\Api\Qris;
use QDH\DurianPay\Exceptions\DurianPayException;
use QDH\DurianPay\Http\HttpClient;

class DurianPay
{
    private const BASE_URL = 'https://api.durianpay.id/v1/';

    private readonly HttpClient $http;
    private ?Orders $orders     = null;
    private ?Payments $payments = null;
    private ?Qris $qris         = null;

    public function __construct(public readonly string $apiKey)
    {
        $this->http = new HttpClient($this->apiKey, self::BASE_URL);
    }

    /**
     * Instantiate from an environment variable.
     *
     * @param string      $envKey  Name of the env variable (default: DURIANPAY_API_KEY)
     * @param string|null $envPath Directory containing the .env file. When provided,
     *                             the file is loaded before reading the variable.
     */
    public static function fromEnv(
        string $envKey = 'DURIANPAY_API_KEY',
        ?string $envPath = null,
    ): static {
        if ($envPath !== null) {
            Dotenv::createImmutable($envPath)->load();
        }

        $apiKey = getenv($envKey);

        if ($apiKey === false || $apiKey === '') {
            $apiKey = $_ENV[$envKey] ?? '';
        }

        if ($apiKey === '') {
            throw new DurianPayException(
                "DurianPay API key not found. Set the \"{$envKey}\" environment variable."
            );
        }

        return new static($apiKey);
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
