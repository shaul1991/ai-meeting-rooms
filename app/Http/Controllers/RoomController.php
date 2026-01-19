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

    public function index(Request $request): View
    {
        $date = $request->query('date')
            ? new DateTimeImmutable($request->query('date'))
            : new DateTimeImmutable('today');

        $rooms = $this->roomAggregator->getActiveRooms();

        // 각 회의실별로 모든 슬롯과 예약 상태를 조회
        $roomsWithSlots = [];
        foreach ($rooms as $room) {
            $roomsWithSlots[] = [
                'room' => $room,
                'allSlots' => $this->reservationAggregator->getAllSlotsWithStatus(
                    $room->id()->value(),
                    $date
                ),
            ];
        }

        return view('rooms.index', compact('rooms', 'date', 'roomsWithSlots'));
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
