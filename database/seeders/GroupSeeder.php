<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Student;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Group::factory(3)->create()->each(function ($group) {
            $studentIds = \App\Models\Student::inRandomOrder()->limit(rand(3, 6))->pluck('id');
            $group->students()->attach($studentIds);
        });

    }
}
