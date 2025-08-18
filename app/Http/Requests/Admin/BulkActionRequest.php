<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;

/**
 * Bulk Action Request
 * 
 * Handles validation for admin bulk operations with comprehensive
 * security checks and business rule validation.
 */
class BulkActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()?->hasRole(['admin', 'super_admin']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'action' => [
                'required',
                'string',
                Rule::in(['delete', 'activate', 'deactivate', 'change_role', 'export'])
            ],
            'user_ids' => [
                'required',
                'array',
                'min:1',
                'max:100' // Limit bulk operations to prevent performance issues
            ],
            'user_ids.*' => [
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    // Prevent operations on self
                    if ($value == auth()->id()) {
                        $fail('You cannot perform bulk operations on your own account.');
                    }
                    
                    // Check if user exists and get user data
                    $user = User::find($value);
                    if (!$user) {
                        $fail('User not found.');
                        return;
                    }
                    
                    // Prevent operations on super admin unless current user is super admin
                    if ($user->hasRole('super_admin') && !auth()->user()->hasRole('super_admin')) {
                        $fail('You cannot perform operations on super admin accounts.');
                    }
                }
            ],
            'role_id' => [
                'required_if:action,change_role',
                'integer',
                'exists:roles,id',
                function ($attribute, $value, $fail) {
                    if ($this->input('action') === 'change_role' && $value) {
                        $role = \App\Models\Role::find($value);
                        if ($role && $role->name === 'super_admin' && !auth()->user()->hasRole('super_admin')) {
                            $fail('You cannot assign super admin role.');
                        }
                    }
                }
            ],
            'reason' => [
                'sometimes',
                'string',
                'max:500'
            ],
            'confirm_action' => [
                'required_if:action,delete',
                'accepted'
            ]
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Aksi wajib dipilih.',
            'action.in' => 'Aksi yang dipilih tidak valid.',
            
            'user_ids.required' => 'Minimal satu pengguna harus dipilih.',
            'user_ids.array' => 'Format data pengguna tidak valid.',
            'user_ids.min' => 'Minimal satu pengguna harus dipilih.',
            'user_ids.max' => 'Maksimal 100 pengguna dapat diproses sekaligus.',
            'user_ids.*.exists' => 'Pengguna yang dipilih tidak valid.',
            
            'role_id.required_if' => 'Role wajib dipilih untuk aksi ubah role.',
            'role_id.exists' => 'Role yang dipilih tidak valid.',
            
            'reason.max' => 'Alasan maksimal 500 karakter.',
            
            'confirm_action.required_if' => 'Konfirmasi wajib untuk aksi hapus.',
            'confirm_action.accepted' => 'Anda harus mengkonfirmasi aksi ini.'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'action' => strtolower(trim($this->action ?? '')),
            'user_ids' => array_unique(array_filter($this->user_ids ?? [])),
            'confirm_action' => $this->boolean('confirm_action')
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'action' => 'aksi',
            'user_ids' => 'pengguna',
            'user_ids.*' => 'pengguna',
            'role_id' => 'role',
            'reason' => 'alasan',
            'confirm_action' => 'konfirmasi'
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        \Illuminate\Support\Facades\Log::warning('Admin bulk action validation failed', [
            'admin_id' => auth()->id(),
            'admin_email' => auth()->user()?->email,
            'action' => $this->input('action'),
            'user_count' => count($this->input('user_ids', [])),
            'errors' => $validator->errors()->toArray()
        ]);

        parent::failedValidation($validator);
    }

    /**
     * Additional validation for business rules
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $action = $this->input('action');
            $userIds = $this->input('user_ids', []);
            
            if ($action === 'delete') {
                $this->validateDeleteAction($validator, $userIds);
            } elseif ($action === 'deactivate') {
                $this->validateDeactivateAction($validator, $userIds);
            } elseif ($action === 'change_role') {
                $this->validateChangeRoleAction($validator, $userIds);
            }
        });
    }

    /**
     * Validate delete action
     */
    private function validateDeleteAction($validator, array $userIds): void
    {
        $superAdmins = User::whereIn('id', $userIds)
            ->whereHas('role', function ($q) {
                $q->where('name', 'super_admin');
            })
            ->count();
        
        if ($superAdmins > 0) {
            $totalSuperAdmins = User::whereHas('role', function ($q) {
                $q->where('name', 'super_admin');
            })->count();
            
            if ($totalSuperAdmins - $superAdmins < 1) {
                $validator->errors()->add('user_ids', 'Cannot delete all super admin accounts.');
            }
        }
        
        // Check for users with critical data
        $usersWithCriticalData = User::whereIn('id', $userIds)
            ->where(function ($q) {
                $q->whereHas('tindakans')
                  ->orWhereHas('pendapatans')
                  ->orWhereHas('pengeluarans');
            })
            ->count();
        
        if ($usersWithCriticalData > 0) {
            $validator->errors()->add('user_ids', 'Some users have critical data and cannot be deleted. Consider deactivating instead.');
        }
    }

    /**
     * Validate deactivate action
     */
    private function validateDeactivateAction($validator, array $userIds): void
    {
        $activeSuperAdmins = User::whereIn('id', $userIds)
            ->whereHas('role', function ($q) {
                $q->where('name', 'super_admin');
            })
            ->whereNotNull('email_verified_at')
            ->count();
        
        if ($activeSuperAdmins > 0) {
            $totalActiveSuperAdmins = User::whereHas('role', function ($q) {
                $q->where('name', 'super_admin');
            })->whereNotNull('email_verified_at')->count();
            
            if ($totalActiveSuperAdmins - $activeSuperAdmins < 1) {
                $validator->errors()->add('user_ids', 'Cannot deactivate all super admin accounts.');
            }
        }
    }

    /**
     * Validate change role action
     */
    private function validateChangeRoleAction($validator, array $userIds): void
    {
        $targetRoleId = $this->input('role_id');
        if (!$targetRoleId) {
            return;
        }
        
        $targetRole = \App\Models\Role::find($targetRoleId);
        if (!$targetRole) {
            return;
        }
        
        // Check super admin limitations
        if ($targetRole->name === 'super_admin') {
            $currentSuperAdminCount = User::whereHas('role', function ($q) {
                $q->where('name', 'super_admin');
            })->count();
            
            $newSuperAdminCount = count($userIds);
            
            if ($currentSuperAdminCount + $newSuperAdminCount > 5) {
                $validator->errors()->add('role_id', 'Maximum 5 super admin accounts allowed.');
            }
        }
        
        // Check if demoting super admins
        if ($targetRole->name !== 'super_admin') {
            $superAdminsBeingDemoted = User::whereIn('id', $userIds)
                ->whereHas('role', function ($q) {
                    $q->where('name', 'super_admin');
                })
                ->count();
            
            if ($superAdminsBeingDemoted > 0) {
                $remainingSuperAdmins = User::whereNotIn('id', $userIds)
                    ->whereHas('role', function ($q) {
                        $q->where('name', 'super_admin');
                    })
                    ->count();
                
                if ($remainingSuperAdmins < 1) {
                    $validator->errors()->add('role_id', 'Cannot demote all super admin accounts.');
                }
            }
        }
    }

    /**
     * Get action summary for logging
     */
    public function getActionSummary(): array
    {
        return [
            'action' => $this->input('action'),
            'user_count' => count($this->input('user_ids', [])),
            'target_role_id' => $this->input('role_id'),
            'reason' => $this->input('reason'),
            'admin_id' => auth()->id(),
            'admin_email' => auth()->user()?->email,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Check if action requires confirmation
     */
    public function requiresConfirmation(): bool
    {
        return in_array($this->input('action'), ['delete', 'change_role']);
    }

    /**
     * Check if action is destructive
     */
    public function isDestructiveAction(): bool
    {
        return in_array($this->input('action'), ['delete', 'deactivate']);
    }

    /**
     * Get affected user IDs (excluding current user)
     */
    public function getAffectedUserIds(): array
    {
        $userIds = $this->input('user_ids', []);
        return array_values(array_filter($userIds, fn($id) => $id != auth()->id()));
    }
}