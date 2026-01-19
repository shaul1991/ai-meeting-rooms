@extends('layouts.app')

@section('title', '로그인')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-md p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900">로그인</h1>
                <p class="text-gray-600 mt-2">회의실 예약 서비스에 오신 것을 환영합니다</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        이메일
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        placeholder="example@email.com"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition @error('email') border-red-500 @enderror"
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        비밀번호
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        placeholder="8자 이상 입력"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition @error('password') border-red-500 @enderror"
                    >
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            name="remember"
                            {{ old('remember') ? 'checked' : '' }}
                            class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                        >
                        <span class="ml-2 text-sm text-gray-600">로그인 상태 유지</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
                >
                    로그인
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    아직 계정이 없으신가요?
                    <a href="{{ route('register') }}" class="text-indigo-600 hover:text-indigo-500 font-medium">
                        회원가입
                    </a>
                </p>
            </div>

            <!-- Demo 계정 빠른 로그인 버튼 -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <p class="text-sm text-gray-500 text-center mb-3">Demo 계정으로 빠른 로그인</p>
                <div class="flex gap-3">
                    <button
                        type="button"
                        onclick="fillDemoCredentials('admin')"
                        class="flex-1 bg-red-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition text-sm"
                    >
                        👑 Admin
                    </button>
                    <button
                        type="button"
                        onclick="fillDemoCredentials('user')"
                        class="flex-1 bg-green-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition text-sm"
                    >
                        👤 User
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function fillDemoCredentials(type) {
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');

        if (type === 'admin') {
            emailInput.value = 'admin@example.com';
            passwordInput.value = 'admin123';
        } else if (type === 'user') {
            emailInput.value = 'user@example.com';
            passwordInput.value = 'user123';
        }

        // 입력 필드에 포커스 효과 적용
        emailInput.classList.add('ring-2', 'ring-indigo-500');
        passwordInput.classList.add('ring-2', 'ring-indigo-500');

        setTimeout(() => {
            emailInput.classList.remove('ring-2', 'ring-indigo-500');
            passwordInput.classList.remove('ring-2', 'ring-indigo-500');
        }, 500);
    }
</script>
@endsection
