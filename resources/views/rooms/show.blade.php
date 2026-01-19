@extends('layouts.app')

@section('title', $room->name())

@section('content')
<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-lg font-medium text-gray-900">{{ $room->name() }}</h1>
                @if($room->description())
                    <p class="mt-1 text-sm text-gray-500">{{ $room->description() }}</p>
                @endif
            </div>
            <a href="{{ route('rooms.index') }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                &larr; 목록으로
            </a>
        </div>
    </div>

    <div class="p-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Room Info -->
            <div class="lg:col-span-1">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-gray-900 mb-4">회의실 정보</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm text-gray-500">수용 인원</dt>
                            <dd class="text-sm font-medium text-gray-900">최대 {{ $room->capacity() }}명</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">이용 요금</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $room->pricePerSlot()->format() }} / 30분</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Date Selector & Available Slots -->
            <div class="lg:col-span-2">
                <div class="mb-4">
                    <form method="GET" action="{{ route('rooms.show', $room->id()) }}" class="flex items-center gap-4">
                        <label for="date" class="text-sm font-medium text-gray-700">날짜 선택:</label>
                        <input type="date"
                               id="date"
                               name="date"
                               value="{{ $date->format('Y-m-d') }}"
                               min="{{ now()->format('Y-m-d') }}"
                               class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                               onchange="this.form.submit()">
                    </form>
                </div>

                <h3 class="text-sm font-medium text-gray-900 mb-4">
                    {{ $date->format('Y년 m월 d일') }} 예약 가능 시간
                </h3>

                @auth
                    @if(count($availableSlots) > 0)
                        <form method="POST" action="{{ route('reservations.store') }}" id="reservation-form">
                            @csrf
                            <input type="hidden" name="room_id" value="{{ $room->id() }}">

                            <div class="grid grid-cols-4 sm:grid-cols-6 gap-2 mb-4">
                                @foreach($availableSlots as $slot)
                                    <label class="relative">
                                        <input type="checkbox"
                                               name="slots[]"
                                               value="{{ $slot->startTime()->format('Y-m-d H:i:s') }}"
                                               data-end="{{ $slot->endTime()->format('Y-m-d H:i:s') }}"
                                               class="peer sr-only slot-checkbox">
                                        <div class="px-3 py-2 text-sm text-center border rounded-md cursor-pointer
                                                    peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600
                                                    hover:bg-gray-50">
                                            {{ $slot->startTime()->format('H:i') }}
                                        </div>
                                    </label>
                                @endforeach
                            </div>

                            <input type="hidden" name="start_time" id="start_time">
                            <input type="hidden" name="end_time" id="end_time">

                            <div class="mb-4">
                                <label for="purpose" class="block text-sm font-medium text-gray-700 mb-1">
                                    사용 목적 (선택)
                                </label>
                                <input type="text"
                                       name="purpose"
                                       id="purpose"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                       placeholder="회의, 미팅 등">
                            </div>

                            <div id="selected-info" class="hidden mb-4 p-3 bg-blue-50 rounded-md">
                                <p class="text-sm text-blue-700">
                                    선택된 시간: <span id="selected-time"></span>
                                </p>
                                <p class="text-sm text-blue-700">
                                    예상 금액: <span id="estimated-price"></span>
                                </p>
                            </div>

                            <button type="submit"
                                    id="submit-btn"
                                    disabled
                                    class="w-full sm:w-auto px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-gray-400 disabled:cursor-not-allowed">
                                예약하기
                            </button>
                        </form>

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const checkboxes = document.querySelectorAll('.slot-checkbox');
                                const submitBtn = document.getElementById('submit-btn');
                                const selectedInfo = document.getElementById('selected-info');
                                const selectedTime = document.getElementById('selected-time');
                                const estimatedPrice = document.getElementById('estimated-price');
                                const startTimeInput = document.getElementById('start_time');
                                const endTimeInput = document.getElementById('end_time');
                                const pricePerSlot = {{ $room->pricePerSlot()->amount() }};

                                function updateSelection() {
                                    const checked = Array.from(checkboxes).filter(cb => cb.checked);

                                    if (checked.length === 0) {
                                        submitBtn.disabled = true;
                                        selectedInfo.classList.add('hidden');
                                        return;
                                    }

                                    // Sort by time
                                    checked.sort((a, b) => new Date(a.value) - new Date(b.value));

                                    // Check if consecutive
                                    let isConsecutive = true;
                                    for (let i = 1; i < checked.length; i++) {
                                        const prevEnd = new Date(checked[i-1].dataset.end);
                                        const currStart = new Date(checked[i].value);
                                        if (prevEnd.getTime() !== currStart.getTime()) {
                                            isConsecutive = false;
                                            break;
                                        }
                                    }

                                    if (!isConsecutive) {
                                        alert('연속된 시간대만 선택할 수 있습니다.');
                                        checkboxes.forEach(cb => {
                                            if (!checked.slice(0, -1).includes(cb)) {
                                                cb.checked = false;
                                            }
                                        });
                                        updateSelection();
                                        return;
                                    }

                                    const startTime = new Date(checked[0].value);
                                    const endTime = new Date(checked[checked.length - 1].dataset.end);

                                    startTimeInput.value = checked[0].value;
                                    endTimeInput.value = checked[checked.length - 1].dataset.end;

                                    const formatTime = (date) => {
                                        return date.toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit' });
                                    };

                                    selectedTime.textContent = `${formatTime(startTime)} ~ ${formatTime(endTime)} (${checked.length * 30}분)`;
                                    estimatedPrice.textContent = (checked.length * pricePerSlot).toLocaleString() + ' KRW';

                                    submitBtn.disabled = false;
                                    selectedInfo.classList.remove('hidden');
                                }

                                checkboxes.forEach(cb => {
                                    cb.addEventListener('change', updateSelection);
                                });
                            });
                        </script>
                    @else
                        <p class="text-gray-500 text-sm">선택한 날짜에 예약 가능한 시간이 없습니다.</p>
                    @endif
                @else
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                        <p class="text-sm text-yellow-700">
                            예약을 하시려면 <a href="{{ route('login') }}" class="font-medium underline">로그인</a>이 필요합니다.
                        </p>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</div>
@endsection
