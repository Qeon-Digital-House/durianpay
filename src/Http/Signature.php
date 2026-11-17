<?php

declare(strict_types=1);

namespace QDH\DurianPay\Http;

use QDH\DurianPay\Exceptions\DurianPayException;

final class Signature
{
    /** SHA256withRSA over "{clientKey}|{timestamp}" — used only for /access-token/b2b. */
    public static function asymmetric(string $clientKey, string $timestamp, string $privateKey): string
    {
        $key = openssl_pkey_get_private($privateKey);
        if ($key === false) {
            throw new DurianPayException('Invalid DurianPay private key: ' . (string) openssl_error_string());
        }

        if (!openssl_sign("{$clientKey}|{$timestamp}", $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new DurianPayException('Failed to sign access token request: ' . (string) openssl_error_string());
        }

        return base64_encode($signature);
    }

    /** HMAC-SHA512 over "METHOD:/v1.0/path:token:sha256hex(body):timestamp" — used for every transaction call. */
    public static function symmetric(
        string $method,
        string $relativePath,
        string $accessToken,
        string $body,
        string $timestamp,
        string $clientSecret,
    ): string {
        $stringToSign = implode(':', [
            strtoupper($method),
            $relativePath,
            $accessToken,
            self::bodyHash($body),
            $timestamp,
        ]);

        return base64_encode(hash_hmac('sha512', $stringToSign, $clientSecret, true));
    }

    /** Verifies an inbound DurianPay notification signed with DurianPay's private key. */
    public static function verifyNotification(
        string $relativePath,
        string $body,
        string $timestamp,
        string $signature,
        string $durianPayPublicKey,
    ): bool {
        $key = openssl_pkey_get_public($durianPayPublicKey);
        if ($key === false) {
            throw new DurianPayException('Invalid DurianPay public key: ' . (string) openssl_error_string());
        }

        $decoded = base64_decode($signature, true);
        if ($decoded === false || $decoded === '') {
            return false;
        }

        // DurianPay signs the minified body; the raw payload we receive may or may not be minified already.
        foreach (array_unique([$body, self::minify($body)]) as $candidate) {
            $stringToSign = "POST:{$relativePath}:" . self::bodyHash($candidate) . ":{$timestamp}";
            if (openssl_verify($stringToSign, $decoded, $key, OPENSSL_ALGO_SHA256) === 1) {
                return true;
            }
        }

        return false;
    }

    public static function bodyHash(string $body): string
    {
        return strtolower(hash('sha256', $body));
    }

    public static function encodeBody(array $body): string
    {
        return (string) json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private static function minify(string $json): string
    {
        $decoded = json_decode($json, associative: true);

        return is_array($decoded) ? self::encodeBody($decoded) : $json;
    }
}
