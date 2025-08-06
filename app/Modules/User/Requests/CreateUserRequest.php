<?php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for creating a user
 * Validates incoming request data
 */
class CreateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller/middleware
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'username' => 'nullable|string|unique:users,username|max:255',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'role' => 'nullable|string|exists:roles,name',
            'role_id' => 'nullable|integer|exists:roles,id',
            'work_location_id' => 'nullable|integer|exists:work_locations,id',
            'pegawai_id' => 'nullable|integer|exists:pegawais,id',
            'nip' => 'nullable|string|max:50',
            'tanggal_bergabung' => 'nullable|date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'username.unique' => 'This username is already taken.',
            'password.required' => 'The password field is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.exists' => 'The selected role is invalid.',
            'role_id.exists' => 'The selected role ID is invalid.',
            'work_location_id.exists' => 'The selected work location is invalid.',
            'pegawai_id.exists' => 'The selected employee is invalid.',
        ];
    }
}