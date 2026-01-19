<?php

declare(strict_types=1);

namespace App\Application\Aggregators;

use App\Domain\Reservation\Entities\Reservation;
use App\Domain\Reservation\Services\ReservationService;
use App\Domain\Reservation\Services\SlotAvailabilityService;
use App\Domain\Reservation\ValueObjects\ReservationId;
use App\Domain\Reservation\ValueObjects\TimeSlot;
use App\Domain\Reservation\ValueObjects\UserId;
use App\Domain\Room\ValueObjects\RoomId;
use App\Infrastructure\Persistence\Eloquent\Repositories\ReservationRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\RoomRepository;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

class ReservationAggregator
{
    public function __construct(
        private ReservationService $reservationService,
        private SlotAvailabilityService $slotAvailabilityService,
        private ReservationRepository $reservationRepository,
        private RoomRepository $roomRepository,
    ) {}

    /**
     * Create a new reservation
     */
    public function createReservation(
        string $roomId,
        string $userId,
        DateTimeImmutable $startTime,
        DateTimeImmutable $endTime,
        ?string $purpose = null,
        bool $isAdmin = false,
    ): Reservation {
        return DB::transaction(function () use ($roomId, $userId, $startTime, $endTime, $purpose, $isAdmin) {
            $room = $this->roomRepository->findByIdOrFail(RoomId::fromString($roomId));
            $userIdVo = UserId::fromString($userId);
            $timeSlot = TimeSlot::create($startTime, $endTime);

            // Check slot availability
            $existingReservations = $this->reservationRepository
                ->findActiveByRoomId($room->id())
                ->all();

            $this->slotAvailabilityService->ensureSlotAvailable(
                $room,
                $timeSlot,
                $existingReservations,
            );

            // Check user's active reservations
            $userActiveReservations = $this->reservationRepository
                ->findActiveByUserId($userIdVo)
                ->all();

            // Create reservation
            $reservation = $this->reservationService->createReservation(
                room: $room,
                userId: $userIdVo,
                timeSlot: $timeSlot,
                userActiveReservations: $userActiveReservations,
                purpose: $purpose,
                isAdmin: $isAdmin,
            );

            $this->reservationRepository->save($reservation);

            return $reservation;
        });
    }

    /**
     * Request cancellation for a reservation
     */
    public function requestCancellation(string $reservationId, string $reason): Reservation
    {
        return DB::transaction(function () use ($reservationId, $reason) {
            $reservation = $this->reservationRepository->findByIdOrFail(
                ReservationId::fromString($reservationId)
            );

            $reservation->requestCancel($reason);

            $this->reservationRepository->save($reservation);

            return $reservation;
        });
    }

    /**
     * Admin: Cancel a reservation
     */
    public function cancelReservation(string $reservationId, ?string $reason = null): Reservation
    {
        return DB::transaction(function () use ($reservationId, $reason) {
            $reservation = $this->reservationRepository->findByIdOrFail(
                ReservationId::fromString($reservationId)
            );

            $reservation->cancel($reason);

            $this->reservationRepository->save($reservation);

            return $reservation;
        });
    }

    /**
     * Admin: Approve cancellation request
     */
    public function approveCancellation(string $reservationId): Reservation
    {
        return $this->cancelReservation($reservationId);
    }

    /**
     * Admin: Reject cancellation request
     */
    public function rejectCancellation(string $reservationId): Reservation
    {
        return DB::transaction(function () use ($reservationId) {
            $reservation = $this->reservationRepository->findByIdOrFail(
                ReservationId::fromString($reservationId)
            );

            $reservation->rejectCancelRequest();

            $this->reservationRepository->save($reservation);

            return $reservation;
        });
    }

    /**
     * Get available time slots for a room on a specific date
     *
     * @return array<TimeSlot>
     */
    public function getAvailableSlots(string $roomId, DateTimeImmutable $date): array
    {
        $room = $this->roomRepository->findByIdOrFail(RoomId::fromString($roomId));

        $existingReservations = $this->reservationRepository
            ->findByRoomAndDate($room->id(), $date)
            ->all();

        return $this->slotAvailabilityService->getAvailableSlots(
            $room,
            $date,
            $existingReservations,
        );
    }

    /**
     * Check if user can make a reservation
     */
    public function canUserMakeReservation(string $userId): bool
    {
        $activeReservations = $this->reservationRepository
            ->findActiveByUserId(UserId::fromString($userId));

        return $activeReservations->isEmpty();
    }
}
