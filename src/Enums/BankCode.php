<?php

declare(strict_types=1);

namespace QDH\DurianPay\Enums;

enum BankCode: string
{
    case Bca     = 'BCA';
    case Bni     = 'BNI';
    case Bri     = 'BRI';
    case Mandiri = 'MANDIRI';
    case Permata = 'PERMATA';
    case Cimb    = 'CIMB';
    case Nobu    = 'NOBU';
    case Ina     = 'INA';
}
