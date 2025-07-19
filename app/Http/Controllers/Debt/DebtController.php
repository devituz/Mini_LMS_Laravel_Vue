<?php

namespace App\Http\Controllers\Debt;

use App\Http\Controllers\Controller;
use App\Models\Debt;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DebtController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = 5;
        $search = strtolower($request->query('search', ''));

        $debts = Debt::with(['student', 'group'])
            ->when($search, function ($query) use ($search) {
                $query->whereHas('student', fn ($q) =>
                $q->whereRaw('LOWER(full_name) LIKE ?', ["%{$search}%"])
                )->orWhereHas('group', fn ($q) =>
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                );
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $formattedDebts = $debts->map(function ($debt) {
            return [
                'id' => $debt->id,
                'student' => [
                    'id' => $debt->student->id,
                    'full_name' => $debt->student->full_name,
                    'phone' => $debt->student->phone,
                ],
                'group' => [
                    'id' => $debt->group->id,
                    'name' => $debt->group->name,
                ],
                'amount' => $debt->amount,
                'month' => $debt->month,
                'paid_amount' => $debt->paid_amount,
                'is_paid' => $debt->is_paid,
                'status' => $debt->status,
                'created_at' => $debt->created_at->format('Y-m-d H:i'),
            ];
        });

        return Inertia::render('Debts', [
            'debts' => $formattedDebts,
            'pagination' => [
                'current_page' => $debts->currentPage(),
                'total_pages' => $debts->lastPage(),
                'total' => $debts->total(),
                'per_page' => $debts->perPage(),
            ],
            'search' => $search,
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
