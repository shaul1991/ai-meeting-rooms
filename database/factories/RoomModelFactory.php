<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\RoomModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomModel>
 */
class RoomModelFactory extends Factory
{
    protected $model = RoomModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' 회의실',
            'description' => fake()->sentence(),
            'capacity' => fake()->numberBetween(4, 20),
            'operating_hours' => [
                0 => ['start' => null, 'end' => null, 'is_closed' => true], // Sunday
                1 => ['start' => '09:00', 'end' => '18:00', 'is_closed' => false], // Monday
                2 => ['start' => '09:00', 'end' => '18:00', 'is_closed' => false], // Tuesday
                3 => ['start' => '09:00', 'end' => '18:00', 'is_closed' => false], // Wednesday
                4 => ['start' => '09:00', 'end' => '18:00', 'is_closed' => false], // Thursday
                5 => ['start' => '09:00', 'end' => '18:00', 'is_closed' => false], // Friday
                6 => ['start' => null, 'end' => null, 'is_closed' => true], // Saturday
            ],
            'price_per_slot' => fake()->numberBetween(5000, 50000),
            'price_currency' => 'KRW',
            'is_active' => true,
            'group_id' => null,
            'metadata' => null,
        ];
    }

    /**
     * Indicate that the room is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
