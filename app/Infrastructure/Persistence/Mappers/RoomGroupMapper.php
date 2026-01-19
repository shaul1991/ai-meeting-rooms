<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mappers;

use App\Domain\Room\Entities\RoomGroup;
use App\Domain\Room\ValueObjects\RoomGroupId;
use App\Infrastructure\Persistence\Eloquent\Models\RoomGroupModel;
use DateTimeImmutable;

class RoomGroupMapper
{
    public function toDomain(RoomGroupModel $model): RoomGroup
    {
        return RoomGroup::reconstitute(
            id: RoomGroupId::fromString($model->id),
            name: $model->name,
            description: $model->description,
            parentId: $model->parent_id ? RoomGroupId::fromString($model->parent_id) : null,
            sortOrder: $model->sort_order,
            isActive: $model->is_active,
            createdAt: DateTimeImmutable::createFromMutable($model->created_at),
            updatedAt: DateTimeImmutable::createFromMutable($model->updated_at),
        );
    }

    public function toModel(RoomGroup $entity, ?RoomGroupModel $model = null): RoomGroupModel
    {
        $model ??= new RoomGroupModel;

        $model->id = $entity->id()->value();
        $model->name = $entity->name();
        $model->description = $entity->description();
        $model->parent_id = $entity->parentId()?->value();
        $model->sort_order = $entity->sortOrder();
        $model->is_active = $entity->isActive();

        return $model;
    }
}
