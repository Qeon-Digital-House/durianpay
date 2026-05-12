<?php

declare(strict_types=1);

namespace QDH\DurianPay\Enums;

enum Environment: string
{
    case Sandbox = 'sandbox';
    case Live    = 'live';
}
