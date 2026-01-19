<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Room\Entities\Room;
use App\Domain\Room\ValueObjects\RoomGroupId;
use App\Domain\Room\ValueObjects\RoomId;
use App\Infrastructure\Persistence\Eloquent\Models\RoomModel;
use App\Infrastructure\Persistence\Mappers\RoomMapper;
use Illuminate\Support\Collection;

class RoomRepository
{
    public function __construct(
        private RoomMapper $mapper,
    ) {}

    public function findById(RoomId $id): ?Room
    {
        $model = RoomModel::find($id->value());

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByIdOrFail(RoomId $id): Room
    {
        $model = RoomModel::findOrFail($id->value());

        return $this->mapper->toDomain($model);
    }

    /**
     * @return Collection<int, Room>
     */
    public function findAll(): Collection
    {
        return RoomModel::orderBy('name')
            ->get()
            ->map(fn (RoomModel $model) => $this->mapper->toDomain($model));
    }

    /**
     * @return Collection<int, Room>
     */
    public function findActive(): Collection
    {
        return RoomModel::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (RoomModel $model) => $this->mapper->toDomain($model));
    }

    /**
     * @return Collection<int, Room>
     */
    public function findByGroupId(RoomGroupId $groupId): Collection
    {
        return RoomModel::where('group_id', $groupId->value())
            ->orderBy('name')
            ->get()
            ->map(fn (RoomModel $model) => $this->mapper->toDomain($model));
    }

    public function save(Room $room): void
    {
        $model = RoomModel::find($room->id()->value());
        $model = $this->mapper->toModel($room, $model);
        $model->save();
    }

    public function delete(Room $room): void
    {
        RoomModel::where('id', $room->id()->value())->delete();
    }
}
