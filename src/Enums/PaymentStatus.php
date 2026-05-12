<?php

declare(strict_types=1);

namespace QDH\DurianPay\Enums;

enum PaymentStatus: string
{
    case Started    = 'STARTED';
    case Processing = 'PROCESSING';
    case Completed  = 'COMPLETED';
    case Failed     = 'FAILED';
    case Cancelled  = 'CANCELLED';
    case Expired    = 'EXPIRED';
    case Pending    = 'PENDING';
}
