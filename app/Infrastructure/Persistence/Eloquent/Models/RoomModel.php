<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomModel extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'rooms';

    protected $fillable = [
        'id',
        'group_id',
        'name',
        'description',
        'capacity',
        'operating_hours',
        'price_per_slot',
        'price_currency',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'operating_hours' => 'array',
            'metadata' => 'array',
            'is_active' => 'boolean',
            'capacity' => 'integer',
            'price_per_slot' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(RoomGroupModel::class, 'group_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(ReservationModel::class, 'room_id');
    }
}
