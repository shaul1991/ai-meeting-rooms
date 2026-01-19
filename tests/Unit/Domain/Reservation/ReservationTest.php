<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Reservation;

use App\Domain\Reservation\Entities\Reservation;
use App\Domain\Reservation\Events\ReservationCancelled;
use App\Domain\Reservation\Events\ReservationCancelRequested;
use App\Domain\Reservation\Events\ReservationCreated;
use App\Domain\Reservation\ValueObjects\ReservationStatus;
use App\Domain\Reservation\ValueObjects\TimeSlot;
use App\Domain\Reservation\ValueObjects\UserId;
use App\Domain\Room\ValueObjects\Money;
use App\Domain\Room\ValueObjects\RoomId;
use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\TestCase;

class ReservationTest extends TestCase
{
    public function test_can_create_reservation(): void
    {
        $reservation = $this->createReservation();

        $this->assertEquals(ReservationStatus::CONFIRMED, $reservation->status());
        $this->assertEquals(10000, $reservation->totalPrice()->amount()); // 2 slots * 5000
    }

    public function test_reservation_creation_records_domain_event(): void
    {
        $reservation = $this->createReservation();

        $events = $reservation->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ReservationCreated::class, $events[0]);
    }

    public function test_cannot_create_reservation_exceeding_max_duration(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('일반 사용자는 최대 120분까지만 예약할 수 있습니다.');

        // 3 hours = 180 minutes, exceeds max of 120
        $start = new DateTimeImmutable('2024-01-15 10:00:00');
        $end = new DateTimeImmutable('2024-01-15 13:00:00');

        Reservation::create(
            roomId: RoomId::generate(),
            userId: UserId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            timeSlot: TimeSlot::create($start, $end),
            pricePerSlot: Money::create(5000),
            isAdmin: false,
        );
    }

    public function test_admin_can_exceed_max_duration(): void
    {
        $start = new DateTimeImmutable('2024-01-15 10:00:00');
        $end = new DateTimeImmutable('2024-01-15 13:00:00');

        $reservation = Reservation::create(
            roomId: RoomId::generate(),
            userId: UserId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            timeSlot: TimeSlot::create($start, $end),
            pricePerSlot: Money::create(5000),
            isAdmin: true,
        );

        $this->assertEquals(180, $reservation->timeSlot()->durationInMinutes());
    }

    public function test_can_request_cancellation_within_2_days(): void
    {
        // 2일 이내 예약은 취소 요청만 가능
        $start = (new DateTimeImmutable)->modify('+1 day')->setTime(10, 0, 0);
        $end = $start->modify('+1 hour');

        $reservation = Reservation::create(
            roomId: RoomId::generate(),
            userId: UserId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            timeSlot: TimeSlot::create($start, $end),
            pricePerSlot: Money::create(5000),
        );

        $reservation->pullDomainEvents(); // Clear creation event

        $reservation->requestCancel('회의 취소');

        $this->assertEquals(ReservationStatus::CANCEL_REQUESTED, $reservation->status());
        $this->assertEquals('회의 취소', $reservation->cancelReason());

        $events = $reservation->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ReservationCancelRequested::class, $events[0]);
    }

    public function test_cannot_request_cancellation_more_than_2_days_ahead(): void
    {
        // 2일 초과 예약은 즉시 취소 사용해야 함 (취소 요청 불가)
        $start = (new DateTimeImmutable)->modify('+5 days')->setTime(10, 0, 0);
        $end = $start->modify('+1 hour');

        $reservation = Reservation::create(
            roomId: RoomId::generate(),
            userId: UserId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            timeSlot: TimeSlot::create($start, $end),
            pricePerSlot: Money::create(5000),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('예약일 2일 전 초과 시에는 즉시 취소를 이용해주세요.');

        $reservation->requestCancel('회의 취소');
    }

    public function test_can_cancel_immediately_more_than_2_days_ahead(): void
    {
        // 2일 초과 예약은 즉시 취소 가능
        $start = (new DateTimeImmutable)->modify('+5 days')->setTime(10, 0, 0);
        $end = $start->modify('+1 hour');

        $reservation = Reservation::create(
            roomId: RoomId::generate(),
            userId: UserId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            timeSlot: TimeSlot::create($start, $end),
            pricePerSlot: Money::create(5000),
        );

        $reservation->pullDomainEvents(); // Clear creation event

        $reservation->cancelImmediately('일정 변경');

        $this->assertEquals(ReservationStatus::CANCELLED, $reservation->status());
        $this->assertEquals('일정 변경', $reservation->cancelReason());

        $events = $reservation->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ReservationCancelled::class, $events[0]);
    }

    public function test_cannot_cancel_immediately_within_2_days(): void
    {
        // 2일 이내 예약은 즉시 취소 불가
        $start = (new DateTimeImmutable)->modify('+1 day')->setTime(10, 0, 0);
        $end = $start->modify('+1 hour');

        $reservation = Reservation::create(
            roomId: RoomId::generate(),
            userId: UserId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            timeSlot: TimeSlot::create($start, $end),
            pricePerSlot: Money::create(5000),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('예약일 2일 전부터는 즉시 취소가 불가능합니다.');

        $reservation->cancelImmediately('일정 변경');
    }

    public function test_can_cancel_reservation(): void
    {
        $reservation = $this->createReservation();
        $reservation->pullDomainEvents();

        $reservation->cancel('관리자에 의한 취소');

        $this->assertEquals(ReservationStatus::CANCELLED, $reservation->status());

        $events = $reservation->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ReservationCancelled::class, $events[0]);
    }

    public function test_cannot_cancel_already_cancelled_reservation(): void
    {
        $reservation = $this->createReservation();
        $reservation->cancel();

        $this->expectException(DomainException::class);

        $reservation->cancel();
    }

    private function createReservation(): Reservation
    {
        $start = new DateTimeImmutable('2024-01-15 10:00:00');
        $end = new DateTimeImmutable('2024-01-15 11:00:00');

        return Reservation::create(
            roomId: RoomId::generate(),
            userId: UserId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            timeSlot: TimeSlot::create($start, $end),
            pricePerSlot: Money::create(5000),
        );
    }
}
