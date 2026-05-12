<?php

declare(strict_types=1);

namespace QDH\DurianPay\Enums;

enum OnlineBankingType: string
{
    case BriEpay         = 'BRI_EPAY';
    case CimbClicks      = 'CIMB_CLICKS';
    case DanamonOnline   = 'DANAMON_ONLINE';
    case MandiriClickpay = 'MANDIRI_CLICKPAY';
    case PermataNet      = 'PERMATA_NET';
}
