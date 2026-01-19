<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mappers;

use App\Domain\Room\Entities\Room;
use App\Domain\Room\ValueObjects\Money;
use App\Domain\Room\ValueObjects\OperatingHours;
use App\Domain\Room\ValueObjects\RoomGroupId;
use App\Domain\Room\ValueObjects\RoomId;
use App\Infrastructure\Persistence\Eloquent\Models\RoomModel;
use DateTimeImmutable;

class RoomMapper
{
    public function toDomain(RoomModel $model): Room
    {
        return Room::reconstitute(
            id: RoomId::fromString($model->id),
            name: $model->name,
            description: $model->description,
            capacity: $model->capacity,
            operatingHours: OperatingHours::create($model->operating_hours),
            pricePerSlot: Money::create($model->price_per_slot, $model->price_currency),
            groupId: $model->group_id ? RoomGroupId::fromString($model->group_id) : null,
            isActive: $model->is_active,
            metadata: $model->metadata,
            createdAt: DateTimeImmutable::createFromMutable($model->created_at),
            updatedAt: DateTimeImmutable::createFromMutable($model->updated_at),
        );
    }

    public function toModel(Room $entity, ?RoomModel $model = null): RoomModel
    {
        $model ??= new RoomModel;

        $model->id = $entity->id()->value();
        $model->name = $entity->name();
        $model->description = $entity->description();
        $model->capacity = $entity->capacity();
        $model->operating_hours = $entity->operatingHours()->toArray();
        $model->price_per_slot = $entity->pricePerSlot()->amount();
        $model->price_currency = $entity->pricePerSlot()->currency();
        $model->group_id = $entity->groupId()?->value();
        $model->is_active = $entity->isActive();
        $model->metadata = $entity->metadata();

        return $model;
    }
}
