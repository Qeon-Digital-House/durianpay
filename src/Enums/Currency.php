<?php

declare(strict_types=1);

namespace QDH\DurianPay\Enums;

/**
 * DurianPay operates exclusively in Indonesia.
 * IDR is the only supported currency.
 */
enum Currency: string
{
    case Idr = 'IDR';
}
