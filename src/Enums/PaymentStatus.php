<?php

declare(strict_types=1);

namespace QDH\DurianPay\Enums;

enum PaymentStatus: string
{
    case Started    = 'started';
    case Processing = 'processing';
    case Completed  = 'completed';
    case Failed     = 'failed';
    case Cancelled  = 'cancelled';
    case Expired    = 'expired';
    case Pending    = 'pending';
}
