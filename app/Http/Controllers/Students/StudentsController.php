<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Http\Requests\Student\StudentRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentsController extends Controller
{

    public function index(Request $request)
    {
        $perPage = 5;
        $search = strtolower($request->query('search', ''));

        $students = Student::query()
            ->when($search, fn($query) =>
            $query->whereRaw('LOWER(full_name) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$search}%"])
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        // birth_date formatlash
        $formattedStudents = collect($students->items())->map(function ($student) {
            return [
                ...$student->toArray(),
                'birth_date' => $student->birth_date ? Carbon::parse($student->birth_date)->format('Y-m-d') : null,
            ];
        });

        return Inertia::render('Students', [
            'students' => $formattedStudents,
            'pagination' => [
                'current_page' => $students->currentPage(),
                'total_pages' => $students->lastPage(),
                'total' => $students->total(),
                'per_page' => $students->perPage(),
            ],
            'search' => $search,
        ]);}

    public function store(StudentRequest $request)
    {
        Student::create($request->validated());

        return to_route('students.index')->with('success', 'Student successfully created.');
    }

    public function update(StudentRequest $request, string $id)
    {
        $student = Student::findOrFail($id);
        $student->update($request->validated());

        return to_route('students.index')->with('success', 'Student successfully updated.');
    }

    public function destroy(string $id)
    {
        $student = Student::findOrFail($id);
        $student->delete();

        return to_route('students.index')->with('success', 'Student successfully deleted.');
    }
}
