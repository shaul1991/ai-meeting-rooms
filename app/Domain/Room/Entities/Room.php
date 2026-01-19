<?php

declare(strict_types=1);

namespace App\Domain\Room\Entities;

use App\Domain\Room\Events\RoomCreated;
use App\Domain\Room\Events\RoomUpdated;
use App\Domain\Room\ValueObjects\Money;
use App\Domain\Room\ValueObjects\OperatingHours;
use App\Domain\Room\ValueObjects\RoomGroupId;
use App\Domain\Room\ValueObjects\RoomId;
use DateTimeImmutable;

class Room
{
    private array $domainEvents = [];

    private function __construct(
        private RoomId $id,
        private string $name,
        private ?string $description,
        private int $capacity,
        private OperatingHours $operatingHours,
        private Money $pricePerSlot,
        private ?RoomGroupId $groupId,
        private bool $isActive,
        private ?array $metadata,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        string $name,
        int $capacity,
        OperatingHours $operatingHours,
        Money $pricePerSlot,
        ?string $description = null,
        ?RoomGroupId $groupId = null,
        ?array $metadata = null,
    ): self {
        $room = new self(
            id: RoomId::generate(),
            name: $name,
            description: $description,
            capacity: $capacity,
            operatingHours: $operatingHours,
            pricePerSlot: $pricePerSlot,
            groupId: $groupId,
            isActive: true,
            metadata: $metadata,
            createdAt: new DateTimeImmutable,
            updatedAt: new DateTimeImmutable,
        );

        $room->recordEvent(new RoomCreated($room->id));

        return $room;
    }

    public static function reconstitute(
        RoomId $id,
        string $name,
        ?string $description,
        int $capacity,
        OperatingHours $operatingHours,
        Money $pricePerSlot,
        ?RoomGroupId $groupId,
        bool $isActive,
        ?array $metadata,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            name: $name,
            description: $description,
            capacity: $capacity,
            operatingHours: $operatingHours,
            pricePerSlot: $pricePerSlot,
            groupId: $groupId,
            isActive: $isActive,
            metadata: $metadata,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function update(
        ?string $name = null,
        ?string $description = null,
        ?int $capacity = null,
        ?OperatingHours $operatingHours = null,
        ?Money $pricePerSlot = null,
        ?RoomGroupId $groupId = null,
        ?array $metadata = null,
    ): void {
        if ($name !== null) {
            $this->name = $name;
        }

        if ($description !== null) {
            $this->description = $description;
        }

        if ($capacity !== null) {
            $this->capacity = $capacity;
        }

        if ($operatingHours !== null) {
            $this->operatingHours = $operatingHours;
        }

        if ($pricePerSlot !== null) {
            $this->pricePerSlot = $pricePerSlot;
        }

        if ($groupId !== null) {
            $this->groupId = $groupId;
        }

        if ($metadata !== null) {
            $this->metadata = $metadata;
        }

        $this->updatedAt = new DateTimeImmutable;

        $this->recordEvent(new RoomUpdated($this->id));
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function isAvailableAt(DateTimeImmutable $dateTime): bool
    {
        if (! $this->isActive) {
            return false;
        }

        return $this->operatingHours->isOpenAt($dateTime);
    }

    public function id(): RoomId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function capacity(): int
    {
        return $this->capacity;
    }

    public function operatingHours(): OperatingHours
    {
        return $this->operatingHours;
    }

    public function pricePerSlot(): Money
    {
        return $this->pricePerSlot;
    }

    public function groupId(): ?RoomGroupId
    {
        return $this->groupId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function metadata(): ?array
    {
        return $this->metadata;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }
}
