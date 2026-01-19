@extends('layouts.app')

@section('title', '내 예약')

@section('content')
<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h1 class="text-lg font-medium text-gray-900">내 예약</h1>
        <p class="mt-1 text-sm text-gray-500">예약 내역을 확인하고 관리하세요.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">회의실</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">일시</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">상태</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">금액</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">작업</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($reservations as $reservation)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $reservation->roomId()->value() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $reservation->timeSlot()->startTime()->format('Y-m-d H:i') }} ~
                            {{ $reservation->timeSlot()->endTime()->format('H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'confirmed' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                    'cancel_requested' => 'bg-orange-100 text-orange-800',
                                    'completed' => 'bg-gray-100 text-gray-800',
                                    'no_show' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$reservation->status()->value] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $reservation->status()->label() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $reservation->totalPrice()->format() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            @if($reservation->status() === \App\Domain\Reservation\ValueObjects\ReservationStatus::CONFIRMED)
                                <button type="button"
                                        onclick="openCancelModal('{{ $reservation->id() }}')"
                                        class="text-red-600 hover:text-red-900">
                                    취소 요청
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            예약 내역이 없습니다.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Cancel Modal -->
<div id="cancel-modal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden" style="z-index: 50;">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">예약 취소 요청</h3>
            <form id="cancel-form" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">취소 사유</label>
                    <textarea name="reason"
                              id="reason"
                              rows="3"
                              required
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                              placeholder="취소 사유를 입력해주세요."></textarea>
                </div>
                <p class="text-sm text-gray-500 mb-4">
                    * 취소 요청은 관리자 승인 후 처리됩니다.<br>
                    * 예약일 2일 전까지만 취소 요청이 가능합니다.
                </p>
                <div class="flex justify-end space-x-3">
                    <button type="button"
                            onclick="closeCancelModal()"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        닫기
                    </button>
                    <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                        취소 요청
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openCancelModal(reservationId) {
        const form = document.getElementById('cancel-form');
        form.action = `/reservations/${reservationId}/cancel-request`;
        document.getElementById('cancel-modal').classList.remove('hidden');
    }

    function closeCancelModal() {
        document.getElementById('cancel-modal').classList.add('hidden');
    }
</script>
@endsection
