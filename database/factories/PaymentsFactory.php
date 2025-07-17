<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payments>
 */
class PaymentsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::inRandomOrder()->first()->id ?? Student::factory(),
            'amount' => $this->faker->randomFloat(2, 50000, 300000),
            'date' => $this->faker->date(),
            'note' => $this->faker->boolean ? $this->faker->sentence() : null,
            'type' => $this->faker->randomElement(['debt', 'balance']),
            'debt_id' => null,
        ];
    }
}
