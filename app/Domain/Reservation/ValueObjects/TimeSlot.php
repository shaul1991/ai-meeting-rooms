<?php

declare(strict_types=1);

namespace App\Domain\Reservation\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

final readonly class TimeSlot implements JsonSerializable
{
    public const int SLOT_DURATION_MINUTES = 30;

    private function __construct(
        private DateTimeImmutable $startTime,
        private DateTimeImmutable $endTime,
    ) {}

    public static function create(DateTimeImmutable $startTime, DateTimeImmutable $endTime): self
    {
        self::validateSlotAlignment($startTime);
        self::validateSlotAlignment($endTime);

        if ($startTime >= $endTime) {
            throw new InvalidArgumentException('Start time must be before end time');
        }

        $durationMinutes = ($endTime->getTimestamp() - $startTime->getTimestamp()) / 60;

        if ($durationMinutes % self::SLOT_DURATION_MINUTES !== 0) {
            throw new InvalidArgumentException(
                'Time slot duration must be a multiple of '.self::SLOT_DURATION_MINUTES.' minutes'
            );
        }

        return new self($startTime, $endTime);
    }

    public static function fromStrings(string $startTime, string $endTime): self
    {
        return self::create(
            new DateTimeImmutable($startTime),
            new DateTimeImmutable($endTime),
        );
    }

    public function startTime(): DateTimeImmutable
    {
        return $this->startTime;
    }

    public function endTime(): DateTimeImmutable
    {
        return $this->endTime;
    }

    public function durationInMinutes(): int
    {
        return (int) (($this->endTime->getTimestamp() - $this->startTime->getTimestamp()) / 60);
    }

    public function slotCount(): int
    {
        return $this->durationInMinutes() / self::SLOT_DURATION_MINUTES;
    }

    public function overlaps(self $other): bool
    {
        return $this->startTime < $other->endTime && $this->endTime > $other->startTime;
    }

    public function contains(DateTimeImmutable $dateTime): bool
    {
        return $dateTime >= $this->startTime && $dateTime < $this->endTime;
    }

    public function equals(self $other): bool
    {
        return $this->startTime == $other->startTime && $this->endTime == $other->endTime;
    }

    public function toArray(): array
    {
        return [
            'start_time' => $this->startTime->format('Y-m-d H:i:s'),
            'end_time' => $this->endTime->format('Y-m-d H:i:s'),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private static function validateSlotAlignment(DateTimeImmutable $time): void
    {
        $minutes = (int) $time->format('i');

        if ($minutes % self::SLOT_DURATION_MINUTES !== 0) {
            throw new InvalidArgumentException(
                "Time must be aligned to {self::SLOT_DURATION_MINUTES}-minute slots. Got: ".$time->format('H:i')
            );
        }

        $seconds = (int) $time->format('s');

        if ($seconds !== 0) {
            throw new InvalidArgumentException('Time must have zero seconds');
        }
    }
}
