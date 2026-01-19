<?php

declare(strict_types=1);

namespace App\Domain\Room\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

final readonly class OperatingHours implements JsonSerializable
{
    /**
     * @param  array<int, DayOperatingHours>  $weeklyHours  0=Sunday, 6=Saturday
     */
    private function __construct(
        private array $weeklyHours,
    ) {}

    public static function create(array $weeklyHours): self
    {
        $hours = [];

        foreach ($weeklyHours as $dayOfWeek => $dayHours) {
            if ($dayOfWeek < 0 || $dayOfWeek > 6) {
                throw new InvalidArgumentException('Day of week must be between 0 (Sunday) and 6 (Saturday)');
            }

            if ($dayHours instanceof DayOperatingHours) {
                $hours[$dayOfWeek] = $dayHours;
            } elseif (is_array($dayHours)) {
                $hours[$dayOfWeek] = DayOperatingHours::fromArray($dayHours);
            }
        }

        return new self($hours);
    }

    public static function allDay(): self
    {
        $hours = [];
        for ($i = 0; $i <= 6; $i++) {
            $hours[$i] = DayOperatingHours::allDay();
        }

        return new self($hours);
    }

    public static function weekdaysOnly(string $startTime, string $endTime): self
    {
        $hours = [];

        // Monday to Friday (1-5)
        for ($i = 1; $i <= 5; $i++) {
            $hours[$i] = DayOperatingHours::create($startTime, $endTime);
        }

        // Saturday and Sunday are closed
        $hours[0] = DayOperatingHours::closed();
        $hours[6] = DayOperatingHours::closed();

        return new self($hours);
    }

    public function isOpenAt(DateTimeImmutable $dateTime): bool
    {
        $dayOfWeek = (int) $dateTime->format('w');

        if (! isset($this->weeklyHours[$dayOfWeek])) {
            return false;
        }

        return $this->weeklyHours[$dayOfWeek]->isOpenAt($dateTime);
    }

    public function getHoursForDay(int $dayOfWeek): ?DayOperatingHours
    {
        return $this->weeklyHours[$dayOfWeek] ?? null;
    }

    public function toArray(): array
    {
        $result = [];

        foreach ($this->weeklyHours as $dayOfWeek => $dayHours) {
            $result[$dayOfWeek] = $dayHours->toArray();
        }

        return $result;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
