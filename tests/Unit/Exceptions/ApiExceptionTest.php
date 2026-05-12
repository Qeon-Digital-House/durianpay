<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Exceptions\ApiException;
use QDH\DurianPay\Exceptions\DurianPayException;

class ApiExceptionTest extends TestCase
{
    public function test_extends_durianpay_exception(): void
    {
        $this->assertInstanceOf(DurianPayException::class, new ApiException(400, []));
    }

    public function test_status_code_and_body_are_stored(): void
    {
        $body = ['error' => 'invalid_request', 'code' => 40001];
        $e    = new ApiException(422, $body);

        $this->assertSame(422, $e->statusCode);
        $this->assertSame($body, $e->responseBody);
    }

    public function test_exception_code_matches_http_status(): void
    {
        $e = new ApiException(404, []);

        $this->assertSame(404, $e->getCode());
    }

    public function test_default_message_is_json_encoded_body(): void
    {
        $body = ['error' => 'not_found'];
        $e    = new ApiException(404, $body);

        $this->assertSame(json_encode($body), $e->getMessage());
    }

    public function test_custom_message_overrides_default(): void
    {
        $e = new ApiException(500, ['error' => 'server_error'], 'Something went wrong');

        $this->assertSame('Something went wrong', $e->getMessage());
    }

    public function test_empty_body_is_valid(): void
    {
        $e = new ApiException(401, []);

        $this->assertSame([], $e->responseBody);
    }
}
