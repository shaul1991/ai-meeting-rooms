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

class ReservationCancellationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private RoomModel $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->room = RoomModel::factory()->create();
    }

    // ==========================================
    // 즉시 취소 테스트 (2일 전 초과)
    // ==========================================

    public function test_예약일_2일_전_초과시_즉시_취소_가능(): void
    {
        // 3일 후 예약 생성
        $startTime = (new DateTimeImmutable)->modify('+3 days')->setTime(10, 0, 0);
        $endTime = $startTime->modify('+1 hour');

        $reservation = ReservationModel::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
            'status' => ReservationStatus::CONFIRMED->value,
        ]);

        $response = $this->actingAs($this->user)->post(
            route('reservations.cancel', $reservation->id)
        );

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHas('success', '예약이 취소되었습니다.');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::CANCELLED->value,
        ]);
    }

    public function test_즉시_취소시_사유는_선택사항(): void
    {
        $startTime = (new DateTimeImmutable)->modify('+3 days')->setTime(10, 0, 0);
        $endTime = $startTime->modify('+1 hour');

        $reservation = ReservationModel::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
            'status' => ReservationStatus::CONFIRMED->value,
        ]);

        // 사유 없이 취소
        $response = $this->actingAs($this->user)->post(
            route('reservations.cancel', $reservation->id)
        );

        $response->assertRedirect(route('reservations.index'));

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::CANCELLED->value,
        ]);
    }

    public function test_즉시_취소시_사유_입력_가능(): void
    {
        $startTime = (new DateTimeImmutable)->modify('+3 days')->setTime(10, 0, 0);
        $endTime = $startTime->modify('+1 hour');

        $reservation = ReservationModel::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
            'status' => ReservationStatus::CONFIRMED->value,
        ]);

        $response = $this->actingAs($this->user)->post(
            route('reservations.cancel', $reservation->id),
            ['reason' => '일정 변경으로 인한 취소']
        );

        $response->assertRedirect(route('reservations.index'));

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::CANCELLED->value,
            'cancel_reason' => '일정 변경으로 인한 취소',
        ]);
    }

    // ==========================================
    // 취소 요청 테스트 (2일 이내)
    // ==========================================

    public function test_예약일_2일_이내시_취소_요청만_가능(): void
    {
        // 1일 후 예약 생성
        $startTime = (new DateTimeImmutable)->modify('+1 day')->setTime(10, 0, 0);
        $endTime = $startTime->modify('+1 hour');

        $reservation = ReservationModel::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
            'status' => ReservationStatus::CONFIRMED->value,
        ]);

        $response = $this->actingAs($this->user)->post(
            route('reservations.request-cancel', $reservation->id),
            ['reason' => '긴급한 일정 변경']
        );

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHas('success', '취소 요청이 접수되었습니다.');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::CANCEL_REQUESTED->value,
            'cancel_reason' => '긴급한 일정 변경',
        ]);
    }

    public function test_취소_요청시_사유는_필수(): void
    {
        $startTime = (new DateTimeImmutable)->modify('+1 day')->setTime(10, 0, 0);
        $endTime = $startTime->modify('+1 hour');

        $reservation = ReservationModel::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
            'status' => ReservationStatus::CONFIRMED->value,
        ]);

        $response = $this->actingAs($this->user)->post(
            route('reservations.request-cancel', $reservation->id),
            ['reason' => ''] // 빈 사유
        );

        $response->assertSessionHasErrors(['reason']);
    }

    // ==========================================
    // 즉시 취소 불가 테스트
    // ==========================================

    public function test_예약일_2일_이내시_즉시_취소_불가(): void
    {
        // 1일 후 예약 생성
        $startTime = (new DateTimeImmutable)->modify('+1 day')->setTime(10, 0, 0);
        $endTime = $startTime->modify('+1 hour');

        $reservation = ReservationModel::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
            'status' => ReservationStatus::CONFIRMED->value,
        ]);

        $response = $this->actingAs($this->user)->post(
            route('reservations.cancel', $reservation->id)
        );

        $response->assertSessionHasErrors(['cancel']);

        // 상태가 변경되지 않아야 함
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::CONFIRMED->value,
        ]);
    }

    // ==========================================
    // 경계 테스트 (정확히 48시간)
    // ==========================================

    public function test_정확히_48시간_전에는_즉시_취소_불가(): void
    {
        // 정확히 2일 후 10:00 예약 (슬롯 정렬됨)
        $startTime = (new DateTimeImmutable)->setTime(10, 0, 0)->modify('+2 days');
        $endTime = $startTime->modify('+1 hour');

        $reservation = ReservationModel::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
            'status' => ReservationStatus::CONFIRMED->value,
        ]);

        $response = $this->actingAs($this->user)->post(
            route('reservations.cancel', $reservation->id)
        );

        // 2일 이하이므로 즉시 취소 불가
        $response->assertSessionHasErrors(['cancel']);
    }

    public function test_48시간_초과시_즉시_취소_가능(): void
    {
        // 2일 + 1일 후 10:00 예약 (슬롯 정렬됨, 확실히 48시간 초과)
        $startTime = (new DateTimeImmutable)->setTime(10, 0, 0)->modify('+3 days');
        $endTime = $startTime->modify('+1 hour');

        $reservation = ReservationModel::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
            'status' => ReservationStatus::CONFIRMED->value,
        ]);

        $response = $this->actingAs($this->user)->post(
            route('reservations.cancel', $reservation->id)
        );

        $response->assertRedirect(route('reservations.index'));

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::CANCELLED->value,
        ]);
    }

    // ==========================================
    // 권한 테스트
    // ==========================================

    public function test_비로그인_사용자는_취소_불가(): void
    {
        $startTime = (new DateTimeImmutable)->modify('+3 days')->setTime(10, 0, 0);
        $endTime = $startTime->modify('+1 hour');

        $reservation = ReservationModel::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
            'status' => ReservationStatus::CONFIRMED->value,
        ]);

        $response = $this->post(route('reservations.cancel', $reservation->id));

        $response->assertRedirect(route('login'));
    }

    public function test_다른_사용자의_예약은_취소_불가(): void
    {
        $otherUser = User::factory()->create();

        $startTime = (new DateTimeImmutable)->modify('+3 days')->setTime(10, 0, 0);
        $endTime = $startTime->modify('+1 hour');

        $reservation = ReservationModel::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $otherUser->id, // 다른 사용자의 예약
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
            'status' => ReservationStatus::CONFIRMED->value,
        ]);

        $response = $this->actingAs($this->user)->post(
            route('reservations.cancel', $reservation->id)
        );

        $response->assertForbidden();
    }
}
