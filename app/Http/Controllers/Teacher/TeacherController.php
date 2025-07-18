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
        $search = $request->query('search', '');

        // Start with a query for all Teacher records
        $query = Teacher::query();

        // Apply search filters across all records if a search term is provided
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(full_name) LIKE ?', ['%' . strtolower($search) . '%'])
                    ->orWhereRaw('LOWER(phone) LIKE ?', ['%' . strtolower($search) . '%']);
            });
        }

        // Paginate the filtered results
        $teachers = $query->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        // Log the results for debugging
        \Log::info('Teachers fetched:', $teachers->toArray());

        // Return the Inertia response with teachers, pagination, and search term
        return Inertia::render('Teachers', [
            'teachers' => $teachers->items(),
            'pagination' => [
                'current_page' => $teachers->currentPage(),
                'total_pages' => $teachers->lastPage(),
                'total' => $teachers->total(),
                'per_page' => $teachers->perPage(),
            ],
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Teachers');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:teachers,phone',
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
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return redirect()->route('teachers.index')->withErrors(['error' => 'O‘qituvchi topilmadi.']);
        }

        return inertia('Teachers', [
            'teachers' => Teacher::latest()->paginate(5)->items(),
            'pagination' => [
                'current_page' => 1,
                'total_pages' => 1,
                'total' => Teacher::count(),
                'per_page' => 5,
            ],
            'editTeacher' => [
                'id' => $teacher->id,
                'full_name' => $teacher->full_name,
                'phone' => $teacher->phone,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return redirect()->route('teachers.index')->withErrors(['error' => 'O‘qituvchi topilmadi.']);
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:teachers,phone,' . $id,
            'password' => 'nullable|string|min:6',
        ]);

        try {
            $teacher->full_name = $validated['full_name'];
            $teacher->phone = $validated['phone'];
            if (!empty($validated['password'])) {
                $teacher->password = Hash::make($validated['password']);
            }
            $teacher->save();

            return redirect()->route('teachers.index')->with('success', 'O‘qituvchi muvaffaqiyatli yangilandi.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'O‘qituvchini yangilashda xatolik: ' . $e->getMessage()]);
        }
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
