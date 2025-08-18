<?php

namespace App\Policies;

use App\Models\Tindakan;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TindakanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Allow petugas role and users with view permissions to view procedures
        return $user->hasRole('petugas') || 
               $user->hasRole(['admin', 'manajer', 'dokter']) ||
               $user->can('view-procedures') || 
               $user->can('view_tindakan');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tindakan $tindakan): bool
    {
        // Allow petugas role and users with view permissions to view procedures  
        return $user->hasRole('petugas') || 
               $user->hasRole(['admin', 'manajer', 'dokter']) ||
               $user->can('view-procedures') || 
               $user->can('view_tindakan');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Allow petugas role and users with create permissions to create procedures
        return $user->hasRole('petugas') || 
               $user->hasRole(['admin', 'manajer', 'dokter']) ||
               $user->can('create-procedures') || 
               $user->can('create_tindakan');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tindakan $tindakan): bool
    {
        // Allow bendahara to validate transactions, allow others if they have permission AND are involved
        if ($user->hasPermissionTo('validate_transactions')) {
            return true; // Bendahara can update validation status
        }
        
        // User can edit if they have permission AND (they created it OR they're involved in it)
        return ($user->hasPermissionTo('input_transactions') || $user->hasPermissionTo('update_tindakan')) && (
            $tindakan->input_by === $user->id ||
            $tindakan->dokter_id === $user->id ||
            $tindakan->paramedis_id === $user->id ||
            $tindakan->non_paramedis_id === $user->id ||
            $user->role && in_array($user->role->name, ['admin', 'manajer'])
        );
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tindakan $tindakan): bool
    {
        return ($user->can('input_transactions') || $user->can('delete_tindakan')) && (
            $tindakan->input_by === $user->id ||
            $user->role && in_array($user->role->name, ['admin', 'manajer'])
        );
    }

    /**
     * Determine whether the user can perform procedures.
     */
    public function perform(User $user): bool
    {
        return $user->can('create_tindakan');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Tindakan $tindakan): bool
    {
        return $user->can('delete_tindakan');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Tindakan $tindakan): bool
    {
        return $user->role && $user->role->name === 'admin';
    }
}