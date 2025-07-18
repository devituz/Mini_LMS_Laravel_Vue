<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Http\Requests\Teacher\TeacherRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $perPage = 5;
        $search = strtolower($request->query('search', ''));

        $teachers = Teacher::query()
            ->when($search, fn($query) =>
            $query->whereRaw('LOWER(full_name) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$search}%"])
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

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

    public function store(TeacherRequest $request)
    {
        Teacher::create([
            'full_name' => $request->full_name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        return to_route('teachers.index')->with('success', 'Teacher successfully created.');
    }

    public function update(TeacherRequest $request, string $id)
    {
        $teacher = Teacher::findOrFail($id);

        $teacher->update([
            'full_name' => $request->full_name,
            'phone' => $request->phone,
            'password' => $request->filled('password')
                ? Hash::make($request->password)
                : $teacher->password,
        ]);

        return to_route('teachers.index')->with('success', 'Teacher successfully updated.');
    }

    public function destroy(string $id)
    {
        $teacher = Teacher::findOrFail($id);
        $teacher->delete();

        return to_route('teachers.index')->with('success', 'Teacher successfully deleted.');
    }
}
