<?php

namespace Database\Factories;

use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Group>
 */
class GroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word . ' group',
            'teacher_id' => Teacher::inRandomOrder()->value('id') ?? Teacher::factory(),
            'monthly_fee' => $this->faker->numberBetween(100000, 500000),
            'start_date' => $this->faker->date(),
            'time' => $this->faker->randomElement(['10:00–11:30', '14:00–15:30', '18:00–19:30']),
        ];
    }
}
