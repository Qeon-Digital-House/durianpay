<?php

declare(strict_types=1);

namespace QDH\DurianPay\Support;

use QDH\DurianPay\Enums\Currency;

final class Amount
{
    /**
     * SNAP amount object: value is a string with exactly two decimals, e.g. "10000.00".
     *
     * @return array{value: string, currency: string}
     */
    public static function of(int|float|string $value, Currency $currency = Currency::Idr): array
    {
        return [
            'value'    => number_format((float) $value, 2, '.', ''),
            'currency' => $currency->value,
        ];
    }
}
