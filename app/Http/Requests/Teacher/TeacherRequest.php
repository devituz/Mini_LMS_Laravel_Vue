<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return match ($this->route()->getName()) {
            'teacher.register', 'teacher.login' => true,
            'teacher.profile.update' => Auth::guard('teacher')->check(),
            default => false,
        };
    }

    public function rules(): array
    {
        return match ($this->route()->getName()) {
            'teacher.register' => $this->registerRules(),
            'teacher.login' => $this->loginRules(),
            'teacher.profile.update' => $this->updateRules(),
            'teacher.profile.get' => [],
            default => [],
        };
    }

    protected function registerRules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:teachers,phone',
            'password' => 'required|string|confirmed|min:6',
        ];
    }

    protected function loginRules(): array
    {
        return [
            'phone' => 'required|string|exists:teachers,phone',
            'password' => 'required|string',
        ];
    }

    protected function updateRules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'phone' => [
                'required',
                'string',
                'max:255',
                Rule::unique('teachers', 'phone')->ignore($this->user('teacher')?->id),
            ],
            'password' => 'nullable|string|min:6|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.exists' => __('auth.failed'), // Use same error message for consistency
        ];
    }
}
