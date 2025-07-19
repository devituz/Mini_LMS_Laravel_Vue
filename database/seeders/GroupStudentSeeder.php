<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\Group;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroupStudentSeeder extends Seeder
{
    public function run(): void
    {
        $students = Student::all();
        $groups = Group::all();

        if ($groups->isEmpty() || $students->isEmpty()) {
            $this->command->warn('Group yoki Student maʼlumotlari mavjud emas.');
            return;
        }

        $groupIds = $groups->pluck('id')->toArray();
        $groupCount = count($groupIds);
        $groupIndex = 0;

        foreach ($students as $student) {
            DB::table('group_student')->insert([
                'group_id' => $groupIds[$groupIndex],
                'student_id' => $student->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $groupIndex = ($groupIndex + 1) % $groupCount; // round-robin
        }
    }
}
