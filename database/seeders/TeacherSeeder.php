<?php

namespace Database\Seeders;

use App\Models\Teacher;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teachers = [
            [
                'full_name' => 'Jasur Tursunov',
                'phone' => '998901234567',
                'password' => 'teacher123',
            ],
            [
                'full_name' => 'Malika Karimova',
                'phone' => '998911112233',
                'password' => 'hello2024',
            ],
            [
                'full_name' => 'Shavkat Ismoilov',
                'phone' => '998931234567',
                'password' => 'qwerty789',
            ],
            [
                'full_name' => 'Dilnoza Toirova',
                'phone' => '998941234567',
                'password' => 'pass4567',
            ],
            [
                'full_name' => 'Temur Usmonov',
                'phone' => '998951234567',
                'password' => 'secure998',
            ],
        ];

        foreach ($teachers as $data) {
            Teacher::create([
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
            ]);
        }
    }
}
