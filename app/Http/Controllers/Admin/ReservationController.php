<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Aggregators\ReservationAggregator;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Repositories\ReservationRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function __construct(
        private ReservationAggregator $reservationAggregator,
        private ReservationRepository $reservationRepository,
    ) {}

    public function cancelRequests(): View
    {
        $reservations = $this->reservationRepository->findPendingCancellations();

        return view('admin.reservations.cancel-requests', compact('reservations'));
    }

    public function approveCancellation(string $id): RedirectResponse
    {
        try {
            $this->reservationAggregator->approveCancellation($id);

            return redirect()
                ->route('admin.reservations.cancel-requests')
                ->with('success', '취소 요청이 승인되었습니다.');

        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function rejectCancellation(string $id): RedirectResponse
    {
        try {
            $this->reservationAggregator->rejectCancellation($id);

            return redirect()
                ->route('admin.reservations.cancel-requests')
                ->with('success', '취소 요청이 거절되었습니다.');

        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function cancel(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->reservationAggregator->cancelReservation($id, $validated['reason'] ?? null);

            return back()->with('success', '예약이 취소되었습니다.');

        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
