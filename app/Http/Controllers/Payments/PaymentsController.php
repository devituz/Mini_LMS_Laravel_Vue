<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Payments;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = 5;
        $search = strtolower($request->query('search', ''));

        $payments = Payments::with(['student', 'debt'])
            ->when($search, function ($query) use ($search) {
                $query->whereHas('student', fn ($q) =>
                $q->whereRaw('LOWER(full_name) LIKE ?', ["%{$search}%"])
                )->orWhereHas('debt', fn ($q) =>
                $q->where('amount', 'LIKE', "%{$search}%")
                );
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $formattedPayments = $payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'student' => [
                    'id' => $payment->student?->id,
                    'full_name' => $payment->student?->full_name,
                    'phone' => $payment->student?->phone,
                ],
                'debt' => $payment->debt ? [
                    'id' => $payment->debt->id,
                    'amount' => $payment->debt->amount,
                    'month' => $payment->debt->month,
                ] : null,
                'amount' => $payment->amount,
                'date' => $payment->date,
                'note' => $payment->note,
                'type' => $payment->type,
                'created_at' => $payment->created_at->format('Y-m-d H:i'),
            ];
        });

        return Inertia::render('Payments', [
            'payments' => $formattedPayments,
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'total_pages' => $payments->lastPage(),
                'total' => $payments->total(),
                'per_page' => $payments->perPage(),
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
