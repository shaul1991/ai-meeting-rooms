<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Reservation\ValueObjects\ReservationStatus;
use App\Infrastructure\Persistence\Eloquent\Models\ReservationModel;
use App\Infrastructure\Persistence\Eloquent\Models\RoomModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservationModel>
 */
class ReservationModelFactory extends Factory
{
    protected $model = ReservationModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // TimeSlot requires 30-minute aligned times
        $baseDate = fake()->dateTimeBetween('+1 day', '+7 days');
        $startHour = fake()->numberBetween(9, 16);
        $startMinute = fake()->randomElement([0, 30]);
        $startTime = $baseDate->setTime($startHour, $startMinute, 0);
        $endTime = (clone $startTime)->modify('+1 hour');

        return [
            'room_id' => RoomModel::factory(),
            'user_id' => User::factory(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => ReservationStatus::CONFIRMED->value,
            'total_price' => fake()->numberBetween(5000, 50000),
            'price_currency' => 'KRW',
            'purpose' => fake()->sentence(),
            'cancel_reason' => null,
            'cancel_requested_at' => null,
        ];
    }

    /**
     * Indicate that the reservation has a cancel request.
     */
    public function cancelRequested(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::CANCEL_REQUESTED->value,
            'cancel_reason' => fake()->sentence(),
            'cancel_requested_at' => now(),
        ]);
    }

    /**
     * Indicate that the reservation is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::CANCELLED->value,
            'cancel_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the reservation is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::CONFIRMED->value,
        ]);
    }
}
