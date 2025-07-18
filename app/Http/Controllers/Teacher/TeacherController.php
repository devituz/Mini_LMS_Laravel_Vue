<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    public function index(Request $request)
    {
        $page = $request->query('page', 1);
        $perPage = 5;

        $teachers = Teacher::query()
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        return inertia('Teachers', [
            'teachers' => $teachers->items(),
            'pagination' => [
                'current_page' => $teachers->currentPage(),
                'total_pages' => $teachers->lastPage(),
                'total' => $teachers->total(),
                'per_page' => $teachers->perPage(),
            ],
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
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:6',
        ]);

        try {
            Teacher::create([
                'full_name' => $validated['full_name'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
            ]);

            return redirect()->route('teachers.index')->with('success', 'O‘qituvchi muvaffaqiyatli qo‘shildi.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'O‘qituvchi qo‘shilmadi: ' . $e->getMessage()]);
        }
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
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return redirect()->back()->withErrors(['error' => 'O‘qituvchi topilmadi.']);
        }

        try {
            $teacher->delete();
            return redirect()->route('teachers.index')->with('success', 'O‘qituvchi muvaffaqiyatli o‘chirildi.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'O‘qituvchini o‘chirishda xatolik: ' . $e->getMessage()]);
        }
    }

}
