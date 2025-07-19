<?php

namespace App\Console\Commands;

use App\Models\Debt;
use App\Models\Payments;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateDebts extends Command
{
    protected $signature = 'debts:generate';
    protected $description = 'Har oy uchun qarzdorliklarni yaratish';

    public function handle()
    {
        $month = now()->format('Y-m');
        $students = Student::all();

        foreach ($students as $student) {
            $groupStudent = DB::table('group_student')
                ->where('student_id', $student->id)
                ->first();

            if (!$groupStudent) continue;

            $group = DB::table('groups')
                ->where('id', $groupStudent->group_id)
                ->first();

            if (!$group) continue;

            $monthlyFee = $group->monthly_fee;
            $balance = $student->balance;

            $amount = $monthlyFee;
            $paidAmount = 0;
            $isPaid = false;
            $status = 'unpaid';

            if ($balance >= $monthlyFee) {
                // To‘liq to‘landi
                $paidAmount = $monthlyFee;
                $amount = 0;
                $isPaid = true;
                $status = 'paid';
            } elseif ($balance > 0) {
                // Qisman to‘landi
                $paidAmount = $balance;
                $amount = $monthlyFee - $balance;
                $isPaid = false;
                $status = 'partial';
            }

            // Student balance dan yechish
            $student->balance -= $paidAmount;
            $student->save();

            // Debt yozish
            $debt = Debt::create([
                'student_id'  => $student->id,
                'group_id'    => $groupStudent->group_id,
                'amount'      => $amount, // qarzdorlik
                'month'       => $month,
                'paid_amount' => $paidAmount,
                'is_paid'     => $isPaid,
                'status'      => $status,
            ]);

            // Payment yozish (agar biror to‘lov bo‘lsa)
            if ($paidAmount > 0) {
                $paymentType = $isPaid ? 'balance' : 'debt';

                Payments::create([
                    'student_id' => $student->id,
                    'amount'     => $paidAmount,
                    'date'       => now(),
                    'note'       => 'Avtomatik qarzdorlikdan',
                    'type'       => $paymentType,
                    'debt_id'    => $debt->id,
                ]);
            }
        }

        $this->info('✅ Qarzdorliklar muvaffaqiyatli yaratildi!');
    }
}
