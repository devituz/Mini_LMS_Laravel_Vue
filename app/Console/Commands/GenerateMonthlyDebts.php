<?php

namespace App\Console\Commands;

use App\Models\Debt;
use App\Models\Group;
use Illuminate\Console\Command;

class GenerateMonthlyDebts extends Command
{
    protected $signature = 'debts:generate';
    protected $description = 'Har oyning 1-sanasida har bir o‘quvchiga qarzdorlik yozish';

    public function handle(): void
    {
        $month = now()->format('Y-m');

        $groups = Group::with('students')->get();

        foreach ($groups as $group) {
            foreach ($group->students as $student) {
                $alreadyExists = Debt::where('student_id', $student->id)
                    ->where('month', $month)
                    ->exists();

                if (!$alreadyExists) {
                    Debt::create([
                        'student_id' => $student->id,
                        'group_id' => $group->id,
                        'amount' => $group->monthly_fee,
                        'month' => $month,
                        'paid_amount' => 0,
                        'is_paid' => false,
                        'status' => 'unpaid',
                    ]);
                }
            }
        }

        $this->info("✅ Qarzdorliklar $month uchun yaratildi.");
    }
}
