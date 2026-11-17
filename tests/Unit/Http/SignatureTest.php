<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Exceptions\DurianPayException;
use QDH\DurianPay\Http\Signature;

class SignatureTest extends TestCase
{
    private string $privateKey;
    private string $publicKey;

    protected function setUp(): void
    {
        $this->privateKey = (string) file_get_contents(dirname(__DIR__, 2) . '/fixtures/private_key.pem');
        $this->publicKey  = (string) file_get_contents(dirname(__DIR__, 2) . '/fixtures/public_key.pem');
    }

    public function test_asymmetric_signs_client_key_pipe_timestamp_with_sha256_rsa(): void
    {
        $timestamp = '2024-05-13T14:53:06+07:00';
        $signature = Signature::asymmetric('client-key', $timestamp, $this->privateKey);

        $this->assertSame(1, openssl_verify(
            "client-key|{$timestamp}",
            base64_decode($signature, true),
            $this->publicKey,
            OPENSSL_ALGO_SHA256,
        ));
    }

    public function test_asymmetric_throws_on_invalid_private_key(): void
    {
        $this->expectException(DurianPayException::class);

        Signature::asymmetric('client-key', '2024-05-13T14:53:06+07:00', 'not-a-key');
    }

    public function test_symmetric_matches_snap_string_to_sign(): void
    {
        $body      = '{"partnerReferenceNo":"ref_1","amount":{"value":"10000.00","currency":"IDR"}}';
        $timestamp = '2024-05-13T15:07:07+07:00';

        $expectedString = 'POST:/v1.0/qr/qr-mpm-generate:token-abc:' . hash('sha256', $body) . ':' . $timestamp;
        $expected       = base64_encode(hash_hmac('sha512', $expectedString, 'secret', true));

        $this->assertSame(
            $expected,
            Signature::symmetric('post', '/v1.0/qr/qr-mpm-generate', 'token-abc', $body, $timestamp, 'secret'),
        );
    }

    public function test_body_hash_is_lowercase_sha256_hex(): void
    {
        $hash = Signature::bodyHash('{}');

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);
        $this->assertSame(hash('sha256', '{}'), $hash);
    }

    public function test_encode_body_is_minified_without_escaped_slashes_or_unicode(): void
    {
        $this->assertSame(
            '{"url":"https://x.id/a","name":"Budi Ñ"}',
            Signature::encodeBody(['url' => 'https://x.id/a', 'name' => 'Budi Ñ']),
        );
    }

    public function test_verify_notification_accepts_valid_signature(): void
    {
        $body = '{"originalReferenceNo":"pay_1","latestTransactionStatus":"00"}';

        $this->assertTrue(Signature::verifyNotification(
            '/callback/qris',
            $body,
            '2026-06-22T11:36:12+00:00',
            $this->sign('/callback/qris', $body, '2026-06-22T11:36:12+00:00'),
            $this->publicKey,
        ));
    }

    public function test_verify_notification_accepts_pretty_printed_body_signed_minified(): void
    {
        $minified = '{"originalReferenceNo":"pay_1","latestTransactionStatus":"00"}';
        $pretty   = json_encode(json_decode($minified, true), JSON_PRETTY_PRINT);
        $ts       = '2026-06-22T11:36:12+00:00';

        $this->assertTrue(Signature::verifyNotification(
            '/callback/qris',
            (string) $pretty,
            $ts,
            $this->sign('/callback/qris', $minified, $ts),
            $this->publicKey,
        ));
    }

    public function test_verify_notification_rejects_tampered_body(): void
    {
        $ts  = '2026-06-22T11:36:12+00:00';
        $sig = $this->sign('/callback/qris', '{"latestTransactionStatus":"08"}', $ts);

        $this->assertFalse(Signature::verifyNotification(
            '/callback/qris',
            '{"latestTransactionStatus":"00"}',
            $ts,
            $sig,
            $this->publicKey,
        ));
    }

    public function test_verify_notification_rejects_garbage_signature(): void
    {
        $this->assertFalse(Signature::verifyNotification('/cb', '{}', 'ts', '!!not-base64!!', $this->publicKey));
    }

    private function sign(string $path, string $body, string $timestamp): string
    {
        openssl_sign("POST:{$path}:" . hash('sha256', $body) . ":{$timestamp}", $sig, $this->privateKey, OPENSSL_ALGO_SHA256);

        return base64_encode($sig);
    }
}
