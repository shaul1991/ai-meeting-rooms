<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Aggregators\RoomAggregator;
use App\Domain\Room\ValueObjects\RoomId;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Repositories\RoomGroupRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\RoomRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function __construct(
        private RoomAggregator $roomAggregator,
        private RoomRepository $roomRepository,
        private RoomGroupRepository $roomGroupRepository,
    ) {}

    public function index(): View
    {
        $rooms = $this->roomRepository->findAll();
        $groups = $this->roomGroupRepository->findAll();

        return view('admin.rooms.index', compact('rooms', 'groups'));
    }

    public function create(): View
    {
        $groups = $this->roomGroupRepository->findAll();

        return view('admin.rooms.create', compact('groups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'required|integer|min:1',
            'price_per_slot' => 'required|integer|min:0',
            'group_id' => 'nullable|uuid',
            'operating_hours' => 'required|array',
        ]);

        $this->roomAggregator->createRoom(
            name: $validated['name'],
            capacity: $validated['capacity'],
            operatingHours: $validated['operating_hours'],
            pricePerSlot: $validated['price_per_slot'],
            description: $validated['description'] ?? null,
            groupId: $validated['group_id'] ?? null,
        );

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', '회의실이 생성되었습니다.');
    }

    public function edit(string $id): View
    {
        $room = $this->roomRepository->findByIdOrFail(RoomId::fromString($id));
        $groups = $this->roomGroupRepository->findAll();

        return view('admin.rooms.edit', compact('room', 'groups'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'required|integer|min:1',
            'price_per_slot' => 'required|integer|min:0',
            'group_id' => 'nullable|uuid',
            'operating_hours' => 'required|array',
        ]);

        $this->roomAggregator->updateRoom(
            roomId: $id,
            name: $validated['name'],
            capacity: $validated['capacity'],
            operatingHours: $validated['operating_hours'],
            pricePerSlot: $validated['price_per_slot'],
            description: $validated['description'] ?? null,
            groupId: $validated['group_id'] ?? null,
        );

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', '회의실이 수정되었습니다.');
    }

    public function toggleActive(string $id): RedirectResponse
    {
        $room = $this->roomRepository->findByIdOrFail(RoomId::fromString($id));

        if ($room->isActive()) {
            $this->roomAggregator->deactivateRoom($id);
            $message = '회의실이 비활성화되었습니다.';
        } else {
            $this->roomAggregator->activateRoom($id);
            $message = '회의실이 활성화되었습니다.';
        }

        return back()->with('success', $message);
    }
}
