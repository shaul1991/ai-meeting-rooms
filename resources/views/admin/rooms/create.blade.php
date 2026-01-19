@extends('layouts.app')

@section('title', '회의실 추가')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">회의실 추가</h1>
        <a href="{{ route('admin.rooms.index') }}"
           class="text-gray-600 hover:text-gray-900">
            목록으로
        </a>
    </div>

    <form action="{{ route('admin.rooms.store') }}" method="POST" class="bg-white shadow-md rounded-lg p-6 space-y-6">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                회의실명 <span class="text-red-500">*</span>
            </label>
            <input type="text"
                   id="name"
                   name="name"
                   value="{{ old('name') }}"
                   required
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-500 @enderror">
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                설명
            </label>
            <textarea id="description"
                      name="description"
                      rows="3"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
            @error('description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="capacity" class="block text-sm font-medium text-gray-700 mb-1">
                    수용인원 <span class="text-red-500">*</span>
                </label>
                <input type="number"
                       id="capacity"
                       name="capacity"
                       value="{{ old('capacity') }}"
                       min="1"
                       required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('capacity') border-red-500 @enderror">
                @error('capacity')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="price_per_slot" class="block text-sm font-medium text-gray-700 mb-1">
                    시간당 요금 (원) <span class="text-red-500">*</span>
                </label>
                <input type="number"
                       id="price_per_slot"
                       name="price_per_slot"
                       value="{{ old('price_per_slot') }}"
                       min="0"
                       required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('price_per_slot') border-red-500 @enderror">
                @error('price_per_slot')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="group_id" class="block text-sm font-medium text-gray-700 mb-1">
                그룹
            </label>
            <select id="group_id"
                    name="group_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">그룹 없음</option>
                @foreach($groups as $group)
                    <option value="{{ $group->id()->value() }}" {{ old('group_id') == $group->id()->value() ? 'selected' : '' }}>
                        {{ $group->name() }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">
                운영시간 <span class="text-red-500">*</span>
            </label>
            @php
                $days = [
                    1 => '월요일',
                    2 => '화요일',
                    3 => '수요일',
                    4 => '목요일',
                    5 => '금요일',
                    6 => '토요일',
                    0 => '일요일',
                ];
            @endphp
            <div class="space-y-3">
                @foreach($days as $dayNum => $dayName)
                    <div class="flex items-center space-x-4">
                        <label class="w-20 text-sm text-gray-600">{{ $dayName }}</label>
                        <input type="time"
                               name="operating_hours[{{ $dayNum }}][start]"
                               value="{{ old("operating_hours.$dayNum.start", $dayNum >= 1 && $dayNum <= 5 ? '09:00' : '') }}"
                               class="px-3 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <span class="text-gray-500">~</span>
                        <input type="time"
                               name="operating_hours[{{ $dayNum }}][end]"
                               value="{{ old("operating_hours.$dayNum.end", $dayNum >= 1 && $dayNum <= 5 ? '18:00' : '') }}"
                               class="px-3 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <label class="flex items-center">
                            <input type="checkbox"
                                   name="operating_hours[{{ $dayNum }}][is_closed]"
                                   value="1"
                                   {{ old("operating_hours.$dayNum.is_closed", $dayNum == 0 || $dayNum == 6 ? true : false) ? 'checked' : '' }}
                                   class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-600">휴무</span>
                        </label>
                    </div>
                @endforeach
            </div>
            @error('operating_hours')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('admin.rooms.index') }}"
               class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                취소
            </a>
            <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                저장
            </button>
        </div>
    </form>
</div>
@endsection
