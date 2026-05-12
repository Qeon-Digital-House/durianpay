<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use QDH\DurianPay\Enums\PaymentStatus;

class PaymentStatusTest extends TestCase
{
    /** @dataProvider caseProvider */
    public function test_case_value(PaymentStatus $case, string $expected): void
    {
        $this->assertSame($expected, $case->value);
    }

    public static function caseProvider(): array
    {
        return [
            [PaymentStatus::Started,    'STARTED'],
            [PaymentStatus::Processing, 'PROCESSING'],
            [PaymentStatus::Completed,  'COMPLETED'],
            [PaymentStatus::Failed,     'FAILED'],
            [PaymentStatus::Cancelled,  'CANCELLED'],
            [PaymentStatus::Expired,    'EXPIRED'],
            [PaymentStatus::Pending,    'PENDING'],
        ];
    }

    public function test_from_resolves_known_value(): void
    {
        $this->assertSame(PaymentStatus::Completed, PaymentStatus::from('COMPLETED'));
    }

    public function test_from_throws_on_unknown_value(): void
    {
        $this->expectException(\ValueError::class);
        PaymentStatus::from('UNKNOWN');
    }

    public function test_try_from_returns_null_on_unknown_value(): void
    {
        $this->assertNull(PaymentStatus::tryFrom('INVALID'));
    }
}
