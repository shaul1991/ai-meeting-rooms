<?php

declare(strict_types=1);

namespace App\Application\Aggregators;

use App\Domain\Room\Entities\Room;
use App\Domain\Room\Entities\RoomGroup;
use App\Domain\Room\ValueObjects\Money;
use App\Domain\Room\ValueObjects\OperatingHours;
use App\Domain\Room\ValueObjects\RoomGroupId;
use App\Domain\Room\ValueObjects\RoomId;
use App\Infrastructure\Persistence\Eloquent\Repositories\RoomGroupRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\RoomRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RoomAggregator
{
    public function __construct(
        private RoomRepository $roomRepository,
        private RoomGroupRepository $roomGroupRepository,
    ) {}

    /**
     * Create a new room
     */
    public function createRoom(
        string $name,
        int $capacity,
        array $operatingHours,
        int $pricePerSlot,
        string $currency = 'KRW',
        ?string $description = null,
        ?string $groupId = null,
        ?array $metadata = null,
    ): Room {
        return DB::transaction(function () use (
            $name, $capacity, $operatingHours, $pricePerSlot,
            $currency, $description, $groupId, $metadata
        ) {
            $room = Room::create(
                name: $name,
                capacity: $capacity,
                operatingHours: OperatingHours::create($operatingHours),
                pricePerSlot: Money::create($pricePerSlot, $currency),
                description: $description,
                groupId: $groupId ? RoomGroupId::fromString($groupId) : null,
                metadata: $metadata,
            );

            $this->roomRepository->save($room);

            return $room;
        });
    }

    /**
     * Update a room
     */
    public function updateRoom(
        string $roomId,
        ?string $name = null,
        ?int $capacity = null,
        ?array $operatingHours = null,
        ?int $pricePerSlot = null,
        ?string $currency = null,
        ?string $description = null,
        ?string $groupId = null,
        ?array $metadata = null,
    ): Room {
        return DB::transaction(function () use (
            $roomId, $name, $capacity, $operatingHours, $pricePerSlot,
            $currency, $description, $groupId, $metadata
        ) {
            $room = $this->roomRepository->findByIdOrFail(RoomId::fromString($roomId));

            $room->update(
                name: $name,
                capacity: $capacity,
                operatingHours: $operatingHours ? OperatingHours::create($operatingHours) : null,
                pricePerSlot: $pricePerSlot !== null
                    ? Money::create($pricePerSlot, $currency ?? 'KRW')
                    : null,
                description: $description,
                groupId: $groupId ? RoomGroupId::fromString($groupId) : null,
                metadata: $metadata,
            );

            $this->roomRepository->save($room);

            return $room;
        });
    }

    /**
     * Activate a room
     */
    public function activateRoom(string $roomId): Room
    {
        return DB::transaction(function () use ($roomId) {
            $room = $this->roomRepository->findByIdOrFail(RoomId::fromString($roomId));
            $room->activate();
            $this->roomRepository->save($room);

            return $room;
        });
    }

    /**
     * Deactivate a room
     */
    public function deactivateRoom(string $roomId): Room
    {
        return DB::transaction(function () use ($roomId) {
            $room = $this->roomRepository->findByIdOrFail(RoomId::fromString($roomId));
            $room->deactivate();
            $this->roomRepository->save($room);

            return $room;
        });
    }

    /**
     * Create a room group
     */
    public function createRoomGroup(
        string $name,
        ?string $description = null,
        ?string $parentId = null,
        int $sortOrder = 0,
    ): RoomGroup {
        return DB::transaction(function () use ($name, $description, $parentId, $sortOrder) {
            $group = RoomGroup::create(
                name: $name,
                description: $description,
                parentId: $parentId ? RoomGroupId::fromString($parentId) : null,
                sortOrder: $sortOrder,
            );

            $this->roomGroupRepository->save($group);

            return $group;
        });
    }

    /**
     * Get room group with all rooms (Composite Pattern)
     */
    public function getRoomGroupWithRooms(string $groupId): ?RoomGroup
    {
        return $this->roomGroupRepository->findWithDescendants(
            RoomGroupId::fromString($groupId)
        );
    }

    /**
     * Get all active rooms
     *
     * @return Collection<int, Room>
     */
    public function getActiveRooms(): Collection
    {
        return $this->roomRepository->findActive();
    }

    /**
     * Get root room groups with their rooms
     *
     * @return Collection<int, RoomGroup>
     */
    public function getRootGroupsWithRooms(): Collection
    {
        return $this->roomGroupRepository->findRootGroups()
            ->map(function (RoomGroup $group) {
                return $this->roomGroupRepository->findWithDescendants($group->id());
            });
    }
}
