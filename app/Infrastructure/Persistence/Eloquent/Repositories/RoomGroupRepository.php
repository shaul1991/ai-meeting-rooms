<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Room\Entities\RoomGroup;
use App\Domain\Room\ValueObjects\RoomGroupId;
use App\Infrastructure\Persistence\Eloquent\Models\RoomGroupModel;
use App\Infrastructure\Persistence\Mappers\RoomGroupMapper;
use Illuminate\Support\Collection;

class RoomGroupRepository
{
    public function __construct(
        private RoomGroupMapper $mapper,
    ) {}

    public function findById(RoomGroupId $id): ?RoomGroup
    {
        $model = RoomGroupModel::find($id->value());

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByIdOrFail(RoomGroupId $id): RoomGroup
    {
        $model = RoomGroupModel::findOrFail($id->value());

        return $this->mapper->toDomain($model);
    }

    /**
     * @return Collection<int, RoomGroup>
     */
    public function findAll(): Collection
    {
        return RoomGroupModel::orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (RoomGroupModel $model) => $this->mapper->toDomain($model));
    }

    /**
     * @return Collection<int, RoomGroup>
     */
    public function findRootGroups(): Collection
    {
        return RoomGroupModel::whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (RoomGroupModel $model) => $this->mapper->toDomain($model));
    }

    /**
     * @return Collection<int, RoomGroup>
     */
    public function findChildren(RoomGroupId $parentId): Collection
    {
        return RoomGroupModel::where('parent_id', $parentId->value())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (RoomGroupModel $model) => $this->mapper->toDomain($model));
    }

    /**
     * Get group with all descendants loaded (for Composite Pattern)
     */
    public function findWithDescendants(RoomGroupId $id): ?RoomGroup
    {
        $model = RoomGroupModel::with('descendants', 'rooms')
            ->find($id->value());

        if (! $model) {
            return null;
        }

        return $this->buildGroupWithChildren($model);
    }

    public function save(RoomGroup $group): void
    {
        $model = RoomGroupModel::find($group->id()->value());
        $model = $this->mapper->toModel($group, $model);
        $model->save();
    }

    public function delete(RoomGroup $group): void
    {
        RoomGroupModel::where('id', $group->id()->value())->delete();
    }

    private function buildGroupWithChildren(RoomGroupModel $model): RoomGroup
    {
        $group = $this->mapper->toDomain($model);

        // Add rooms to the group
        $roomMapper = app(RoomMapper::class);
        foreach ($model->rooms as $roomModel) {
            $group->addRoom($roomMapper->toDomain($roomModel));
        }

        // Recursively add child groups
        foreach ($model->children as $childModel) {
            $childGroup = $this->buildGroupWithChildren($childModel);
            $group->addChildGroup($childGroup);
        }

        return $group;
    }
}
