<?php

declare(strict_types=1);

namespace App\Domain\Reservation\ValueObjects;

enum ReservationStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case CANCEL_REQUESTED = 'cancel_requested';
    case COMPLETED = 'completed';
    case NO_SHOW = 'no_show';

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::PENDING => in_array($newStatus, [
                self::CONFIRMED,
                self::CANCELLED,
            ]),
            self::CONFIRMED => in_array($newStatus, [
                self::CANCEL_REQUESTED,
                self::CANCELLED,
                self::COMPLETED,
                self::NO_SHOW,
            ]),
            self::CANCEL_REQUESTED => in_array($newStatus, [
                self::CONFIRMED,
                self::CANCELLED,
            ]),
            self::CANCELLED, self::COMPLETED, self::NO_SHOW => false,
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::CONFIRMED,
            self::CANCEL_REQUESTED,
        ]);
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::CANCELLED,
            self::COMPLETED,
            self::NO_SHOW,
        ]);
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => '대기중',
            self::CONFIRMED => '확정',
            self::CANCELLED => '취소됨',
            self::CANCEL_REQUESTED => '취소 요청',
            self::COMPLETED => '완료',
            self::NO_SHOW => '노쇼',
        };
    }
}
