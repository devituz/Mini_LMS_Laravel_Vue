<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Debt>
 */
class DebtFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 100000, 300000);
        $paid = $this->faker->randomFloat(2, 0, $amount);

        $status = match (true) {
            $paid == 0 => 'unpaid',
            $paid < $amount => 'partial',
            default => 'paid',
        };

        return [
            'student_id' => Student::inRandomOrder()->first()?->id,
            'group_id' => Group::inRandomOrder()->first()?->id,
            'amount' => $amount,
            'month' => now()->format('Y-m'),
            'paid_amount' => $paid,
            'is_paid' => $status === 'paid',
            'status' => $status,
        ];
    }
}
