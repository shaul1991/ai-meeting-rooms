<?php

declare(strict_types=1);

namespace App\Domain\Reservation\Services;

use App\Domain\Reservation\Entities\Reservation;
use App\Domain\Reservation\ValueObjects\ReservationStatus;
use App\Domain\Reservation\ValueObjects\TimeSlot;
use App\Domain\Reservation\ValueObjects\UserId;
use App\Domain\Room\Entities\Room;
use App\Domain\Room\ValueObjects\RoomId;
use DomainException;

class ReservationService
{
    /**
     * Create a new reservation
     *
     * @param  array<Reservation>  $userActiveReservations
     */
    public function createReservation(
        Room $room,
        UserId $userId,
        TimeSlot $timeSlot,
        array $userActiveReservations,
        ?string $purpose = null,
        bool $isAdmin = false,
    ): Reservation {
        // Check if user already has an active reservation (non-admin only)
        if (! $isAdmin) {
            $this->ensureUserHasNoActiveReservation($userActiveReservations);
        }

        return Reservation::create(
            roomId: $room->id(),
            userId: $userId,
            timeSlot: $timeSlot,
            pricePerSlot: $room->pricePerSlot(),
            purpose: $purpose,
            isAdmin: $isAdmin,
        );
    }

    /**
     * Ensure user doesn't have any active reservations
     *
     * @param  array<Reservation>  $reservations
     *
     * @throws DomainException
     */
    public function ensureUserHasNoActiveReservation(array $reservations): void
    {
        foreach ($reservations as $reservation) {
            if ($reservation->status()->isActive()) {
                throw new DomainException(
                    '이미 활성화된 예약이 있습니다. 동시에 1개의 예약만 가능합니다.'
                );
            }
        }
    }

    /**
     * Check if user can request cancellation (2 days before rule)
     */
    public function canRequestCancellation(Reservation $reservation): bool
    {
        if ($reservation->status() !== ReservationStatus::CONFIRMED) {
            return false;
        }

        $twoDaysBefore = $reservation->timeSlot()->startTime()->modify('-2 days');

        return new \DateTimeImmutable <= $twoDaysBefore;
    }

    /**
     * Get active reservations for a room within a time range
     *
     * @param  array<Reservation>  $reservations
     * @return array<Reservation>
     */
    public function filterActiveReservationsForRoom(
        array $reservations,
        RoomId $roomId,
    ): array {
        return array_filter(
            $reservations,
            fn (Reservation $r) => $r->roomId()->equals($roomId) && $r->status()->isActive()
        );
    }

    /**
     * Get active reservations for a user
     *
     * @param  array<Reservation>  $reservations
     * @return array<Reservation>
     */
    public function filterActiveReservationsForUser(
        array $reservations,
        UserId $userId,
    ): array {
        return array_filter(
            $reservations,
            fn (Reservation $r) => $r->userId()->equals($userId) && $r->status()->isActive()
        );
    }
}
