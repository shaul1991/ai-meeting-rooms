<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Infrastructure\Persistence\Eloquent\Models\RoomModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomControllerTest extends TestCase
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

    public function test_관리자는_회의실_목록을_볼_수_있다(): void
    {
        RoomModel::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.rooms.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.rooms.index');
        $response->assertViewHas('rooms');
        $response->assertViewHas('groups');
    }

    public function test_일반_사용자는_회의실_관리_페이지에_접근할_수_없다(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.rooms.index'));

        $response->assertStatus(403);
    }

    public function test_비로그인_사용자는_로그인_페이지로_리다이렉트된다(): void
    {
        $response = $this->get(route('admin.rooms.index'));

        $response->assertRedirect(route('login'));
    }

    // ==========================================
    // Create Tests
    // ==========================================

    public function test_관리자는_회의실_생성_페이지를_볼_수_있다(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.rooms.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.rooms.create');
        $response->assertViewHas('groups');
    }

    public function test_관리자는_새_회의실을_생성할_수_있다(): void
    {
        $roomData = [
            'name' => '테스트 회의실',
            'description' => '테스트 설명',
            'capacity' => 10,
            'price_per_slot' => 5000,
            'operating_hours' => [
                0 => ['start' => '', 'end' => '', 'is_closed' => '1'], // Sunday
                1 => ['start' => '09:00', 'end' => '18:00'], // Monday
                2 => ['start' => '09:00', 'end' => '18:00'], // Tuesday
                3 => ['start' => '09:00', 'end' => '18:00'], // Wednesday
                4 => ['start' => '09:00', 'end' => '18:00'], // Thursday
                5 => ['start' => '09:00', 'end' => '18:00'], // Friday
                6 => ['start' => '', 'end' => '', 'is_closed' => '1'], // Saturday
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.rooms.store'), $roomData);

        $response->assertRedirect(route('admin.rooms.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rooms', [
            'name' => '테스트 회의실',
            'capacity' => 10,
        ]);
    }

    public function test_필수_필드가_없으면_유효성_검사_실패(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.rooms.store'), []);

        $response->assertSessionHasErrors(['name', 'capacity', 'price_per_slot', 'operating_hours']);
    }

    // ==========================================
    // Edit Tests
    // ==========================================

    public function test_관리자는_회의실_수정_페이지를_볼_수_있다(): void
    {
        $room = RoomModel::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.rooms.edit', $room->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.rooms.edit');
        $response->assertViewHas('room');
        $response->assertViewHas('groups');
    }

    public function test_관리자는_회의실을_수정할_수_있다(): void
    {
        $room = RoomModel::factory()->create(['name' => '기존 회의실']);

        $updateData = [
            'name' => '수정된 회의실',
            'description' => '수정된 설명',
            'capacity' => 20,
            'price_per_slot' => 10000,
            'operating_hours' => [
                0 => ['start' => '', 'end' => '', 'is_closed' => '1'], // Sunday
                1 => ['start' => '08:00', 'end' => '20:00'], // Monday
                2 => ['start' => '08:00', 'end' => '20:00'], // Tuesday
                3 => ['start' => '08:00', 'end' => '20:00'], // Wednesday
                4 => ['start' => '08:00', 'end' => '20:00'], // Thursday
                5 => ['start' => '08:00', 'end' => '20:00'], // Friday
                6 => ['start' => '', 'end' => '', 'is_closed' => '1'], // Saturday
            ],
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.rooms.update', $room->id), $updateData);

        $response->assertRedirect(route('admin.rooms.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'name' => '수정된 회의실',
            'capacity' => 20,
        ]);
    }

    // ==========================================
    // Toggle Active Tests
    // ==========================================

    public function test_관리자는_활성화된_회의실을_비활성화할_수_있다(): void
    {
        $room = RoomModel::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->patch(route('admin.rooms.toggle-active', $room->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'is_active' => false,
        ]);
    }

    public function test_관리자는_비활성화된_회의실을_활성화할_수_있다(): void
    {
        $room = RoomModel::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->admin)->patch(route('admin.rooms.toggle-active', $room->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'is_active' => true,
        ]);
    }
}
