<?php

namespace App\Http\Controllers\Groups;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Http\Requests\Group\GroupRequest;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GroupsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = 5;
        $search = strtolower($request->query('search', ''));

        $groups = Group::query()
            ->when($search, fn($query) =>
            $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(time) LIKE ?', ["%{$search}%"])
            )
            ->with('teacher') // O'qituvchi ma'lumotlarini yuklash
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        // Guruhlarni formatlash
        $formattedGroups = collect($groups->items())->map(function ($group) {
            return [
                'id' => $group->id,
                'name' => $group->name,
                'teacher_id' => $group->teacher_id,
                'teacher' => $group->teacher ? [
                    'id' => $group->teacher->id,
                    'full_name' => $group->teacher->full_name,
                    'phone' => $group->teacher->phone,
                ] : null,
                'monthly_fee' => $group->monthly_fee,
                'start_date' => $group->start_date ? Carbon::parse($group->start_date)->format('Y-m-d') : null,
                'time' => $group->time,
                'created_at_formatted' => $group->created_at->format('Y-m-d H:i'),
            ];
        });

        // Barcha o'qituvchilarni olish
        $teachers = Teacher::select('id', 'full_name')->get();

        return Inertia::render('Groups', [
            'groups' => $formattedGroups,
            'pagination' => [
                'current_page' => $groups->currentPage(),
                'total_pages' => $groups->lastPage(),
                'total' => $groups->total(),
                'per_page' => $groups->perPage(),
            ],
            'search' => $search,
            'teachers' => $teachers, // O'qituvchilar ro'yxati qo'shildi
        ]);
    }

    public function store(GroupRequest $request)
    {
        Group::create($request->validated());

        return to_route('groups.index')->with('success', 'Group successfully created.');
    }

    public function update(GroupRequest $request, string $id)
    {
        $group = Group::findOrFail($id);
        $group->update($request->validated());

        return to_route('groups.index')->with('success', 'Group successfully updated.');
    }

    public function destroy(string $id)
    {
        $group = Group::findOrFail($id);
        $group->delete();

        return to_route('groups.index')->with('success', 'Group successfully deleted.');
    }
}
