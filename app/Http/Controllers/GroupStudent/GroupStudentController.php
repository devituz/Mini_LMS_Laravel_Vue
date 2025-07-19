<?php

namespace App\Http\Controllers\GroupStudent;

use App\Http\Controllers\Controller;
use App\Http\Requests\GroupStudent\GroupStudentRequest;
use App\Models\Group;
use App\Models\GroupStudent;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GroupStudentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = 5;
        $search = strtolower($request->query('search', ''));

        $relations = GroupStudent::with(['student', 'group'])
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

        $formattedRelations = collect($relations->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'student' => [
                    'id' => $item->student->id,
                    'full_name' => $item->student->full_name,
                ],
                'group' => [
                    'id' => $item->group->id,
                    'name' => $item->group->name,
                ],
                'created_at' => $item->created_at->format('Y-m-d H:i'),
            ];
        });

        // Barcha student va group larni olish
        $students = Student::select('id', 'full_name')->get();
        $groups = Group::select('id', 'name')->get();

        return Inertia::render('GroupStudent', [
            'relations' => $formattedRelations,
            'pagination' => [
                'current_page' => $relations->currentPage(),
                'total_pages' => $relations->lastPage(),
                'total' => $relations->total(),
                'per_page' => $relations->perPage(),
            ],
            'search' => $search,
            'students' => $students,
            'groups' => $groups,
        ]);
    }

    public function store(GroupStudentRequest $request)
    {
        GroupStudent::create($request->validated());

        return to_route('group-students.index')->with('success', 'Bog‘lanish muvaffaqiyatli yaratildi.');
    }

    public function update(GroupStudentRequest $request, string $id)
    {
        $relation = GroupStudent::findOrFail($id);
        $relation->update($request->validated());

        return to_route('group-students.index')->with('success', 'Bog‘lanish muvaffaqiyatli yangilandi.');
    }

    public function destroy(string $id)
    {
        $relation = GroupStudent::findOrFail($id);
        $relation->delete();

        return to_route('group-students.index')->with('success', 'Bog‘lanish o‘chirildi.');
    }
}
