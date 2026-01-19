<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Reservation\ValueObjects\ReservationStatus;
use App\Infrastructure\Persistence\Eloquent\Models\ReservationModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->user = User::factory()->create();
    }

    // ==========================================
    // Index Tests
    // ==========================================

    public function test_관리자는_취소요청_목록을_볼_수_있다(): void
    {
        ReservationModel::factory()->cancelRequested()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.reservations.cancel-requests'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.reservations.cancel-requests');
        $response->assertViewHas('reservations');
    }

    public function test_일반_사용자는_취소요청_목록에_접근할_수_없다(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.reservations.cancel-requests'));

        $response->assertStatus(403);
    }

    public function test_비로그인_사용자는_로그인_페이지로_리다이렉트된다(): void
    {
        $response = $this->get(route('admin.reservations.cancel-requests'));

        $response->assertRedirect(route('login'));
    }

    // ==========================================
    // Approve Cancellation Tests
    // ==========================================

    public function test_관리자는_취소요청을_승인할_수_있다(): void
    {
        $reservation = ReservationModel::factory()->cancelRequested()->create();

        $response = $this->actingAs($this->admin)->post(route('admin.reservations.approve-cancel', $reservation->id));

        $response->assertRedirect(route('admin.reservations.cancel-requests'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::CANCELLED->value,
        ]);
    }

    // ==========================================
    // Reject Cancellation Tests
    // ==========================================

    public function test_관리자는_취소요청을_거절할_수_있다(): void
    {
        $reservation = ReservationModel::factory()->cancelRequested()->create();

        $response = $this->actingAs($this->admin)->post(route('admin.reservations.reject-cancel', $reservation->id));

        $response->assertRedirect(route('admin.reservations.cancel-requests'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::CONFIRMED->value,
        ]);
    }
}
