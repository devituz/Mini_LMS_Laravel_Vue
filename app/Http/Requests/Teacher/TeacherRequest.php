<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class TeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $teacherId = $this->route('id') ?? $this->route('teacher');

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:13',
                'unique:teachers,phone' . ($teacherId ? ',' . $teacherId : ''),
            ],
            'password' => [
                $this->isMethod('POST') ? 'required' : 'nullable',
                'string',
                'min:6',
            ],
        ];
    }
}
