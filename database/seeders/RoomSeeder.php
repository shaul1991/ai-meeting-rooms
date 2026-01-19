<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\RoomGroupModel;
use App\Infrastructure\Persistence\Eloquent\Models\RoomModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoomSeeder extends Seeder
{
    /**
     * 회의실 시드 데이터 생성
     *
     * 구조:
     * - 본사 빌딩
     *   - 3층 (소형 회의실)
     *   - 5층 (중형 회의실)
     *   - 10층 (대형 회의실, VIP)
     * - 분사 빌딩
     *   - 2층 (소형/중형 회의실)
     */
    public function run(): void
    {
        // 기본 운영 시간 (평일 09:00-18:00)
        $defaultOperatingHours = $this->getDefaultOperatingHours();

        // 확장 운영 시간 (평일 08:00-21:00)
        $extendedOperatingHours = $this->getExtendedOperatingHours();

        // 24시간 운영
        $alwaysOpenHours = $this->get24HoursOperatingHours();

        // === 본사 빌딩 ===
        $headquarters = RoomGroupModel::create([
            'id' => Str::uuid(),
            'name' => '본사 빌딩',
            'description' => '서울특별시 강남구 테헤란로 123',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // 3층 - 소형 회의실
        $floor3 = RoomGroupModel::create([
            'id' => Str::uuid(),
            'parent_id' => $headquarters->id,
            'name' => '3층',
            'description' => '소형 회의실 (4-6인)',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // 3층 회의실들
        RoomModel::create([
            'id' => Str::uuid(),
            'group_id' => $floor3->id,
            'name' => '스타트업 룸',
            'description' => '아이디어 회의에 적합한 소형 회의실',
            'capacity' => 4,
            'operating_hours' => $defaultOperatingHours,
            'price_per_slot' => 5000,
            'price_currency' => 'KRW',
            'is_active' => true,
            'metadata' => [
                'amenities' => ['화이트보드', 'TV 모니터', 'Wi-Fi'],
                'floor_area' => 15,
                'has_window' => true,
            ],
        ]);

        RoomModel::create([
            'id' => Str::uuid(),
            'group_id' => $floor3->id,
            'name' => '포커스 룸',
            'description' => '집중 업무와 소규모 미팅용',
            'capacity' => 6,
            'operating_hours' => $defaultOperatingHours,
            'price_per_slot' => 7000,
            'price_currency' => 'KRW',
            'is_active' => true,
            'metadata' => [
                'amenities' => ['화이트보드', 'TV 모니터', 'Wi-Fi', '전화기'],
                'floor_area' => 18,
                'has_window' => true,
            ],
        ]);

        RoomModel::create([
            'id' => Str::uuid(),
            'group_id' => $floor3->id,
            'name' => '브레인스토밍 룸',
            'description' => '창의적인 아이디어 회의용 (점검 중)',
            'capacity' => 6,
            'operating_hours' => $defaultOperatingHours,
            'price_per_slot' => 6000,
            'price_currency' => 'KRW',
            'is_active' => false, // 비활성화된 회의실
            'metadata' => [
                'amenities' => ['화이트보드', 'Wi-Fi'],
                'floor_area' => 16,
                'has_window' => false,
                'maintenance_note' => '에어컨 수리 중',
            ],
        ]);

        // 5층 - 중형 회의실
        $floor5 = RoomGroupModel::create([
            'id' => Str::uuid(),
            'parent_id' => $headquarters->id,
            'name' => '5층',
            'description' => '중형 회의실 (8-15인)',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        RoomModel::create([
            'id' => Str::uuid(),
            'group_id' => $floor5->id,
            'name' => '컨퍼런스 룸 A',
            'description' => '팀 회의 및 프레젠테이션용',
            'capacity' => 10,
            'operating_hours' => $extendedOperatingHours,
            'price_per_slot' => 15000,
            'price_currency' => 'KRW',
            'is_active' => true,
            'metadata' => [
                'amenities' => ['대형 스크린', '빔 프로젝터', '화이트보드', 'Wi-Fi', '화상회의 장비'],
                'floor_area' => 35,
                'has_window' => true,
            ],
        ]);

        RoomModel::create([
            'id' => Str::uuid(),
            'group_id' => $floor5->id,
            'name' => '컨퍼런스 룸 B',
            'description' => '팀 회의 및 워크샵용',
            'capacity' => 12,
            'operating_hours' => $extendedOperatingHours,
            'price_per_slot' => 18000,
            'price_currency' => 'KRW',
            'is_active' => true,
            'metadata' => [
                'amenities' => ['대형 스크린', '빔 프로젝터', '화이트보드', 'Wi-Fi', '화상회의 장비', '음향 시스템'],
                'floor_area' => 40,
                'has_window' => true,
            ],
        ]);

        RoomModel::create([
            'id' => Str::uuid(),
            'group_id' => $floor5->id,
            'name' => '트레이닝 룸',
            'description' => '교육 및 세미나용 대형 회의실',
            'capacity' => 15,
            'operating_hours' => $extendedOperatingHours,
            'price_per_slot' => 25000,
            'price_currency' => 'KRW',
            'is_active' => true,
            'metadata' => [
                'amenities' => ['빔 프로젝터', '대형 스크린', '마이크', '음향 시스템', 'Wi-Fi', '노트북 연결'],
                'floor_area' => 60,
                'has_window' => true,
                'layout' => '강의실 형태',
            ],
        ]);

        // 10층 - 대형/VIP 회의실
        $floor10 = RoomGroupModel::create([
            'id' => Str::uuid(),
            'parent_id' => $headquarters->id,
            'name' => '10층 (임원층)',
            'description' => '대형 회의실 및 VIP 전용 (20인 이상)',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        RoomModel::create([
            'id' => Str::uuid(),
            'group_id' => $floor10->id,
            'name' => '이사회 회의실',
            'description' => 'VIP 전용 대형 회의실',
            'capacity' => 20,
            'operating_hours' => $alwaysOpenHours,
            'price_per_slot' => 50000,
            'price_currency' => 'KRW',
            'is_active' => true,
            'metadata' => [
                'amenities' => ['최고급 가구', '대형 LED 스크린', '화상회의 시스템', '동시통역 부스', '음향 시스템', '커피 머신'],
                'floor_area' => 100,
                'has_window' => true,
                'vip' => true,
                'catering_available' => true,
            ],
        ]);

        RoomModel::create([
            'id' => Str::uuid(),
            'group_id' => $floor10->id,
            'name' => '컨벤션 홀',
            'description' => '대규모 행사 및 컨퍼런스용',
            'capacity' => 50,
            'operating_hours' => $extendedOperatingHours,
            'price_per_slot' => 100000,
            'price_currency' => 'KRW',
            'is_active' => true,
            'metadata' => [
                'amenities' => ['무대', '대형 스크린', '빔 프로젝터', '전문 음향 시스템', '조명 시스템', '마이크'],
                'floor_area' => 200,
                'has_window' => true,
                'catering_available' => true,
                'event_support' => true,
            ],
        ]);

        // === 분사 빌딩 ===
        $branchOffice = RoomGroupModel::create([
            'id' => Str::uuid(),
            'name' => '분사 빌딩',
            'description' => '서울특별시 서초구 반포대로 456',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        // 2층 - 혼합 회의실
        $branch2Floor = RoomGroupModel::create([
            'id' => Str::uuid(),
            'parent_id' => $branchOffice->id,
            'name' => '2층',
            'description' => '다목적 회의실',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        RoomModel::create([
            'id' => Str::uuid(),
            'group_id' => $branch2Floor->id,
            'name' => '미팅룸 201',
            'description' => '소규모 미팅용',
            'capacity' => 4,
            'operating_hours' => $defaultOperatingHours,
            'price_per_slot' => 4000,
            'price_currency' => 'KRW',
            'is_active' => true,
            'metadata' => [
                'amenities' => ['화이트보드', 'Wi-Fi'],
                'floor_area' => 12,
                'has_window' => true,
            ],
        ]);

        RoomModel::create([
            'id' => Str::uuid(),
            'group_id' => $branch2Floor->id,
            'name' => '미팅룸 202',
            'description' => '중소규모 회의용',
            'capacity' => 8,
            'operating_hours' => $defaultOperatingHours,
            'price_per_slot' => 10000,
            'price_currency' => 'KRW',
            'is_active' => true,
            'metadata' => [
                'amenities' => ['TV 모니터', '화이트보드', 'Wi-Fi', '화상회의 장비'],
                'floor_area' => 25,
                'has_window' => true,
            ],
        ]);

        // 비활성화된 그룹 (리모델링 중인 층)
        $branchFloor3 = RoomGroupModel::create([
            'id' => Str::uuid(),
            'parent_id' => $branchOffice->id,
            'name' => '3층 (리모델링 중)',
            'description' => '현재 리모델링 진행 중',
            'sort_order' => 2,
            'is_active' => false, // 비활성화된 그룹
        ]);

        RoomModel::create([
            'id' => Str::uuid(),
            'group_id' => $branchFloor3->id,
            'name' => '미팅룸 301',
            'description' => '리모델링 예정',
            'capacity' => 6,
            'operating_hours' => $defaultOperatingHours,
            'price_per_slot' => 6000,
            'price_currency' => 'KRW',
            'is_active' => false,
            'metadata' => [
                'amenities' => ['화이트보드'],
                'floor_area' => 16,
                'renovation_end_date' => '2026-03-01',
            ],
        ]);
    }

    /**
     * 기본 운영 시간 (평일 09:00-18:00)
     * 0=일요일, 1=월요일, ..., 6=토요일
     */
    private function getDefaultOperatingHours(): array
    {
        return [
            0 => ['is_open' => false], // 일요일
            1 => ['start' => '09:00', 'end' => '18:00'], // 월요일
            2 => ['start' => '09:00', 'end' => '18:00'], // 화요일
            3 => ['start' => '09:00', 'end' => '18:00'], // 수요일
            4 => ['start' => '09:00', 'end' => '18:00'], // 목요일
            5 => ['start' => '09:00', 'end' => '18:00'], // 금요일
            6 => ['is_open' => false], // 토요일
        ];
    }

    /**
     * 확장 운영 시간 (평일 08:00-21:00, 토요일 09:00-18:00)
     */
    private function getExtendedOperatingHours(): array
    {
        return [
            0 => ['is_open' => false], // 일요일
            1 => ['start' => '08:00', 'end' => '21:00'], // 월요일
            2 => ['start' => '08:00', 'end' => '21:00'], // 화요일
            3 => ['start' => '08:00', 'end' => '21:00'], // 수요일
            4 => ['start' => '08:00', 'end' => '21:00'], // 목요일
            5 => ['start' => '08:00', 'end' => '21:00'], // 금요일
            6 => ['start' => '09:00', 'end' => '18:00'], // 토요일
        ];
    }

    /**
     * 24시간 운영
     */
    private function get24HoursOperatingHours(): array
    {
        return [
            0 => ['start' => '00:00', 'end' => '23:59'], // 일요일
            1 => ['start' => '00:00', 'end' => '23:59'], // 월요일
            2 => ['start' => '00:00', 'end' => '23:59'], // 화요일
            3 => ['start' => '00:00', 'end' => '23:59'], // 수요일
            4 => ['start' => '00:00', 'end' => '23:59'], // 목요일
            5 => ['start' => '00:00', 'end' => '23:59'], // 금요일
            6 => ['start' => '00:00', 'end' => '23:59'], // 토요일
        ];
    }
}
