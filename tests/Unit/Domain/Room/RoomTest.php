<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Room;

use App\Domain\Room\Entities\Room;
use App\Domain\Room\Events\RoomCreated;
use App\Domain\Room\ValueObjects\Money;
use App\Domain\Room\ValueObjects\OperatingHours;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class RoomTest extends TestCase
{
    public function test_can_create_room(): void
    {
        $room = Room::create(
            name: '회의실 A',
            capacity: 10,
            operatingHours: OperatingHours::weekdaysOnly('09:00', '18:00'),
            pricePerSlot: Money::create(5000),
            description: '10인용 회의실',
        );

        $this->assertEquals('회의실 A', $room->name());
        $this->assertEquals(10, $room->capacity());
        $this->assertEquals('10인용 회의실', $room->description());
        $this->assertEquals(5000, $room->pricePerSlot()->amount());
        $this->assertTrue($room->isActive());
    }

    public function test_room_creation_records_domain_event(): void
    {
        $room = Room::create(
            name: '회의실 A',
            capacity: 10,
            operatingHours: OperatingHours::allDay(),
            pricePerSlot: Money::create(5000),
        );

        $events = $room->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(RoomCreated::class, $events[0]);
        $this->assertEquals($room->id(), $events[0]->roomId);
    }

    public function test_can_update_room(): void
    {
        $room = Room::create(
            name: '회의실 A',
            capacity: 10,
            operatingHours: OperatingHours::allDay(),
            pricePerSlot: Money::create(5000),
        );

        $room->update(
            name: '회의실 B',
            capacity: 20,
        );

        $this->assertEquals('회의실 B', $room->name());
        $this->assertEquals(20, $room->capacity());
    }

    public function test_can_deactivate_and_activate_room(): void
    {
        $room = Room::create(
            name: '회의실 A',
            capacity: 10,
            operatingHours: OperatingHours::allDay(),
            pricePerSlot: Money::create(5000),
        );

        $this->assertTrue($room->isActive());

        $room->deactivate();
        $this->assertFalse($room->isActive());

        $room->activate();
        $this->assertTrue($room->isActive());
    }

    public function test_inactive_room_is_not_available(): void
    {
        $room = Room::create(
            name: '회의실 A',
            capacity: 10,
            operatingHours: OperatingHours::allDay(),
            pricePerSlot: Money::create(5000),
        );

        $room->deactivate();

        $this->assertFalse($room->isAvailableAt(new DateTimeImmutable('2024-01-15 10:00:00')));
    }

    public function test_room_respects_operating_hours(): void
    {
        $room = Room::create(
            name: '회의실 A',
            capacity: 10,
            operatingHours: OperatingHours::weekdaysOnly('09:00', '18:00'),
            pricePerSlot: Money::create(5000),
        );

        // Monday 10:00 - should be available
        $monday = new DateTimeImmutable('2024-01-15 10:00:00'); // Monday
        $this->assertTrue($room->isAvailableAt($monday));

        // Monday 08:00 - before operating hours
        $earlyMonday = new DateTimeImmutable('2024-01-15 08:00:00');
        $this->assertFalse($room->isAvailableAt($earlyMonday));

        // Sunday - closed
        $sunday = new DateTimeImmutable('2024-01-14 10:00:00'); // Sunday
        $this->assertFalse($room->isAvailableAt($sunday));
    }
}
