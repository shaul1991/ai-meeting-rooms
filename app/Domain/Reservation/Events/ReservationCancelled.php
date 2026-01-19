<?php

declare(strict_types=1);

namespace App\Domain\Reservation\Events;

use App\Domain\Reservation\ValueObjects\ReservationId;

final readonly class ReservationCancelled
{
    public function __construct(
        public ReservationId $reservationId,
    ) {}
}
