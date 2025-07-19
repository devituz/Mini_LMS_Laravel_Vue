<?php

namespace App\Http\Requests\GroupStudent;

use Illuminate\Foundation\Http\FormRequest;

class GroupStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_id' => 'required|exists:groups,id',
            'student_id' => 'required|exists:students,id',
        ];
    }
}
