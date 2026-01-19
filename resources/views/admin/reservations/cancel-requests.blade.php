@extends('layouts.app')

@section('title', '취소 요청 관리')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">취소 요청 관리</h1>
        <span class="text-sm text-gray-500">
            총 {{ $reservations->count() }}건의 취소 요청
        </span>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        예약 정보
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        예약자
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        예약 일시
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        취소 사유
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        요청 일시
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        처리
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($reservations as $reservation)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $reservation->roomId()->value() }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ number_format($reservation->totalPrice()->amount()) }}원
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $reservation->userId()->value() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $reservation->timeSlot()->startTime()->format('Y-m-d H:i') }} ~
                            {{ $reservation->timeSlot()->endTime()->format('H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $reservation->cancelReason() }}">
                                {{ $reservation->cancelReason() ?? '-' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $reservation->cancelRequestedAt()?->format('Y-m-d H:i') ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <form action="{{ route('admin.reservations.approve-cancel', $reservation->id()->value()) }}"
                                  method="POST"
                                  class="inline"
                                  onsubmit="return confirm('취소 요청을 승인하시겠습니까?')">
                                @csrf
                                <button type="submit"
                                        class="text-green-600 hover:text-green-900">
                                    승인
                                </button>
                            </form>
                            <form action="{{ route('admin.reservations.reject-cancel', $reservation->id()->value()) }}"
                                  method="POST"
                                  class="inline"
                                  onsubmit="return confirm('취소 요청을 거절하시겠습니까?')">
                                @csrf
                                <button type="submit"
                                        class="text-red-600 hover:text-red-900">
                                    거절
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            처리 대기 중인 취소 요청이 없습니다.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
