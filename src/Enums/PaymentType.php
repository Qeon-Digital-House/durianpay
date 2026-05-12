<?php

declare(strict_types=1);

namespace QDH\DurianPay\Enums;

enum PaymentType: string
{
    case EWallet        = 'EWALLET';
    case VirtualAccount = 'VA';
    case RetailStore    = 'RETAILSTORE';
    case OnlineBanking  = 'ONLINE_BANKING';
    case BuyNowPayLater = 'BNPL';
    case Qris           = 'QRIS';
}
