<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Aggregators\ReservationAggregator;
use App\Application\Aggregators\RoomAggregator;
use App\Domain\Room\ValueObjects\RoomId;
use App\Infrastructure\Persistence\Eloquent\Repositories\RoomRepository;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function __construct(
        private RoomAggregator $roomAggregator,
        private ReservationAggregator $reservationAggregator,
        private RoomRepository $roomRepository,
    ) {}

    public function index(): View
    {
        $rooms = $this->roomAggregator->getActiveRooms();

        return view('rooms.index', compact('rooms'));
    }

    public function show(string $id, Request $request): View
    {
        $room = $this->roomRepository->findByIdOrFail(RoomId::fromString($id));

        $date = $request->query('date')
            ? new DateTimeImmutable($request->query('date'))
            : new DateTimeImmutable('today');

        $availableSlots = $this->reservationAggregator->getAvailableSlots($id, $date);

        return view('rooms.show', compact('room', 'date', 'availableSlots'));
    }
}
