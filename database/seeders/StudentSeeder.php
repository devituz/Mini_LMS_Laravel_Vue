<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $students = [
            [
                'full_name' => 'Azizbek Jo‘rayev',
                'phone' => '+998901234567',
                'birth_date' => '2005-06-12',
                'balance' => 250000,
            ],
            [
                'full_name' => 'Dilnoza Murodova',
                'phone' => '+998901234568',
                'birth_date' => '2006-03-21',
                'balance' => 300000,
            ],
            [
                'full_name' => 'Sherzod Karimov',
                'phone' => '+998901234569',
                'birth_date' => '2004-11-03',
                'balance' => 400000,
            ],
            [
                'full_name' => 'Gulbahor Usmonova',
                'phone' => '+998901234570',
                'birth_date' => '2007-01-15',
                'balance' => 200000,
            ],
            [
                'full_name' => 'Bekzod Islomov',
                'phone' => '+998901234571',
                'birth_date' => '2003-09-30',
                'balance' => 350000,
            ],
            [
                'full_name' => 'Malika Yo‘ldosheva',
                'phone' => '+998901234572',
                'birth_date' => '2005-07-09',
                'balance' => 450000,
            ],
            [
                'full_name' => 'Zafar Bekchanov',
                'phone' => '+998901234573',
                'birth_date' => '2006-04-17',
                'balance' => 500000,
            ],
            [
                'full_name' => 'Nigora Hamroyeva',
                'phone' => '+998901234574',
                'birth_date' => '2004-12-26',
                'balance' => 280000,
            ],
            [
                'full_name' => 'Shoxrux Rajabov',
                'phone' => '+998901234575',
                'birth_date' => '2005-05-05',
                'balance' => 220000,
            ],
            [
                'full_name' => 'Sevinch Qodirova',
                'phone' => '+998901234576',
                'birth_date' => '2007-08-18',
                'balance' => 320000,
            ],
        ];

        foreach ($students as $student) {
            Student::create($student);
        }
    }
}
