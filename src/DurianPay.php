<?php

declare(strict_types=1);

namespace QDH\DurianPay;

use QDH\DurianPay\Api\Orders;
use QDH\DurianPay\Api\Payments;
use QDH\DurianPay\Api\Qris;
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
