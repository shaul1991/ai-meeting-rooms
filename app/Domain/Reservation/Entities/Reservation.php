<?php

declare(strict_types=1);

namespace App\Domain\Reservation\Entities;

use App\Domain\Reservation\Events\ReservationCancelled;
use App\Domain\Reservation\Events\ReservationCancelRequested;
use App\Domain\Reservation\Events\ReservationConfirmed;
use App\Domain\Reservation\Events\ReservationCreated;
use App\Domain\Reservation\ValueObjects\ReservationId;
use App\Domain\Reservation\ValueObjects\ReservationStatus;
use App\Domain\Reservation\ValueObjects\TimeSlot;
use App\Domain\Reservation\ValueObjects\UserId;
use App\Domain\Room\ValueObjects\Money;
use App\Domain\Room\ValueObjects\RoomId;
use DateTimeImmutable;
use DomainException;

class Reservation
{
    public const int MAX_DURATION_MINUTES = 120; // 일반 사용자 최대 2시간

    private array $domainEvents = [];

    private function __construct(
        private ReservationId $id,
        private RoomId $roomId,
        private UserId $userId,
        private TimeSlot $timeSlot,
        private ReservationStatus $status,
        private Money $totalPrice,
        private ?string $purpose,
        private ?string $cancelReason,
        private ?DateTimeImmutable $cancelRequestedAt,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        RoomId $roomId,
        UserId $userId,
        TimeSlot $timeSlot,
        Money $pricePerSlot,
        ?string $purpose = null,
        bool $isAdmin = false,
    ): self {
        // 일반 사용자 최대 예약 시간 제한
        if (! $isAdmin && $timeSlot->durationInMinutes() > self::MAX_DURATION_MINUTES) {
            throw new DomainException(
                '일반 사용자는 최대 '.self::MAX_DURATION_MINUTES.'분까지만 예약할 수 있습니다.'
            );
        }

        $totalPrice = $pricePerSlot->multiply($timeSlot->slotCount());

        $reservation = new self(
            id: ReservationId::generate(),
            roomId: $roomId,
            userId: $userId,
            timeSlot: $timeSlot,
            status: ReservationStatus::CONFIRMED,
            totalPrice: $totalPrice,
            purpose: $purpose,
            cancelReason: null,
            cancelRequestedAt: null,
            createdAt: new DateTimeImmutable,
            updatedAt: new DateTimeImmutable,
        );

        $reservation->recordEvent(new ReservationCreated(
            reservationId: $reservation->id,
            roomId: $roomId,
            userId: $userId,
            timeSlot: $timeSlot,
        ));

        return $reservation;
    }

    public static function reconstitute(
        ReservationId $id,
        RoomId $roomId,
        UserId $userId,
        TimeSlot $timeSlot,
        ReservationStatus $status,
        Money $totalPrice,
        ?string $purpose,
        ?string $cancelReason,
        ?DateTimeImmutable $cancelRequestedAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            roomId: $roomId,
            userId: $userId,
            timeSlot: $timeSlot,
            status: $status,
            totalPrice: $totalPrice,
            purpose: $purpose,
            cancelReason: $cancelReason,
            cancelRequestedAt: $cancelRequestedAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function confirm(): void
    {
        $this->transitionTo(ReservationStatus::CONFIRMED);

        $this->recordEvent(new ReservationConfirmed($this->id));
    }

    /**
     * 즉시 취소 가능 여부 확인 (예약일 48시간 초과)
     */
    public function canCancelImmediately(): bool
    {
        $twoDaysBefore = $this->timeSlot->startTime()->modify('-2 days');

        return new DateTimeImmutable < $twoDaysBefore;
    }

    /**
     * 즉시 취소 (예약일 2일 전 초과 시)
     */
    public function cancelImmediately(?string $reason = null): void
    {
        if (! $this->canCancelImmediately()) {
            throw new DomainException('예약일 2일 전부터는 즉시 취소가 불가능합니다. 취소 요청을 이용해주세요.');
        }

        $this->transitionTo(ReservationStatus::CANCELLED);

        if ($reason !== null) {
            $this->cancelReason = $reason;
        }

        $this->recordEvent(new ReservationCancelled($this->id));
    }

    /**
     * 취소 요청 (예약일 2일 이내)
     */
    public function requestCancel(string $reason): void
    {
        // 예약일 2일 이내만 취소 요청 가능 (2일 초과면 즉시 취소 사용)
        if ($this->canCancelImmediately()) {
            throw new DomainException('예약일 2일 전 초과 시에는 즉시 취소를 이용해주세요.');
        }

        // 예약 시작 시간 이후에는 취소 요청 불가
        if (new DateTimeImmutable >= $this->timeSlot->startTime()) {
            throw new DomainException('예약 시작 시간 이후에는 취소할 수 없습니다.');
        }

        $this->transitionTo(ReservationStatus::CANCEL_REQUESTED);
        $this->cancelReason = $reason;
        $this->cancelRequestedAt = new DateTimeImmutable;

        $this->recordEvent(new ReservationCancelRequested(
            reservationId: $this->id,
            reason: $reason,
        ));
    }

    public function cancel(?string $reason = null): void
    {
        $this->transitionTo(ReservationStatus::CANCELLED);

        if ($reason !== null) {
            $this->cancelReason = $reason;
        }

        $this->recordEvent(new ReservationCancelled($this->id));
    }

    public function complete(): void
    {
        $this->transitionTo(ReservationStatus::COMPLETED);
    }

    public function markAsNoShow(): void
    {
        $this->transitionTo(ReservationStatus::NO_SHOW);
    }

    public function rejectCancelRequest(): void
    {
        if ($this->status !== ReservationStatus::CANCEL_REQUESTED) {
            throw new DomainException('취소 요청 상태에서만 거절할 수 있습니다.');
        }

        $this->status = ReservationStatus::CONFIRMED;
        $this->cancelReason = null;
        $this->cancelRequestedAt = null;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function overlaps(TimeSlot $timeSlot): bool
    {
        return $this->timeSlot->overlaps($timeSlot) && $this->status->isActive();
    }

    private function transitionTo(ReservationStatus $newStatus): void
    {
        if (! $this->status->canTransitionTo($newStatus)) {
            throw new DomainException(
                "Cannot transition from {$this->status->value} to {$newStatus->value}"
            );
        }

        $this->status = $newStatus;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function id(): ReservationId
    {
        return $this->id;
    }

    public function roomId(): RoomId
    {
        return $this->roomId;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function timeSlot(): TimeSlot
    {
        return $this->timeSlot;
    }

    public function status(): ReservationStatus
    {
        return $this->status;
    }

    public function totalPrice(): Money
    {
        return $this->totalPrice;
    }

    public function purpose(): ?string
    {
        return $this->purpose;
    }

    public function cancelReason(): ?string
    {
        return $this->cancelReason;
    }

    public function cancelRequestedAt(): ?DateTimeImmutable
    {
        return $this->cancelRequestedAt;
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
