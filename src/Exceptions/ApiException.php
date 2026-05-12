<?php

declare(strict_types=1);

namespace QDH\DurianPay\Exceptions;

class ApiException extends DurianPayException
{
    public function __construct(
        public readonly int $statusCode,
        public readonly array $responseBody,
        string $message = '',
    ) {
        parent::__construct($message ?: (string) json_encode($responseBody), $statusCode);
    }
}
