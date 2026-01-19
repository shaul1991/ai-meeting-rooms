<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Aggregators\ReservationAggregator;
use App\Domain\Reservation\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Repositories\ReservationRepository;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function __construct(
        private ReservationAggregator $reservationAggregator,
        private ReservationRepository $reservationRepository,
    ) {}

    public function index(): View
    {
        $userId = UserId::fromString(Auth::id());
        $reservations = $this->reservationRepository->findByUserId($userId);

        return view('reservations.index', compact('reservations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'room_id' => 'required|uuid',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'purpose' => 'nullable|string|max:500',
        ]);

        try {
            $reservation = $this->reservationAggregator->createReservation(
                roomId: $validated['room_id'],
                userId: Auth::id(),
                startTime: new DateTimeImmutable($validated['start_time']),
                endTime: new DateTimeImmutable($validated['end_time']),
                purpose: $validated['purpose'] ?? null,
                isAdmin: Auth::user()->isAdmin(),
            );

            return redirect()
                ->route('reservations.index')
                ->with('success', '예약이 완료되었습니다.');

        } catch (\DomainException $e) {
            return back()
                ->withInput()
                ->withErrors(['reservation' => $e->getMessage()]);
        }
    }

    /**
     * 즉시 취소 (예약일 2일 전 초과)
     */
    public function cancel(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        // 본인 예약인지 확인
        $reservation = $this->reservationRepository->findByIdOrFail(
            \App\Domain\Reservation\ValueObjects\ReservationId::fromString($id)
        );

        if ($reservation->userId()->value() !== Auth::id()) {
            abort(403, '본인의 예약만 취소할 수 있습니다.');
        }

        try {
            $this->reservationAggregator->immediateCancel($id, $validated['reason'] ?? null);

            return redirect()
                ->route('reservations.index')
                ->with('success', '예약이 취소되었습니다.');

        } catch (\DomainException $e) {
            return back()->withErrors(['cancel' => $e->getMessage()]);
        }
    }

    /**
     * 취소 요청 (예약일 2일 이내)
     */
    public function requestCancel(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // 본인 예약인지 확인
        $reservation = $this->reservationRepository->findByIdOrFail(
            \App\Domain\Reservation\ValueObjects\ReservationId::fromString($id)
        );

        if ($reservation->userId()->value() !== Auth::id()) {
            abort(403, '본인의 예약만 취소 요청할 수 있습니다.');
        }

        try {
            $this->reservationAggregator->requestCancellation($id, $validated['reason']);

            return redirect()
                ->route('reservations.index')
                ->with('success', '취소 요청이 접수되었습니다.');

        } catch (\DomainException $e) {
            return back()->withErrors(['cancel' => $e->getMessage()]);
        }
    }
}
