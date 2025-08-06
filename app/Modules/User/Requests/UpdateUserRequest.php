<?php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for updating a user
 * Validates incoming request data
 */
class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user') ?? $this->route('id');
        
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $userId . '|max:255',
            'username' => 'sometimes|string|unique:users,username,' . $userId . '|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'bio' => 'nullable|string|max:1000',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'role_id' => 'sometimes|integer|exists:roles,id',
            'work_location_id' => 'sometimes|integer|exists:work_locations,id',
            'nip' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
            'language' => 'nullable|in:en,id',
            'timezone' => 'nullable|string|max:50',
            'theme' => 'nullable|in:light,dark,auto',
        ];
    }

    /**
     * Get custom messages for validator errors
     */
    public function messages(): array
    {
        return [
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'username.unique' => 'This username is already taken.',
            'gender.in' => 'Gender must be male, female, or other.',
            'role_id.exists' => 'The selected role is invalid.',
            'work_location_id.exists' => 'The selected work location is invalid.',
            'language.in' => 'Language must be en or id.',
            'theme.in' => 'Theme must be light, dark, or auto.',
        ];
    }
}