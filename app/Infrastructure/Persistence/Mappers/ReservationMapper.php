<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mappers;

use App\Domain\Reservation\Entities\Reservation;
use App\Domain\Reservation\ValueObjects\ReservationId;
use App\Domain\Reservation\ValueObjects\ReservationStatus;
use App\Domain\Reservation\ValueObjects\TimeSlot;
use App\Domain\Reservation\ValueObjects\UserId;
use App\Domain\Room\ValueObjects\Money;
use App\Domain\Room\ValueObjects\RoomId;
use App\Infrastructure\Persistence\Eloquent\Models\ReservationModel;
use DateTimeImmutable;

class ReservationMapper
{
    public function toDomain(ReservationModel $model): Reservation
    {
        return Reservation::reconstitute(
            id: ReservationId::fromString($model->id),
            roomId: RoomId::fromString($model->room_id),
            userId: UserId::fromString($model->user_id),
            timeSlot: TimeSlot::create(
                DateTimeImmutable::createFromMutable($model->start_time),
                DateTimeImmutable::createFromMutable($model->end_time),
            ),
            status: $model->status,
            totalPrice: Money::create($model->total_price, $model->price_currency),
            purpose: $model->purpose,
            cancelReason: $model->cancel_reason,
            cancelRequestedAt: $model->cancel_requested_at
                ? DateTimeImmutable::createFromMutable($model->cancel_requested_at)
                : null,
            createdAt: DateTimeImmutable::createFromMutable($model->created_at),
            updatedAt: DateTimeImmutable::createFromMutable($model->updated_at),
        );
    }

    public function toModel(Reservation $entity, ?ReservationModel $model = null): ReservationModel
    {
        $model ??= new ReservationModel;

        $model->id = $entity->id()->value();
        $model->room_id = $entity->roomId()->value();
        $model->user_id = $entity->userId()->value();
        $model->start_time = $entity->timeSlot()->startTime();
        $model->end_time = $entity->timeSlot()->endTime();
        $model->status = $entity->status();
        $model->total_price = $entity->totalPrice()->amount();
        $model->price_currency = $entity->totalPrice()->currency();
        $model->purpose = $entity->purpose();
        $model->cancel_reason = $entity->cancelReason();
        $model->cancel_requested_at = $entity->cancelRequestedAt();

        return $model;
    }
}
