<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 관리자 계정 생성
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => '관리자',
                'password' => Hash::make('admin123'),
                'is_admin' => true,
            ]
        );

        // 일반 테스트 사용자 생성
        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => '테스트 사용자',
                'password' => Hash::make('user123'),
                'is_admin' => false,
            ]
        );

        // 회의실 데이터 시딩
        $this->call([
            RoomSeeder::class,
        ]);
    }
}
