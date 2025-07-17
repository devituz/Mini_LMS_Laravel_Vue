<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Debt;
use App\Models\Group;
use App\Models\Payments;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $today = Carbon::today();

        return Inertia::render('Dashboard', [
            'studentCount'    => Student::count(),
            'teacherCount'    => Teacher::count(),
            'groupCount'      => Group::count(),
            'todaysRevenue'   => Payments::whereDate('date', $today)->sum('amount'),
            'totalDebt'       => Debt::sum('amount'),
        ]);
    }
}
