<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    /**
     * Handle form data mutation and constraint validation before save
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = $this->getRecord();
        
        // Clean up NIP - make it null if empty for Pegawai/Dokter
        if (isset($data['nip']) && empty(trim($data['nip']))) {
            $data['nip'] = null;
        }
        
        // For Pegawai and Dokter, NIP should be optional/nullable
        $roleId = $data['role_id'] ?? $user->role_id;
        if ($roleId) {
            $role = \App\Models\Role::find($roleId);
            if ($role && in_array($role->name, ['dokter', 'pegawai'])) {
                // For Dokter and Pegawai, NIP can be null
                if (empty($data['nip'])) {
                    $data['nip'] = null;
                }
            }
        }
        
        // NIP validation removed - NIP can be duplicated for multi-role users
        // Log if NIP is being reused (informational only)
        if (!empty($data['nip']) && $data['nip'] != $user->nip) {
            $existingUsers = \App\Models\User::where('nip', $data['nip'])
                ->where('id', '!=', $user->id)
                ->get();
                
            if ($existingUsers->count() > 0) {
                // Just log for information - multi-role is allowed
                \Log::info('EditUser: NIP shared with other users (multi-role)', [
                    'user_id' => $user->id,
                    'nip' => $data['nip'],
                    'other_users' => $existingUsers->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'role' => $user->role ? $user->role->name : null
                        ];
                    })->toArray()
                ]);
                
                // Show informational notification (not error)
                $usersList = $existingUsers->map(function ($user) {
                    return "{$user->name} (Role: " . ($user->role ? $user->role->display_name : 'No role') . ")";
                })->join(', ');
                
                \Filament\Notifications\Notification::make()
                    ->title('ℹ️ NIP Multi-Role')
                    ->body("NIP '{$data['nip']}' juga digunakan oleh: {$usersList}. Ini diperbolehkan untuk multi-role.")
                    ->info()
                    ->duration(5000)
                    ->send();
            }
        }
        
        // DEBUG: Log form data yang diterima untuk edit
        \Log::info('EditUser: Form data processed for edit', [
            'user_id' => $user->id,
            'nip_before' => $user->nip,
            'nip_after' => $data['nip'] ?? 'NULL',
            'role' => $role->name ?? 'unknown',
            'data_keys' => array_keys($data)
        ]);
        
        return $data;
    }
    
    /**
     * Handle save with proper error handling for constraint violations
     */
    protected function handleRecordUpdate($record, array $data): Model
    {
        try {
            return parent::handleRecordUpdate($record, $data);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                // Handle unique constraint violations with user-friendly messages
                $message = \App\Models\User::getConstraintViolationMessage($e);
                
                \Filament\Notifications\Notification::make()
                    ->title('❌ Gagal Menyimpan Perubahan')
                    ->body($message)
                    ->danger()
                    ->persistent()
                    ->send();
                    
                // Stop the save process
                $this->halt();
            }
            
            throw $e;
        }
    }
    
    /**
     * Log hasil setelah save
     */
    protected function getSavedNotification(): ?\Filament\Notifications\Notification
    {
        $user = $this->getRecord();
        
        // DEBUG: Log user setelah disimpan
        \Log::info('EditUser: User updated successfully', [
            'user_id' => $user->id,
            'username' => $user->username ?: 'EMPTY',
            'name' => $user->name,
            'email' => $user->email,
            'nip' => $user->nip ?: 'NULL',
        ]);
        
        return \Filament\Notifications\Notification::make()
            ->title('✅ User Berhasil Diperbarui')
            ->body('Data user telah berhasil disimpan dengan aman.')
            ->success();
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
