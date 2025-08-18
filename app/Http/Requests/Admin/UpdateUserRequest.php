<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use App\Models\Role;
use App\Models\User;

/**
 * Update User Request
 * 
 * Handles validation for admin user updates with comprehensive
 * security checks and business rule validation.
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->route('user');
        
        // Check basic permission
        if (!auth()->user()?->hasPermissionTo('update_user')) {
            return false;
        }
        
        // Users can edit themselves
        if (auth()->id() === $user->id) {
            return true;
        }
        
        // Admin can edit others
        return auth()->user()?->hasRole(['admin', 'super_admin']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $user = $this->route('user');
        
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-Z\s\.\-\']+$/' // Only letters, spaces, dots, hyphens, apostrophes
            ],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
                'not_regex:/[<>"\'\{\}]/' // XSS prevention
            ],
            'password' => [
                'sometimes',
                'nullable',
                'string',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
                'confirmed'
            ],
            'password_confirmation' => [
                'required_with:password',
                'same:password'
            ],
            'role_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:roles,id',
                function ($attribute, $value, $fail) use ($user) {
                    $role = Role::find($value);
                    if (!$role) {
                        $fail('Selected role does not exist.');
                        return;
                    }
                    
                    // Prevent changing super admin unless current user is super admin
                    if ($role->name === 'super_admin' && !auth()->user()->hasRole('super_admin')) {
                        $fail('You cannot assign super admin role.');
                        return;
                    }
                    
                    // Prevent removing last super admin
                    if ($user->hasRole('super_admin') && $role->name !== 'super_admin') {
                        $superAdminCount = User::whereHas('role', function ($q) {
                            $q->where('name', 'super_admin');
                        })->count();
                        
                        if ($superAdminCount <= 1) {
                            $fail('Cannot remove the last super admin.');
                        }
                    }
                    
                    // Prevent self-demotion from admin roles
                    if (auth()->id() === $user->id && $user->hasRole(['admin', 'super_admin']) && !in_array($role->name, ['admin', 'super_admin'])) {
                        $fail('You cannot demote yourself from admin role.');
                    }
                }
            ],
            'nip' => [
                'sometimes',
                'nullable',
                'string',
                'min:3',
                'max:20',
                Rule::unique('users', 'nip')->ignore($user->id),
                'regex:/^[A-Z0-9\-]+$/' // Only uppercase letters, numbers, and hyphens
            ],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'min:10',
                'max:15',
                'regex:/^[\+]?[0-9\-\(\)\s]+$/' // International phone format
            ],
            'address' => [
                'sometimes',
                'nullable',
                'string',
                'max:500'
            ],
            'is_active' => [
                'sometimes',
                'boolean',
                function ($attribute, $value, $fail) use ($user) {
                    // Prevent self-deactivation
                    if (auth()->id() === $user->id && !$value) {
                        $fail('You cannot deactivate your own account.');
                    }
                    
                    // Prevent deactivating last super admin
                    if (!$value && $user->hasRole('super_admin')) {
                        $superAdminCount = User::whereHas('role', function ($q) {
                            $q->where('name', 'super_admin');
                        })->whereNotNull('email_verified_at')->count();
                        
                        if ($superAdminCount <= 1) {
                            $fail('Cannot deactivate the last active super admin.');
                        }
                    }
                }
            ]
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama pengguna wajib diisi.',
            'name.min' => 'Nama pengguna minimal 2 karakter.',
            'name.max' => 'Nama pengguna maksimal 255 karakter.',
            'name.regex' => 'Nama pengguna hanya boleh mengandung huruf, spasi, titik, tanda hubung, dan apostrof.',
            
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh pengguna lain.',
            'email.not_regex' => 'Email mengandung karakter yang tidak diperbolehkan.',
            
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password_confirmation.required_with' => 'Konfirmasi password wajib diisi jika mengubah password.',
            'password_confirmation.same' => 'Konfirmasi password tidak cocok.',
            
            'role_id.required' => 'Role pengguna wajib dipilih.',
            'role_id.exists' => 'Role yang dipilih tidak valid.',
            
            'nip.unique' => 'NIP sudah digunakan oleh pengguna lain.',
            'nip.regex' => 'Format NIP tidak valid. Gunakan huruf besar, angka, dan tanda hubung.',
            
            'phone.regex' => 'Format nomor telepon tidak valid.',
            'phone.min' => 'Nomor telepon minimal 10 digit.',
            'phone.max' => 'Nomor telepon maksimal 15 digit.'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [];
        
        if ($this->has('email')) {
            $data['email'] = strtolower(trim($this->email));
        }
        
        if ($this->has('name')) {
            $data['name'] = trim($this->name);
        }
        
        if ($this->has('nip')) {
            $data['nip'] = strtoupper(trim($this->nip));
        }
        
        if ($this->has('phone')) {
            $data['phone'] = preg_replace('/[^\d\+\-\(\)\s]/', '', $this->phone ?? '');
        }
        
        if ($this->has('is_active')) {
            $data['is_active'] = $this->boolean('is_active');
        }
        
        $this->merge($data);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama pengguna',
            'email' => 'email',
            'password' => 'password',
            'password_confirmation' => 'konfirmasi password',
            'role_id' => 'role',
            'nip' => 'NIP',
            'phone' => 'nomor telepon',
            'address' => 'alamat',
            'is_active' => 'status aktif'
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $user = $this->route('user');
        
        \Illuminate\Support\Facades\Log::warning('Admin user update validation failed', [
            'admin_id' => auth()->id(),
            'admin_email' => auth()->user()?->email,
            'target_user_id' => $user->id,
            'target_user_email' => $user->email,
            'errors' => $validator->errors()->toArray(),
            'input' => $this->safe()->except(['password', 'password_confirmation'])
        ]);

        parent::failedValidation($validator);
    }

    /**
     * Get validated data with processed values
     */
    public function getProcessedData(): array
    {
        $validated = $this->validated();
        
        // Hash password if provided
        if (!empty($validated['password'])) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        
        // Set email verification based on is_active
        if (isset($validated['is_active'])) {
            $validated['email_verified_at'] = $validated['is_active'] ? now() : null;
        }
        
        // Remove confirmation field
        unset($validated['password_confirmation']);
        
        // Remove null values
        return array_filter($validated, fn($value) => $value !== null);
    }

    /**
     * Additional validation for business rules
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->route('user');
            
            // Validate email domain if required and email is being changed
            if ($this->has('email')) {
                $allowedDomains = config('auth.allowed_email_domains', []);
                if (!empty($allowedDomains)) {
                    $email = $this->input('email');
                    if ($email) {
                        $domain = substr(strrchr($email, "@"), 1);
                        if (!in_array($domain, $allowedDomains)) {
                            $validator->errors()->add('email', 'Domain email tidak diperbolehkan.');
                        }
                    }
                }
            }
            
            // Check if user is trying to change critical settings without proper permissions
            if ($this->has('role_id') && auth()->id() !== $user->id) {
                if (!auth()->user()->hasRole(['admin', 'super_admin'])) {
                    $validator->errors()->add('role_id', 'Anda tidak memiliki izin untuk mengubah role pengguna.');
                }
            }
        });
    }

    /**
     * Check if password is being changed
     */
    public function isChangingPassword(): bool
    {
        return $this->filled('password');
    }

    /**
     * Check if role is being changed
     */
    public function isChangingRole(): bool
    {
        return $this->filled('role_id');
    }

    /**
     * Check if critical security settings are being changed
     */
    public function hasCriticalChanges(): bool
    {
        return $this->isChangingPassword() || 
               $this->isChangingRole() || 
               $this->filled('email') || 
               $this->filled('is_active');
    }
}