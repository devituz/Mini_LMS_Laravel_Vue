<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Student;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            [
                'name' => 'Matematika Boshlang‘ich',
                'teacher_id' => 1,
                'monthly_fee' => 150000,
                'start_date' => '2024-08-01',
                'time' => '15:00',
            ],
            [
                'name' => 'Ingliz tili A',
                'teacher_id' => 2,
                'monthly_fee' => 180000,
                'start_date' => '2024-08-05',
                'time' => '17:00',
            ],
            [
                'name' => 'Fizika O‘rta',
                'teacher_id' => 3,
                'monthly_fee' => 140000,
                'start_date' => '2024-08-10',
                'time' => '16:00',
            ],
            [
                'name' => 'Kimyo 1',
                'teacher_id' => 4,
                'monthly_fee' => 160000,
                'start_date' => '2024-08-15',
                'time' => '14:00',
            ],
            [
                'name' => 'Biologiya A',
                'teacher_id' => 5,
                'monthly_fee' => 155000,
                'start_date' => '2024-08-20',
                'time' => '13:00',
            ],
            [
                'name' => 'Matematika Oliy',
                'teacher_id' => 1,
                'monthly_fee' => 200000,
                'start_date' => '2024-09-01',
                'time' => '18:00',
            ],
            [
                'name' => 'Ingliz tili B',
                'teacher_id' => 2,
                'monthly_fee' => 185000,
                'start_date' => '2024-09-05',
                'time' => '15:30',
            ],
            [
                'name' => 'Fizika Boshlang‘ich',
                'teacher_id' => 3,
                'monthly_fee' => 130000,
                'start_date' => '2024-09-10',
                'time' => '17:00',
            ],
            [
                'name' => 'Kimyo O‘rta',
                'teacher_id' => 4,
                'monthly_fee' => 170000,
                'start_date' => '2024-09-15',
                'time' => '16:30',
            ],
            [
                'name' => 'Biologiya B',
                'teacher_id' => 5,
                'monthly_fee' => 160000,
                'start_date' => '2024-09-20',
                'time' => '14:30',
            ],
        ];

        foreach ($groups as $groupData) {
            $group = Group::create($groupData);

            // Har bir guruhga random 3–6 ta o‘quvchi biriktirish
            $studentIds = Student::inRandomOrder()->limit(rand(3, 6))->pluck('id');
            $group->students()->attach($studentIds);
        }
    }
}
