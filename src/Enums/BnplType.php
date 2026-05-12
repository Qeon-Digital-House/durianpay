<?php

declare(strict_types=1);

namespace QDH\DurianPay\Enums;

enum BnplType: string
{
    case Kredivo   = 'KREDIVO';
    case Akulaku   = 'AKULAKU';
    case Indodana  = 'INDODANA';
    case SpayLater = 'SPAYLATER';
}
