<?php

declare(strict_types=1);

namespace QDH\DurianPay\Exceptions;

class ApiException extends DurianPayException
{
    public readonly ?string $responseCode;
    public readonly ?string $responseMessage;

    public function __construct(
        public readonly int $statusCode,
        public readonly array $responseBody,
        string $message = '',
    ) {
        $this->responseCode    = isset($responseBody['responseCode']) ? (string) $responseBody['responseCode'] : null;
        $this->responseMessage = isset($responseBody['responseMessage']) ? (string) $responseBody['responseMessage'] : null;

        parent::__construct($message ?: (string) json_encode($responseBody), $statusCode);
    }

    public function traceId(): ?string
    {
        return $this->responseBody['additionalInfo']['trace_id'] ?? null;
    }
}
