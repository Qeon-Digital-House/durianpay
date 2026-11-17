<?php

declare(strict_types=1);

namespace QDH\DurianPay;

use QDH\DurianPay\Http\Signature;

final class Webhook
{
    /**
     * Verifies a SNAP notification (VA, QRIS, eWallet).
     *
     * @param string $path      Relative path of your webhook URL as DurianPay called it, e.g. "/callback/v1.0/qr/qr-mpm-notify"
     * @param string $rawBody   Raw request body (php://input), not a re-encoded array
     * @param string $publicKey DurianPay's public key (PEM) for the matching environment
     */
    public static function verify(
        string $path,
        string $rawBody,
        string $timestamp,
        string $signature,
        string $publicKey,
    ): bool {
        return Signature::verifyNotification($path, $rawBody, $timestamp, $signature, $publicKey);
    }
}
