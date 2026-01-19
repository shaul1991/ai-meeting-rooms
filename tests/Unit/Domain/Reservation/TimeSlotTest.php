<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Reservation;

use App\Domain\Reservation\ValueObjects\TimeSlot;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TimeSlotTest extends TestCase
{
    public function test_can_create_valid_time_slot(): void
    {
        $start = new DateTimeImmutable('2024-01-15 10:00:00');
        $end = new DateTimeImmutable('2024-01-15 11:00:00');

        $slot = TimeSlot::create($start, $end);

        $this->assertEquals($start, $slot->startTime());
        $this->assertEquals($end, $slot->endTime());
    }

    public function test_cannot_create_slot_with_end_before_start(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $start = new DateTimeImmutable('2024-01-15 11:00:00');
        $end = new DateTimeImmutable('2024-01-15 10:00:00');

        TimeSlot::create($start, $end);
    }

    public function test_slot_must_align_to_30_minutes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $start = new DateTimeImmutable('2024-01-15 10:15:00');
        $end = new DateTimeImmutable('2024-01-15 11:00:00');

        TimeSlot::create($start, $end);
    }

    public function test_can_calculate_duration(): void
    {
        $start = new DateTimeImmutable('2024-01-15 10:00:00');
        $end = new DateTimeImmutable('2024-01-15 11:30:00');

        $slot = TimeSlot::create($start, $end);

        $this->assertEquals(90, $slot->durationInMinutes());
        $this->assertEquals(3, $slot->slotCount());
    }

    public function test_can_detect_overlapping_slots(): void
    {
        $slot1 = TimeSlot::create(
            new DateTimeImmutable('2024-01-15 10:00:00'),
            new DateTimeImmutable('2024-01-15 11:00:00')
        );

        $slot2 = TimeSlot::create(
            new DateTimeImmutable('2024-01-15 10:30:00'),
            new DateTimeImmutable('2024-01-15 11:30:00')
        );

        $slot3 = TimeSlot::create(
            new DateTimeImmutable('2024-01-15 11:00:00'),
            new DateTimeImmutable('2024-01-15 12:00:00')
        );

        $this->assertTrue($slot1->overlaps($slot2));
        $this->assertFalse($slot1->overlaps($slot3)); // Adjacent, not overlapping
    }

    public function test_can_check_if_contains_datetime(): void
    {
        $slot = TimeSlot::create(
            new DateTimeImmutable('2024-01-15 10:00:00'),
            new DateTimeImmutable('2024-01-15 11:00:00')
        );

        $this->assertTrue($slot->contains(new DateTimeImmutable('2024-01-15 10:30:00')));
        $this->assertFalse($slot->contains(new DateTimeImmutable('2024-01-15 11:00:00'))); // End time exclusive
        $this->assertFalse($slot->contains(new DateTimeImmutable('2024-01-15 09:30:00')));
    }
}
