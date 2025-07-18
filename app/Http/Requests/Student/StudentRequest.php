<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $studentId = $this->route('id') ?? $this->route('student');

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:13',
                'unique:students,phone' . ($studentId ? ',' . $studentId : ''),
            ],
            'birth_date' => ['nullable', 'date'],
            'balance' => ['nullable', 'numeric'],
        ];
    }
}
