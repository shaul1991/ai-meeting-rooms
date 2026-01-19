<?php

declare(strict_types=1);

namespace App\Domain\Reservation\Events;

use App\Domain\Reservation\ValueObjects\ReservationId;
use App\Domain\Reservation\ValueObjects\TimeSlot;
use App\Domain\Reservation\ValueObjects\UserId;
use App\Domain\Room\ValueObjects\RoomId;

final readonly class ReservationCreated
{
    public function __construct(
        public ReservationId $reservationId,
        public RoomId $roomId,
        public UserId $userId,
        public TimeSlot $timeSlot,
    ) {}
}
