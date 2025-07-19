<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\GroupRequest;
use App\Models\Group;
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

        // start_date formatlash
        $formattedGroups = collect($groups->items())->map(function ($group) {
            return [
                ...$group->toArray(),
                'start_date' => $group->start_date ? Carbon::parse($group->start_date)->format('Y-m-d') : null,
            ];
        });

        return Inertia::render('Groups', [
            'groups' => $formattedGroups,
            'pagination' => [
                'current_page' => $groups->currentPage(),
                'total_pages' => $groups->lastPage(),
                'total' => $groups->total(),
                'per_page' => $groups->perPage(),
            ],
            'search' => $search,
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
