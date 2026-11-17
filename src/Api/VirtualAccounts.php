<?php

declare(strict_types=1);

namespace QDH\DurianPay\Api;

use QDH\DurianPay\Enums\BankCode;
use QDH\DurianPay\Enums\VirtualAccountType;
use QDH\DurianPay\Support\Amount;

class VirtualAccounts extends AbstractApi
{
    /**
     * POST /v1.0/transfer-va/create-va
     *
     * Passing an amount creates a closed-amount VA ("C"); null creates an open-amount VA ("O").
     * $options may hold customerNo, expiredDate and an `additionalInfo` array (isReusable, customerInfo).
     */
    public function create(
        BankCode $bank,
        string $trxId,
        string $name,
        int|float|string|null $amount = null,
        array $options = [],
    ): array {
        $additionalInfo = array_merge($options['additionalInfo'] ?? [], ['bankCode' => $bank->value]);
        unset($options['additionalInfo']);

        $body = array_merge($options, [
            'virtualAccountName'    => $name,
            'virtualAccountTrxType' => ($amount === null ? VirtualAccountType::Open : VirtualAccountType::Closed)->value,
            'trxId'                 => $trxId,
            'additionalInfo'        => $additionalInfo,
        ]);

        if ($amount !== null) {
            $body['totalAmount'] = Amount::of($amount);
        }

        return $this->post('transfer-va/create-va', $body);
    }

    /** POST /v1.0/transfer-va/inquiry-va — identifiers come from create()'s `virtualAccountData`. */
    public function inquiry(string $partnerServiceId, string $customerNo, string $virtualAccountNo, string $trxId): array
    {
        return $this->post('transfer-va/inquiry-va', [
            'partnerServiceId' => $partnerServiceId,
            'customerNo'       => $customerNo,
            'virtualAccountNo' => $virtualAccountNo,
            'trxId'            => $trxId,
        ]);
    }

    /**
     * PUT /v1.0/transfer-va/update-va
     *
     * $params must hold partnerServiceId, customerNo, virtualAccountNo, virtualAccountName, trxId,
     * plus the fields to change (expiredDate, totalAmount). Setting expiredDate to now expires the VA.
     */
    public function update(array $params): array
    {
        return $this->put('transfer-va/update-va', $params);
    }
}
