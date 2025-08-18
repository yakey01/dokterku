<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use App\Models\Role;

/**
 * Create User Request
 * 
 * Handles validation for admin user creation with comprehensive
 * security checks and business rule validation.
 */
class CreateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()?->hasPermissionTo('create_user') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-Z\s\.\-\']+$/' // Only letters, spaces, dots, hyphens, apostrophes
            ],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'unique:users,email',
                'not_regex:/[<>"\'\{\}]/' // XSS prevention
            ],
            'password' => [
                'required',
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
                'required',
                'string',
                'same:password'
            ],
            'role_id' => [
                'required',
                'integer',
                'exists:roles,id',
                function ($attribute, $value, $fail) {
                    $role = Role::find($value);
                    if (!$role) {
                        $fail('Selected role does not exist.');
                        return;
                    }
                    
                    // Prevent creating super admin unless current user is super admin
                    if ($role->name === 'super_admin' && !auth()->user()->hasRole('super_admin')) {
                        $fail('You cannot assign super admin role.');
                    }
                }
            ],
            'nip' => [
                'nullable',
                'string',
                'min:3',
                'max:20',
                'unique:users,nip',
                'regex:/^[A-Z0-9\-]+$/' // Only uppercase letters, numbers, and hyphens
            ],
            'phone' => [
                'nullable',
                'string',
                'min:10',
                'max:15',
                'regex:/^[\+]?[0-9\-\(\)\s]+$/' // International phone format
            ],
            'address' => [
                'nullable',
                'string',
                'max:500'
            ],
            'is_active' => [
                'sometimes',
                'boolean'
            ],
            'send_welcome_email' => [
                'sometimes',
                'boolean'
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
            
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            
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
        $this->merge([
            'email' => strtolower(trim($this->email ?? '')),
            'name' => trim($this->name ?? ''),
            'nip' => strtoupper(trim($this->nip ?? '')),
            'phone' => preg_replace('/[^\d\+\-\(\)\s]/', '', $this->phone ?? ''),
            'is_active' => $this->boolean('is_active', true),
            'send_welcome_email' => $this->boolean('send_welcome_email', true)
        ]);
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
            'is_active' => 'status aktif',
            'send_welcome_email' => 'kirim email selamat datang'
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        \Illuminate\Support\Facades\Log::warning('Admin user creation validation failed', [
            'admin_id' => auth()->id(),
            'admin_email' => auth()->user()?->email,
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
        
        // Hash password
        $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        
        // Generate NIP if not provided
        if (empty($validated['nip'])) {
            $validated['nip'] = $this->generateUniqueNip();
        }
        
        // Set email verification based on is_active
        $validated['email_verified_at'] = ($validated['is_active'] ?? true) ? now() : null;
        
        // Remove confirmation field
        unset($validated['password_confirmation']);
        
        return $validated;
    }

    /**
     * Generate unique NIP
     */
    private function generateUniqueNip(): string
    {
        do {
            $nip = 'USR' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (\App\Models\User::where('nip', $nip)->exists());

        return $nip;
    }

    /**
     * Additional validation for business rules
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check if role allows user creation
            $roleId = $this->input('role_id');
            if ($roleId) {
                $role = Role::find($roleId);
                if ($role && $role->name === 'super_admin') {
                    $existingSuperAdmins = \App\Models\User::whereHas('role', function ($q) {
                        $q->where('name', 'super_admin');
                    })->count();
                    
                    if ($existingSuperAdmins >= 2) {
                        $validator->errors()->add('role_id', 'Maksimal 2 super admin diperbolehkan.');
                    }
                }
            }

            // Validate email domain if required
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
        });
    }
}