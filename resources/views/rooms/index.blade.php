@extends('layouts.app')

@section('title', '회의실 목록')

@section('content')
<div class="space-y-6">
    <!-- 날짜 선택기 -->
    <div class="bg-white shadow rounded-lg p-4">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-sm font-medium text-gray-700">날짜:</span>

                @php
                    $today = new DateTimeImmutable('today');
                    $tomorrow = new DateTimeImmutable('tomorrow');
                    $dayAfter = new DateTimeImmutable('+2 days');
                    $threeDays = new DateTimeImmutable('+3 days');
                @endphp

                <a href="{{ route('rooms.index', ['date' => $today->format('Y-m-d')]) }}"
                   class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors
                          {{ $date->format('Y-m-d') === $today->format('Y-m-d')
                              ? 'bg-indigo-600 text-white'
                              : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                    오늘
                </a>

                <a href="{{ route('rooms.index', ['date' => $tomorrow->format('Y-m-d')]) }}"
                   class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors
                          {{ $date->format('Y-m-d') === $tomorrow->format('Y-m-d')
                              ? 'bg-indigo-600 text-white'
                              : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                    내일
                </a>

                <a href="{{ route('rooms.index', ['date' => $dayAfter->format('Y-m-d')]) }}"
                   class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors
                          {{ $date->format('Y-m-d') === $dayAfter->format('Y-m-d')
                              ? 'bg-indigo-600 text-white'
                              : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                    +2일
                </a>

                <a href="{{ route('rooms.index', ['date' => $threeDays->format('Y-m-d')]) }}"
                   class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors
                          {{ $date->format('Y-m-d') === $threeDays->format('Y-m-d')
                              ? 'bg-indigo-600 text-white'
                              : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                    +3일
                </a>

                <form method="GET" action="{{ route('rooms.index') }}" class="inline-flex items-center">
                    <input type="date"
                           name="date"
                           value="{{ $date->format('Y-m-d') }}"
                           min="{{ $today->format('Y-m-d') }}"
                           class="px-3 py-1.5 text-sm rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                           onchange="this.form.submit()">
                </form>
            </div>

            <span class="text-sm text-gray-600">
                @php
                    $dayNames = ['일', '월', '화', '수', '목', '금', '토'];
                    $dayOfWeek = $dayNames[(int)$date->format('w')];
                @endphp
                {{ $date->format('Y년 m월 d일') }} ({{ $dayOfWeek }})
            </span>
        </div>
    </div>

    <!-- 회의실 목록 -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h1 class="text-lg font-medium text-gray-900">회의실 목록</h1>
            <p class="mt-1 text-sm text-gray-500">원하는 시간대를 선택하여 예약하세요.</p>
        </div>

        <div class="divide-y divide-gray-200">
            @forelse($roomsWithSlots as $roomData)
                @php
                    $room = $roomData['room'];
                    $allSlots = $roomData['allSlots'];
                @endphp
                <div class="p-4 sm:p-6">
                    <!-- 회의실 헤더 -->
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-base font-medium text-gray-900">{{ $room->name() }}</h3>
                            @if($room->description())
                                <p class="text-sm text-gray-500">{{ $room->description() }}</p>
                            @endif
                        </div>
                        <div class="text-right text-sm text-gray-500">
                            <div class="flex items-center justify-end">
                                <svg class="flex-shrink-0 mr-1 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                최대 {{ $room->capacity() }}명
                            </div>
                            <div class="flex items-center justify-end mt-1">
                                <svg class="flex-shrink-0 mr-1 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $room->pricePerSlot()->format() }} / 30분
                            </div>
                        </div>
                    </div>

                    <!-- 시간대 그리드 -->
                    @if(count($allSlots) > 0)
                        <div class="grid grid-cols-6 sm:grid-cols-9 lg:grid-cols-12 gap-1">
                            @foreach($allSlots as $slot)
                                @if($slot['available'])
                                    @auth
                                        <button type="button"
                                                class="slot-btn px-2 py-1.5 text-xs text-center rounded border border-gray-300 bg-white text-gray-700 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-colors"
                                                data-room-id="{{ $room->id()->value() }}"
                                                data-room-name="{{ $room->name() }}"
                                                data-date="{{ $date->format('Y-m-d') }}"
                                                data-time="{{ $slot['time'] }}"
                                                data-start="{{ $slot['startTime'] }}"
                                                data-end="{{ $slot['endTime'] }}"
                                                data-price="{{ $room->pricePerSlot()->amount() }}">
                                            {{ $slot['time'] }}
                                        </button>
                                    @else
                                        <button type="button"
                                                class="px-2 py-1.5 text-xs text-center rounded border border-gray-300 bg-white text-gray-700 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-colors"
                                                onclick="window.location.href='{{ route('login') }}'">
                                            {{ $slot['time'] }}
                                        </button>
                                    @endauth
                                @else
                                    <button type="button"
                                            disabled
                                            class="px-2 py-1.5 text-xs text-center rounded border border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed"
                                            title="예약됨">
                                        {{ $slot['time'] }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">선택한 날짜에 운영하지 않는 회의실입니다.</p>
                    @endif
                </div>
            @empty
                <div class="p-6 text-center text-gray-500">
                    등록된 회의실이 없습니다.
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- 예약 확인 모달 -->
<div id="reservation-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" id="modal-backdrop"></div>

        <!-- Modal -->
        <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
            <form method="POST" action="{{ route('reservations.store') }}" id="reservation-form">
                @csrf
                <input type="hidden" name="room_id" id="modal-room-id">
                <input type="hidden" name="start_time" id="modal-start-time">
                <input type="hidden" name="end_time" id="modal-end-time">

                <!-- Header -->
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900" id="modal-title">예약 확인</h3>
                </div>

                <!-- Content -->
                <div class="px-4 pb-4 sm:px-6">
                    <dl class="divide-y divide-gray-100">
                        <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500">회의실</dt>
                            <dd class="text-sm text-gray-900 sm:col-span-2" id="modal-room-name"></dd>
                        </div>
                        <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500">날짜</dt>
                            <dd class="text-sm text-gray-900 sm:col-span-2" id="modal-date"></dd>
                        </div>
                        <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500">시간</dt>
                            <dd class="text-sm text-gray-900 sm:col-span-2" id="modal-time"></dd>
                        </div>
                        <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500">예상 금액</dt>
                            <dd class="text-sm font-semibold text-indigo-600 sm:col-span-2" id="modal-price"></dd>
                        </div>
                    </dl>

                    <div class="mt-4">
                        <label for="purpose" class="block text-sm font-medium text-gray-700">사용 목적 (선택)</label>
                        <input type="text"
                               name="purpose"
                               id="purpose"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                               placeholder="회의, 미팅 등">
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                    <button type="submit"
                            class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:w-auto">
                        예약하기
                    </button>
                    <button type="button"
                            id="modal-cancel"
                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                        취소
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@auth
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('reservation-modal');
    const backdrop = document.getElementById('modal-backdrop');
    const cancelBtn = document.getElementById('modal-cancel');
    const slotButtons = document.querySelectorAll('.slot-btn');

    // 선택된 슬롯들
    let selectedSlots = [];
    let currentRoomId = null;
    let pricePerSlot = 0;

    function openModal() {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        // 선택 초기화
        selectedSlots = [];
        currentRoomId = null;
        slotButtons.forEach(btn => btn.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600'));
    }

    function updateModal() {
        if (selectedSlots.length === 0) return;

        // 시간순 정렬
        selectedSlots.sort((a, b) => new Date(a.start) - new Date(b.start));

        const firstSlot = selectedSlots[0];
        const lastSlot = selectedSlots[selectedSlots.length - 1];

        document.getElementById('modal-room-id').value = firstSlot.roomId;
        document.getElementById('modal-room-name').textContent = firstSlot.roomName;
        document.getElementById('modal-date').textContent = firstSlot.date;
        document.getElementById('modal-start-time').value = firstSlot.start;
        document.getElementById('modal-end-time').value = lastSlot.end;

        const startTime = firstSlot.time;
        const endTime = new Date(lastSlot.end).toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit', hour12: false });
        const duration = selectedSlots.length * 30;
        document.getElementById('modal-time').textContent = `${startTime} ~ ${endTime} (${duration}분)`;

        const totalPrice = selectedSlots.length * pricePerSlot;
        document.getElementById('modal-price').textContent = totalPrice.toLocaleString() + ' KRW';
    }

    function isConsecutive(slots) {
        if (slots.length <= 1) return true;

        const sorted = [...slots].sort((a, b) => new Date(a.start) - new Date(b.start));

        for (let i = 1; i < sorted.length; i++) {
            const prevEnd = new Date(sorted[i-1].end);
            const currStart = new Date(sorted[i].start);
            if (prevEnd.getTime() !== currStart.getTime()) {
                return false;
            }
        }
        return true;
    }

    slotButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const roomId = this.dataset.roomId;
            const slotData = {
                roomId: roomId,
                roomName: this.dataset.roomName,
                date: this.dataset.date,
                time: this.dataset.time,
                start: this.dataset.start,
                end: this.dataset.end
            };

            // 다른 회의실을 선택한 경우 초기화
            if (currentRoomId && currentRoomId !== roomId) {
                selectedSlots = [];
                slotButtons.forEach(b => b.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600'));
            }

            currentRoomId = roomId;
            pricePerSlot = parseInt(this.dataset.price);

            // 이미 선택된 슬롯인지 확인
            const existingIndex = selectedSlots.findIndex(s => s.start === slotData.start);

            if (existingIndex > -1) {
                // 선택 해제
                selectedSlots.splice(existingIndex, 1);
                this.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600');
            } else {
                // 선택 추가
                const testSlots = [...selectedSlots, slotData];
                if (!isConsecutive(testSlots)) {
                    alert('연속된 시간대만 선택할 수 있습니다.');
                    return;
                }

                selectedSlots.push(slotData);
                this.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600');
            }

            // 슬롯이 선택된 경우 모달 표시
            if (selectedSlots.length > 0) {
                updateModal();
                openModal();
            } else {
                closeModal();
            }
        });
    });

    // 모달 닫기
    cancelBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);

    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
});
</script>
@endauth
@endsection
