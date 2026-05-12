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
    case Bsi     = 'BSI';
    case Cimb    = 'CIMB';
    case Danamon = 'DANAMON';
    case Btn     = 'BTN';
    case Maybank = 'MAYBANK';
}
