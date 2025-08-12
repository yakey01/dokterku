<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use App\Rules\ConsistentRoleAssignmentRule;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    
    /**
     * Handle automatic role assignment based on source module with validation
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Clean up NIP - make it null if empty for Pegawai/Dokter
        if (isset($data['nip']) && empty(trim($data['nip']))) {
            $data['nip'] = null;
        }
        
        // For Pegawai and Dokter, NIP should be optional/nullable
        $roleId = $data['role_id'] ?? null;
        if ($roleId) {
            $role = \App\Models\Role::find($roleId);
            if ($role && in_array($role->name, ['dokter', 'pegawai', 'non_paramedis'])) {
                // For Dokter, Pegawai, and Non-Paramedis, NIP can be null
                if (empty($data['nip'])) {
                    $data['nip'] = null;
                }
            }
        }
        
        // NIP validation removed - NIP can be duplicated for multi-role users
        // Log if NIP is being reused (informational only)
        if (!empty($data['nip'])) {
            $existingUsers = \App\Models\User::where('nip', $data['nip'])->get();
            if ($existingUsers->count() > 0) {
                \Log::info('CreateUser: NIP reused for multi-role', [
                    'nip' => $data['nip'],
                    'existing_users' => $existingUsers->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'role' => $user->role ? $user->role->name : null
                        ];
                    })->toArray(),
                    'new_user_name' => $data['name'] ?? 'NOT_SET',
                    'new_user_role_id' => $data['role_id'] ?? null
                ]);
            }
        }
        
        // DEBUG: Log form data yang diterima dengan detail lebih lengkap
        \Log::info('CreateUser: Form data received', [
            'data_keys' => array_keys($data),
            'username' => $data['username'] ?? 'NOT_SET',
            'name' => $data['name'] ?? 'NOT_SET',
            'email' => $data['email'] ?? 'NOT_SET',
            'nip' => $data['nip'] ?? 'NULL',
            'role' => $role->name ?? 'unknown',
            'full_data' => $data
        ]);
        
        $source = $data['source'] ?? request()->get('source');
        $employeeType = $data['employee_type'] ?? null;
        $relatedRecordId = request()->get('related_record_id');
        
        // Email is now optional - user can be created with username only
        // Handle ConvertEmptyStringsToNull middleware effect
        if (isset($data['email']) && !is_null($data['email']) && !empty($data['email'])) {
            // Ensure email is trimmed if provided
            $data['email'] = trim($data['email']);
            
            // Validate email format if provided
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'email' => 'Format email tidak valid. Masukkan alamat email yang benar.'
                ]);
            }
        } else {
            // Email is empty/null - set to null for proper database handling
            $data['email'] = null;
        }
        
        // Validate role consistency if role_id is already set and source is provided
        if (!empty($data['role_id']) && !empty($source)) {
            $validator = new ConsistentRoleAssignmentRule($source, $employeeType, $relatedRecordId);
            
            try {
                $validator->validate('role_id', $data['role_id'], function($message) {
                    throw ValidationException::withMessages(['role_id' => $message]);
                });
            } catch (ValidationException $e) {
                // Re-throw with proper format for Filament
                throw $e;
            }
        }
        
        // Auto-assign role based on source if not already set
        if (empty($data['role_id'])) {
            $data['role_id'] = $this->getAutoAssignedRoleId($source, $employeeType);
            
            // Only require role if we can't auto-assign and no role was manually selected
            if (empty($data['role_id'])) {
                throw ValidationException::withMessages([
                    'role_id' => 'Silakan pilih role untuk user ini.'
                ]);
            }
        }
        
        // Final validation after role assignment (only if source is provided)
        if (!empty($source)) {
            $finalValidator = new ConsistentRoleAssignmentRule($source, $employeeType, $relatedRecordId);
            $finalValidator->validate('role_id', $data['role_id'], function($message) {
                throw ValidationException::withMessages(['role_id' => $message]);
            });
        }
        
        // Remove temporary fields before saving
        unset($data['source'], $data['employee_type']);
        
        // DEBUG: Log final data yang akan disimpan
        \Log::info('CreateUser: Final data to save', [
            'data_keys' => array_keys($data),
            'username' => $data['username'] ?? 'NOT_SET',
            'name' => $data['name'] ?? 'NOT_SET',
            'email' => $data['email'] ?? 'NOT_SET',
            'has_password' => isset($data['password']) ? 'YES' : 'NO',
            'final_data' => $data
        ]);
        
        return $data;
    }
    
    /**
     * Override the create method to add more debugging and protection
     */
    protected function handleRecordCreation(array $data): Model
    {
        // DEBUG: Log final data before actual creation
        \Log::info('CreateUser: Final data before Eloquent create', [
            'data_keys' => array_keys($data),
            'email_value' => $data['email'] ?? 'NOT_SET',
            'email_type' => gettype($data['email'] ?? null),
            'email_var_dump' => var_export($data['email'] ?? null, true),
            'name_value' => $data['name'] ?? 'NOT_SET',
            'username_value' => $data['username'] ?? 'NOT_SET',
            'complete_data' => $data
        ]);
        
        // Email is now optional - log for debugging but allow null values
        \Log::info('CreateUser: Email validation', [
            'email_isset' => isset($data['email']),
            'email_value' => $data['email'] ?? 'NULL',
            'email_is_null' => is_null($data['email'] ?? 'UNSET'),
            'username' => $data['username'] ?? 'NO_USERNAME'
        ]);
        
        // Ensure email is properly trimmed if provided, otherwise keep as null
        if (isset($data['email']) && !is_null($data['email']) && $data['email'] !== '') {
            $data['email'] = trim($data['email']);
        }
        
        try {
            return static::getModel()::create($data);
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database constraint violations using helper method
            $errorMessage = \App\Models\User::getConstraintViolationMessage($e);
            
            // Determine which field has the constraint violation for better UX
            if (str_contains($e->getMessage(), 'users.nip')) {
                throw ValidationException::withMessages(['nip' => $errorMessage]);
            } elseif (str_contains($e->getMessage(), 'users.username')) {
                throw ValidationException::withMessages(['username' => $errorMessage]);
            } elseif (str_contains($e->getMessage(), 'users.email')) {
                throw ValidationException::withMessages(['email' => $errorMessage]);
            } else {
                throw ValidationException::withMessages(['general' => $errorMessage]);
            }
        } catch (\Exception $e) {
            // Handle any other unexpected errors
            \Log::error('CreateUser: Unexpected error', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            throw ValidationException::withMessages([
                'general' => 'Terjadi kesalahan tidak terduga. Silakan coba lagi.'
            ]);
        }
    }
    
    /**
     * Auto-assign role based on source and employee type
     */
    private function getAutoAssignedRoleId(?string $source, ?string $employeeType): ?int
    {
        // Return null if no source is provided (manual role selection)
        if (empty($source)) {
            return null;
        }
        
        if ($source === 'dokter') {
            return Role::where('name', 'dokter')->first()?->id;
        }
        
        if ($source === 'pegawai') {
            if ($employeeType === 'paramedis') {
                return Role::where('name', 'paramedis')->first()?->id;
            } else {
                // Default non-paramedis to petugas role
                return Role::where('name', 'petugas')->first()?->id;
            }
        }
        
        return null;
    }
    
    /**
     * Redirect based on the source after creation
     */
    protected function getRedirectUrl(): string
    {
        $source = request()->get('source');
        
        return match($source) {
            'dokter' => $this->getResource()::getUrl('index') . '?source=dokter&created=1',
            'pegawai' => $this->getResource()::getUrl('index') . '?source=pegawai&created=1',
            'staff_management' => '/admin/pegawais?user_created=1',
            default => $this->getResource()::getUrl('index')
        };
    }
    
    /**
     * Get the page title based on source
     */
    public function getTitle(): string
    {
        $source = request()->get('source');
        
        return match($source) {
            'dokter' => 'Buat Akun Dokter',
            'pegawai' => 'Buat Akun Pegawai',
            'staff_management' => 'Buat Akun User (Petugas/Bendahara/Pegawai)',
            default => 'Buat User Baru'
        };
    }
    
    /**
     * Show success notification with role information
     */
    protected function getCreatedNotification(): ?\Filament\Notifications\Notification
    {
        $source = request()->get('source');
        $user = $this->getRecord();
        
        // DEBUG: Log user yang berhasil dibuat
        \Log::info('CreateUser: User created successfully', [
            'user_id' => $user->id,
            'username' => $user->username ?: 'EMPTY',
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->name,
            'is_active' => $user->is_active
        ]);
        
        $message = match($source) {
            'dokter' => "Akun dokter '{$user->name}' berhasil dibuat dengan role 'Dokter'",
            'pegawai' => "Akun pegawai '{$user->name}' berhasil dibuat dengan role '{$user->role->display_name}'",
            'staff_management' => "Akun user '{$user->name}' berhasil dibuat dengan role '{$user->role->display_name}' untuk manajemen pegawai",
            default => "User '{$user->name}' berhasil dibuat"
        };
        
        return \Filament\Notifications\Notification::make()
            ->success()
            ->title('Akun Berhasil Dibuat')
            ->body($message);
    }
}
