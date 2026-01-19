<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Reservation\ValueObjects\ReservationStatus;
use App\Infrastructure\Persistence\Eloquent\Models\ReservationModel;
use App\Infrastructure\Persistence\Eloquent\Models\RoomModel;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // ==========================================
    // Index Tests - 날짜 우선 회의실 선택
    // ==========================================

    public function test_회의실_목록_페이지에_날짜_파라미터_없이_접근하면_오늘_날짜가_기본값이다(): void
    {
        RoomModel::factory()->count(2)->create();

        $response = $this->actingAs($this->user)->get(route('rooms.index'));

        $response->assertStatus(200);
        $response->assertViewIs('rooms.index');
        $response->assertViewHas('date');
        $response->assertViewHas('rooms');
        $response->assertViewHas('roomsWithSlots');

        $date = $response->viewData('date');
        $this->assertEquals((new DateTimeImmutable('today'))->format('Y-m-d'), $date->format('Y-m-d'));
    }

    public function test_회의실_목록_페이지에_날짜_파라미터로_접근하면_해당_날짜의_슬롯을_보여준다(): void
    {
        RoomModel::factory()->count(2)->create();

        // 다음 월요일 날짜 (회의실이 평일만 운영)
        $targetDate = $this->getNextWeekday();

        $response = $this->actingAs($this->user)->get(route('rooms.index', ['date' => $targetDate]));

        $response->assertStatus(200);
        $response->assertViewHas('date');

        $date = $response->viewData('date');
        $this->assertEquals($targetDate, $date->format('Y-m-d'));
    }

    public function test_회의실_목록에서_각_회의실의_예약_가능_슬롯과_예약된_슬롯을_구분한다(): void
    {
        $room = RoomModel::factory()->create();

        // 다음 평일 날짜
        $targetDate = $this->getNextWeekday();

        // 09:00-10:00 예약 생성
        ReservationModel::factory()->create([
            'room_id' => $room->id,
            'start_time' => $targetDate.' 09:00:00',
            'end_time' => $targetDate.' 10:00:00',
            'status' => ReservationStatus::CONFIRMED->value,
        ]);

        $response = $this->actingAs($this->user)->get(route('rooms.index', ['date' => $targetDate]));

        $response->assertStatus(200);

        $roomsWithSlots = $response->viewData('roomsWithSlots');
        $this->assertNotEmpty($roomsWithSlots);

        // 첫 번째 회의실의 슬롯 확인
        $roomData = $roomsWithSlots[0];
        $this->assertArrayHasKey('room', $roomData);
        $this->assertArrayHasKey('allSlots', $roomData);

        // 슬롯 구조 확인 - 각 슬롯은 시간과 예약 가능 여부를 포함
        $allSlots = $roomData['allSlots'];
        $this->assertNotEmpty($allSlots);

        // 09:00 슬롯은 예약됨 (available = false)
        $slot0900 = collect($allSlots)->first(fn ($s) => $s['time'] === '09:00');
        $this->assertNotNull($slot0900);
        $this->assertFalse($slot0900['available']);

        // 09:30 슬롯도 예약됨
        $slot0930 = collect($allSlots)->first(fn ($s) => $s['time'] === '09:30');
        $this->assertNotNull($slot0930);
        $this->assertFalse($slot0930['available']);

        // 10:00 슬롯은 예약 가능
        $slot1000 = collect($allSlots)->first(fn ($s) => $s['time'] === '10:00');
        $this->assertNotNull($slot1000);
        $this->assertTrue($slot1000['available']);
    }

    public function test_비로그인_사용자도_회의실_목록을_볼_수_있다(): void
    {
        RoomModel::factory()->count(2)->create();

        $response = $this->get(route('rooms.index'));

        $response->assertStatus(200);
        $response->assertViewIs('rooms.index');
    }

    public function test_비활성_회의실은_목록에_표시되지_않는다(): void
    {
        RoomModel::factory()->create(['name' => '활성 회의실']);
        RoomModel::factory()->inactive()->create(['name' => '비활성 회의실']);

        $response = $this->get(route('rooms.index'));

        $response->assertStatus(200);
        $response->assertSee('활성 회의실');
        $response->assertDontSee('비활성 회의실');
    }

    public function test_과거_날짜로_접근해도_처리된다(): void
    {
        RoomModel::factory()->create();

        $pastDate = (new DateTimeImmutable('-7 days'))->format('Y-m-d');

        $response = $this->get(route('rooms.index', ['date' => $pastDate]));

        $response->assertStatus(200);
    }

    /**
     * 다음 평일(월~금) 날짜를 반환
     */
    private function getNextWeekday(): string
    {
        $date = new DateTimeImmutable('tomorrow');
        $dayOfWeek = (int) $date->format('w');

        // 토요일(6)이면 월요일로
        if ($dayOfWeek === 6) {
            $date = $date->modify('+2 days');
        }
        // 일요일(0)이면 월요일로
        elseif ($dayOfWeek === 0) {
            $date = $date->modify('+1 day');
        }

        return $date->format('Y-m-d');
    }
}
