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
            [PaymentStatus::Completed,  '00'],
            [PaymentStatus::Initiated,  '01'],
            [PaymentStatus::Processing, '02'],
            [PaymentStatus::Pending,    '03'],
            [PaymentStatus::Refunded,   '04'],
            [PaymentStatus::Cancelled,  '05'],
            [PaymentStatus::Failed,     '06'],
            [PaymentStatus::NotFound,   '07'],
            [PaymentStatus::Expired,    '08'],
            [PaymentStatus::Rejected,   '09'],
        ];
    }

    public function test_only_completed_is_paid(): void
    {
        foreach (PaymentStatus::cases() as $case) {
            $this->assertSame($case === PaymentStatus::Completed, $case->isPaid(), $case->name);
        }
    }

    public function test_in_flight_statuses_are_not_final(): void
    {
        $this->assertFalse(PaymentStatus::Processing->isFinal());
        $this->assertFalse(PaymentStatus::Pending->isFinal());
        $this->assertFalse(PaymentStatus::Initiated->isFinal());
        $this->assertTrue(PaymentStatus::Completed->isFinal());
        $this->assertTrue(PaymentStatus::Expired->isFinal());
    }

    public function test_from_throws_on_unknown_value(): void
    {
        $this->expectException(\ValueError::class);
        PaymentStatus::from('99');
    }

    public function test_try_from_returns_null_on_unknown_value(): void
    {
        $this->assertNull(PaymentStatus::tryFrom('completed'));
    }
}
