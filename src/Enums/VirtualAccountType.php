<?php

declare(strict_types=1);

namespace QDH\DurianPay\Enums;

enum VirtualAccountType: string
{
    case Closed = 'C';
    case Open   = 'O';
}
