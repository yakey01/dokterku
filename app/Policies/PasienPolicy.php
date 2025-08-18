<?php

namespace App\Policies;

use App\Models\Pasien;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PasienPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Allow petugas role and other medical staff to view patients
        return $user->hasRole('petugas') || 
               $user->hasRole(['admin', 'manajer', 'dokter']) ||
               $user->can('view-patients');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Pasien $pasien): bool
    {
        // Allow petugas role and other medical staff to view patients
        return $user->hasRole('petugas') || 
               $user->hasRole(['admin', 'manajer', 'dokter']) ||
               $user->can('view-patients');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Allow petugas role and other medical staff to create patients
        return $user->hasRole('petugas') || 
               $user->hasRole(['admin', 'manajer', 'dokter']) ||
               $user->can('create-patients');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Pasien $pasien): bool
    {
        // Allow petugas role and other medical staff to update patients
        return $user->hasRole('petugas') || 
               $user->hasRole(['admin', 'manajer', 'dokter']) ||
               $user->can('edit-patients');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Pasien $pasien): bool
    {
        // Allow petugas role and other medical staff to delete patients
        return $user->hasRole('petugas') || 
               $user->hasRole(['admin', 'manajer']) ||
               $user->can('delete-patients');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Pasien $pasien): bool
    {
        return $user->can('delete_pasien');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Pasien $pasien): bool
    {
        return $user->hasRole('admin');
    }
}