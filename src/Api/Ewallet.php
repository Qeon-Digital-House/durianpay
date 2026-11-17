<?php

declare(strict_types=1);

namespace QDH\DurianPay\Api;

use QDH\DurianPay\Enums\PaymentStatus;
use QDH\DurianPay\Enums\WalletType;
use QDH\DurianPay\Support\Amount;

class Ewallet extends AbstractApi
{
    public const SERVICE_CODE = '54';

    /**
     * POST /v1.0/debit/payment-host-to-host — send the customer to `webRedirectUrl` (not returned for OVO).
     * $additionalInfo accepts expiryDate and customerInfo.
     */
    public function create(
        string $partnerReferenceNo,
        int|float|string $amount,
        WalletType $channel,
        string $phoneNumber,
        array $additionalInfo = [],
    ): array {
        return $this->post('debit/payment-host-to-host', [
            'partnerReferenceNo' => $partnerReferenceNo,
            'amount'             => Amount::of($amount),
            'additionalInfo'     => array_merge($additionalInfo, [
                'phoneNumber' => $phoneNumber,
                'channelCode' => $channel->value,
            ]),
        ]);
    }

    /** POST /v1.0/debit/status — $referenceNo is the `referenceNo` returned by create(). */
    public function query(string $referenceNo): array
    {
        return $this->post('debit/status', [
            'originalReferenceNo' => $referenceNo,
            'serviceCode'         => self::SERVICE_CODE,
        ]);
    }

    public function status(string $referenceNo): PaymentStatus
    {
        return $this->toStatus($this->query($referenceNo));
    }

    /** POST /v1.0/debit/cancel — only payments that are not yet completed. */
    public function cancel(string $referenceNo, string $partnerReferenceNo, ?string $reason = null): array
    {
        return $this->post('debit/cancel', array_filter([
            'originalReferenceNo'        => $referenceNo,
            'originalPartnerReferenceNo' => $partnerReferenceNo,
            'reason'                     => $reason,
        ], fn ($v) => $v !== null));
    }

    /** POST /v1.0/debit/refund — only completed payments. */
    public function refund(
        string $referenceNo,
        string $partnerReferenceNo,
        string $partnerRefundNo,
        int|float|string $amount,
    ): array {
        return $this->post('debit/refund', [
            'originalReferenceNo'        => $referenceNo,
            'originalPartnerReferenceNo' => $partnerReferenceNo,
            'partnerRefundNo'            => $partnerRefundNo,
            'refundAmount'               => Amount::of($amount),
        ]);
    }
}
