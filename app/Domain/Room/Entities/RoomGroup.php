<?php

declare(strict_types=1);

namespace App\Domain\Room\Entities;

use App\Domain\Room\ValueObjects\RoomGroupId;
use DateTimeImmutable;

class RoomGroup
{
    /** @var array<Room> */
    private array $rooms = [];

    /** @var array<RoomGroup> */
    private array $childGroups = [];

    private function __construct(
        private RoomGroupId $id,
        private string $name,
        private ?string $description,
        private ?RoomGroupId $parentId,
        private int $sortOrder,
        private bool $isActive,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        string $name,
        ?string $description = null,
        ?RoomGroupId $parentId = null,
        int $sortOrder = 0,
    ): self {
        return new self(
            id: RoomGroupId::generate(),
            name: $name,
            description: $description,
            parentId: $parentId,
            sortOrder: $sortOrder,
            isActive: true,
            createdAt: new DateTimeImmutable,
            updatedAt: new DateTimeImmutable,
        );
    }

    public static function reconstitute(
        RoomGroupId $id,
        string $name,
        ?string $description,
        ?RoomGroupId $parentId,
        int $sortOrder,
        bool $isActive,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            name: $name,
            description: $description,
            parentId: $parentId,
            sortOrder: $sortOrder,
            isActive: $isActive,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function update(
        ?string $name = null,
        ?string $description = null,
        ?RoomGroupId $parentId = null,
        ?int $sortOrder = null,
    ): void {
        if ($name !== null) {
            $this->name = $name;
        }

        if ($description !== null) {
            $this->description = $description;
        }

        if ($parentId !== null) {
            $this->parentId = $parentId;
        }

        if ($sortOrder !== null) {
            $this->sortOrder = $sortOrder;
        }

        $this->updatedAt = new DateTimeImmutable;
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

    // Composite Pattern Methods

    public function addRoom(Room $room): void
    {
        $this->rooms[$room->id()->value()] = $room;
    }

    public function removeRoom(Room $room): void
    {
        unset($this->rooms[$room->id()->value()]);
    }

    public function addChildGroup(RoomGroup $group): void
    {
        $this->childGroups[$group->id()->value()] = $group;
    }

    public function removeChildGroup(RoomGroup $group): void
    {
        unset($this->childGroups[$group->id()->value()]);
    }

    /**
     * Composite Pattern: Get all rooms including nested groups
     *
     * @return array<Room>
     */
    public function getAllRooms(): array
    {
        $allRooms = $this->rooms;

        foreach ($this->childGroups as $childGroup) {
            $allRooms = array_merge($allRooms, $childGroup->getAllRooms());
        }

        return $allRooms;
    }

    /**
     * Composite Pattern: Count all rooms including nested groups
     */
    public function getTotalRoomCount(): int
    {
        return count($this->getAllRooms());
    }

    /**
     * @return array<Room>
     */
    public function rooms(): array
    {
        return $this->rooms;
    }

    /**
     * @return array<RoomGroup>
     */
    public function childGroups(): array
    {
        return $this->childGroups;
    }

    public function id(): RoomGroupId
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

    public function parentId(): ?RoomGroupId
    {
        return $this->parentId;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function hasParent(): bool
    {
        return $this->parentId !== null;
    }
}
