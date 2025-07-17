<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => $this->faker->name,
            'phone' => '+998' . $this->faker->randomElement(['90','91','93','94','97']) . $this->faker->numberBetween(1000000,9999999),
            'birth_date' => $this->faker->date(),
            'balance' => $this->faker->randomFloat(2, 0, 10000),
        ];
    }
}
