<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Reservation\ValueObjects\ReservationStatus;
use Database\Factories\ReservationModelFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReservationModel extends Model
{
    /** @use HasFactory<ReservationModelFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected static function newFactory(): ReservationModelFactory
    {
        return ReservationModelFactory::new();
    }

    protected $table = 'reservations';

    protected $fillable = [
        'id',
        'room_id',
        'user_id',
        'start_time',
        'end_time',
        'status',
        'total_price',
        'price_currency',
        'purpose',
        'cancel_reason',
        'cancel_requested_at',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'status' => ReservationStatus::class,
            'total_price' => 'integer',
            'cancel_requested_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(RoomModel::class, 'room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            ReservationStatus::PENDING->value,
            ReservationStatus::CONFIRMED->value,
            ReservationStatus::CANCEL_REQUESTED->value,
        ]);
    }

    public function scopeForRoom($query, string $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOverlapping($query, \DateTimeInterface $startTime, \DateTimeInterface $endTime)
    {
        return $query->where(function ($q) use ($startTime, $endTime) {
            $q->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime);
        });
    }
}
