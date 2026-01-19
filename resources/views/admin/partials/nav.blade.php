<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex space-x-8">
        <a href="{{ route('admin.rooms.index') }}"
           class="@if(request()->routeIs('admin.rooms.*')) border-indigo-500 text-indigo-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
            회의실 관리
        </a>
        <a href="{{ route('admin.reservations.cancel-requests') }}"
           class="@if(request()->routeIs('admin.reservations.*')) border-indigo-500 text-indigo-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
            취소 요청 관리
        </a>
    </nav>
</div>
