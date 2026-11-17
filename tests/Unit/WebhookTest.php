<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Webhook;

class WebhookTest extends TestCase
{
    public function test_verify_round_trips_a_durianpay_style_signature(): void
    {
        $fixtures = dirname(__DIR__) . '/fixtures';
        $private  = (string) file_get_contents("{$fixtures}/private_key.pem");
        $public   = (string) file_get_contents("{$fixtures}/public_key.pem");

        $path = '/callback/v1.0/qr/qr-mpm-notify';
        $body = '{"originalReferenceNo":"pay_1","latestTransactionStatus":"00"}';
        $ts   = '2026-06-22T11:36:12+07:00';

        openssl_sign("POST:{$path}:" . hash('sha256', $body) . ":{$ts}", $sig, $private, OPENSSL_ALGO_SHA256);

        $this->assertTrue(Webhook::verify($path, $body, $ts, base64_encode($sig), $public));
        $this->assertFalse(Webhook::verify('/other/path', $body, $ts, base64_encode($sig), $public));
    }
}
