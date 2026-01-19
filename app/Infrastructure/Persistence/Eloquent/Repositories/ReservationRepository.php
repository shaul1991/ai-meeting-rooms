<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Reservation\Entities\Reservation;
use App\Domain\Reservation\ValueObjects\ReservationId;
use App\Domain\Reservation\ValueObjects\ReservationStatus;
use App\Domain\Reservation\ValueObjects\TimeSlot;
use App\Domain\Reservation\ValueObjects\UserId;
use App\Domain\Room\ValueObjects\RoomId;
use App\Infrastructure\Persistence\Eloquent\Models\ReservationModel;
use App\Infrastructure\Persistence\Mappers\ReservationMapper;
use DateTimeImmutable;
use Illuminate\Support\Collection;

class ReservationRepository
{
    public function __construct(
        private ReservationMapper $mapper,
    ) {}

    public function findById(ReservationId $id): ?Reservation
    {
        $model = ReservationModel::find($id->value());

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByIdOrFail(ReservationId $id): Reservation
    {
        $model = ReservationModel::findOrFail($id->value());

        return $this->mapper->toDomain($model);
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function findByRoomId(RoomId $roomId): Collection
    {
        return ReservationModel::forRoom($roomId->value())
            ->orderBy('start_time')
            ->get()
            ->map(fn (ReservationModel $model) => $this->mapper->toDomain($model));
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function findActiveByRoomId(RoomId $roomId): Collection
    {
        return ReservationModel::forRoom($roomId->value())
            ->active()
            ->orderBy('start_time')
            ->get()
            ->map(fn (ReservationModel $model) => $this->mapper->toDomain($model));
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function findByUserId(UserId $userId): Collection
    {
        return ReservationModel::forUser($userId->value())
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(fn (ReservationModel $model) => $this->mapper->toDomain($model));
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function findActiveByUserId(UserId $userId): Collection
    {
        return ReservationModel::forUser($userId->value())
            ->active()
            ->orderBy('start_time')
            ->get()
            ->map(fn (ReservationModel $model) => $this->mapper->toDomain($model));
    }

    /**
     * Find overlapping reservations for a room within a time slot
     *
     * @return Collection<int, Reservation>
     */
    public function findOverlapping(RoomId $roomId, TimeSlot $timeSlot): Collection
    {
        return ReservationModel::forRoom($roomId->value())
            ->active()
            ->overlapping($timeSlot->startTime(), $timeSlot->endTime())
            ->get()
            ->map(fn (ReservationModel $model) => $this->mapper->toDomain($model));
    }

    /**
     * Find reservations for a room on a specific date
     *
     * @return Collection<int, Reservation>
     */
    public function findByRoomAndDate(RoomId $roomId, DateTimeImmutable $date): Collection
    {
        $startOfDay = $date->setTime(0, 0);
        $endOfDay = $date->setTime(23, 59, 59);

        return ReservationModel::forRoom($roomId->value())
            ->whereBetween('start_time', [$startOfDay, $endOfDay])
            ->orderBy('start_time')
            ->get()
            ->map(fn (ReservationModel $model) => $this->mapper->toDomain($model));
    }

    /**
     * Find all pending cancellation requests
     *
     * @return Collection<int, Reservation>
     */
    public function findPendingCancellations(): Collection
    {
        return ReservationModel::where('status', ReservationStatus::CANCEL_REQUESTED->value)
            ->orderBy('cancel_requested_at')
            ->get()
            ->map(fn (ReservationModel $model) => $this->mapper->toDomain($model));
    }

    public function save(Reservation $reservation): void
    {
        $model = ReservationModel::find($reservation->id()->value());
        $model = $this->mapper->toModel($reservation, $model);
        $model->save();
    }

    public function delete(Reservation $reservation): void
    {
        ReservationModel::where('id', $reservation->id()->value())->delete();
    }
}
