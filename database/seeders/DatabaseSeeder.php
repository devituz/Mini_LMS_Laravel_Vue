<?php

namespace Database\Seeders;

use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();


        $this->call([
            TeacherSeeder::class,
            StudentSeeder::class,
            GroupSeeder::class,
            GroupStudentSeeder::class,
//            DebtSeeder::class,
//            PaymentsSeeder::class,

        ]);
    }
}
