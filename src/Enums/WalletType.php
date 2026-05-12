<?php

declare(strict_types=1);

namespace QDH\DurianPay\Enums;

enum WalletType: string
{
    case Dana      = 'DANA';
    case Ovo       = 'OVO';
    case LinkAja   = 'LINKAJA';
    case GoPay     = 'GOPAY';
    case ShopeePay = 'SHOPEEPAY';
    case JeniusPay = 'JENIUSPAY';
    case AstraPay  = 'ASTRAPAY';
}
