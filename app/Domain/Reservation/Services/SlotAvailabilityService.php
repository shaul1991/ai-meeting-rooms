<?php

declare(strict_types=1);

namespace App\Domain\Reservation\Services;

use App\Domain\Reservation\Entities\Reservation;
use App\Domain\Reservation\ValueObjects\TimeSlot;
use App\Domain\Room\Entities\Room;
use DateTimeImmutable;
use DomainException;

class SlotAvailabilityService
{
    /**
     * Check if a time slot is available for a room
     *
     * @param  array<Reservation>  $existingReservations
     */
    public function isSlotAvailable(
        Room $room,
        TimeSlot $timeSlot,
        array $existingReservations,
    ): bool {
        // Check if room is active
        if (! $room->isActive()) {
            return false;
        }

        // Check operating hours for each slot
        $current = $timeSlot->startTime();
        while ($current < $timeSlot->endTime()) {
            if (! $room->isAvailableAt($current)) {
                return false;
            }
            $current = $current->modify('+30 minutes');
        }

        // Check for overlapping reservations
        foreach ($existingReservations as $reservation) {
            if ($reservation->overlaps($timeSlot)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get available time slots for a room on a specific date
     *
     * @param  array<Reservation>  $existingReservations
     * @return array<TimeSlot>
     */
    public function getAvailableSlots(
        Room $room,
        DateTimeImmutable $date,
        array $existingReservations,
    ): array {
        $dayOfWeek = (int) $date->format('w');
        $operatingHours = $room->operatingHours()->getHoursForDay($dayOfWeek);

        if ($operatingHours === null || ! $operatingHours->isOpen()) {
            return [];
        }

        $startTime = new DateTimeImmutable(
            $date->format('Y-m-d').' '.$operatingHours->startTime()
        );
        $endTime = new DateTimeImmutable(
            $date->format('Y-m-d').' '.$operatingHours->endTime()
        );

        // Handle 24:00 case
        if ($operatingHours->endTime() === '24:00') {
            $endTime = $date->modify('+1 day')->setTime(0, 0);
        }

        $availableSlots = [];
        $current = $startTime;

        while ($current < $endTime) {
            $slotEnd = $current->modify('+30 minutes');

            $slot = TimeSlot::create($current, $slotEnd);

            if ($this->isSlotAvailable($room, $slot, $existingReservations)) {
                $availableSlots[] = $slot;
            }

            $current = $slotEnd;
        }

        return $availableSlots;
    }

    /**
     * Validate and ensure slot is available, throw exception if not
     *
     * @param  array<Reservation>  $existingReservations
     *
     * @throws DomainException
     */
    public function ensureSlotAvailable(
        Room $room,
        TimeSlot $timeSlot,
        array $existingReservations,
    ): void {
        if (! $room->isActive()) {
            throw new DomainException('해당 회의실은 현재 사용할 수 없습니다.');
        }

        // Check operating hours
        $current = $timeSlot->startTime();
        while ($current < $timeSlot->endTime()) {
            if (! $room->isAvailableAt($current)) {
                throw new DomainException(
                    '선택한 시간대는 회의실 운영 시간이 아닙니다: '.$current->format('H:i')
                );
            }
            $current = $current->modify('+30 minutes');
        }

        // Check for overlapping reservations
        foreach ($existingReservations as $reservation) {
            if ($reservation->overlaps($timeSlot)) {
                throw new DomainException('선택한 시간대에 이미 다른 예약이 있습니다.');
            }
        }
    }
}
