<?php

declare(strict_types=1);

namespace App\Domain\Room\Events;

use App\Domain\Room\ValueObjects\RoomId;

final readonly class RoomCreated
{
    public function __construct(
        public RoomId $roomId,
    ) {}
}
