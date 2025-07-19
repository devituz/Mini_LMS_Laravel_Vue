<?php

namespace App\Http\Requests\Group;

use Illuminate\Foundation\Http\FormRequest;

class GroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $groupId = $this->route('id') ?? $this->route('group');

        return [
            'name' => ['required', 'string', 'max:255', 'unique:groups,name' . ($groupId ? ',' . $groupId : '')],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'time' => ['required', 'string', 'max:255'],
        ];
    }
}
