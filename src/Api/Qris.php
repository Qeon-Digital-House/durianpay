<?php

declare(strict_types=1);

namespace QDH\DurianPay\Api;

use QDH\DurianPay\Enums\PaymentStatus;
use QDH\DurianPay\Support\Amount;

class Qris extends AbstractApi
{
    public const SERVICE_CODE = '47';

    /** POST /v1.0/qr/qr-mpm-generate — $additionalInfo accepts expiryDate and customerInfo. */
    public function generate(string $partnerReferenceNo, int|float|string $amount, array $additionalInfo = []): array
    {
        return $this->post('qr/qr-mpm-generate', [
            'partnerReferenceNo' => $partnerReferenceNo,
            'amount'             => Amount::of($amount),
            'additionalInfo'     => $additionalInfo === [] ? new \stdClass() : $additionalInfo,
        ]);
    }

    /** POST /v1.0/qr/qr-mpm-query — $referenceNo is the `referenceNo` returned by generate(). */
    public function query(string $referenceNo): array
    {
        return $this->post('qr/qr-mpm-query', [
            'originalReferenceNo' => $referenceNo,
            'serviceCode'         => self::SERVICE_CODE,
        ]);
    }

    public function status(string $referenceNo): PaymentStatus
    {
        return $this->toStatus($this->query($referenceNo));
    }

    /** POST /v1.0/qr/qr-mpm-cancel — only payments that are not yet completed. */
    public function cancel(
        string $referenceNo,
        string $partnerReferenceNo,
        string $merchantId,
        string $reason = 'cancelled by merchant',
    ): array {
        return $this->post('qr/qr-mpm-cancel', [
            'merchantId'                 => $merchantId,
            'originalReferenceNo'        => $referenceNo,
            'originalPartnerReferenceNo' => $partnerReferenceNo,
            'reason'                     => $reason,
        ]);
    }

    /** POST /v1.0/qr/qr-mpm-refund — only completed payments. */
    public function refund(
        string $referenceNo,
        string $partnerReferenceNo,
        string $partnerRefundNo,
        int|float|string $amount,
    ): array {
        return $this->post('qr/qr-mpm-refund', [
            'originalReferenceNo'        => $referenceNo,
            'originalPartnerReferenceNo' => $partnerReferenceNo,
            'partnerRefundNo'            => $partnerRefundNo,
            'refundAmount'               => Amount::of($amount),
        ]);
    }
}
