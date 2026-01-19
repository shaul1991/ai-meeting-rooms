<?php

declare(strict_types=1);

namespace App\Domain\Room\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

final readonly class DayOperatingHours implements JsonSerializable
{
    private function __construct(
        private bool $isOpen,
        private ?string $startTime,
        private ?string $endTime,
    ) {}

    public static function create(string $startTime, string $endTime): self
    {
        self::validateTimeFormat($startTime);
        self::validateTimeFormat($endTime);

        if ($startTime >= $endTime) {
            throw new InvalidArgumentException('Start time must be before end time');
        }

        return new self(
            isOpen: true,
            startTime: $startTime,
            endTime: $endTime,
        );
    }

    public static function allDay(): self
    {
        return new self(
            isOpen: true,
            startTime: '00:00',
            endTime: '24:00',
        );
    }

    public static function closed(): self
    {
        return new self(
            isOpen: false,
            startTime: null,
            endTime: null,
        );
    }

    public static function fromArray(array $data): self
    {
        if (! ($data['is_open'] ?? true)) {
            return self::closed();
        }

        return self::create(
            $data['start_time'] ?? '09:00',
            $data['end_time'] ?? '18:00',
        );
    }

    public function isOpen(): bool
    {
        return $this->isOpen;
    }

    public function startTime(): ?string
    {
        return $this->startTime;
    }

    public function endTime(): ?string
    {
        return $this->endTime;
    }

    public function isOpenAt(DateTimeImmutable $dateTime): bool
    {
        if (! $this->isOpen) {
            return false;
        }

        $time = $dateTime->format('H:i');

        return $time >= $this->startTime && $time < $this->endTime;
    }

    public function toArray(): array
    {
        return [
            'is_open' => $this->isOpen,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private static function validateTimeFormat(string $time): void
    {
        if (! preg_match('/^([01]?[0-9]|2[0-4]):[0-5][0-9]$/', $time)) {
            throw new InvalidArgumentException("Invalid time format: {$time}. Expected HH:MM format.");
        }
    }
}
