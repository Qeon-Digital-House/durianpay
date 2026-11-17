<?php

declare(strict_types=1);

namespace QDH\DurianPay\Enums;

/** SNAP `latestTransactionStatus` codes. */
enum PaymentStatus: string
{
    case Completed  = '00';
    case Initiated  = '01';
    case Processing = '02';
    case Pending    = '03';
    case Refunded   = '04';
    case Cancelled  = '05';
    case Failed     = '06';
    case NotFound   = '07';
    case Expired    = '08';
    case Rejected   = '09';

    public function isPaid(): bool
    {
        return $this === self::Completed;
    }

    public function isFinal(): bool
    {
        return !in_array($this, [self::Initiated, self::Processing, self::Pending], true);
    }
}
