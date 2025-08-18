<?php

namespace App\Policies;

use App\Models\JumlahPasienHarian;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class JumlahPasienHarianPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Allow petugas role and medical staff 
        return $user->hasRole('petugas') || 
               $user->hasRole(['admin', 'manajer', 'dokter']) ||
               $user->hasPermissionTo('view-patients') || 
               $user->hasPermissionTo('create-patients') ||
               $user->hasPermissionTo('validate_transactions');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, JumlahPasienHarian $jumlahPasienHarian): bool
    {
        // Allow if user has permission and either created the record or has validation rights
        return ($user->hasPermissionTo('view-patients') || 
                $user->hasPermissionTo('create-patients') ||
                $user->hasPermissionTo('validate_transactions')) &&
               ($jumlahPasienHarian->input_by === $user->id || 
                $user->hasPermissionTo('validate_transactions'));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-patients');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, JumlahPasienHarian $jumlahPasienHarian): bool
    {
        // Allow if user has permission and either created the record or has validation rights
        return ($user->hasPermissionTo('edit_jumlah_pasien_harian') || 
                $user->hasPermissionTo('input_transactions') ||
                $user->hasPermissionTo('validate_transactions')) &&
               ($jumlahPasienHarian->input_by === $user->id || 
                $user->hasPermissionTo('validate_transactions'));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, JumlahPasienHarian $jumlahPasienHarian): bool
    {
        // Only allow deletion if user created the record or has admin rights
        return ($user->hasPermissionTo('delete_jumlah_pasien_harian') ||
                $user->hasPermissionTo('input_transactions')) &&
               ($jumlahPasienHarian->input_by === $user->id || 
                $user->hasRole('admin'));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, JumlahPasienHarian $jumlahPasienHarian): bool
    {
        return $user->hasPermissionTo('restore_jumlah_pasien_harian') ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, JumlahPasienHarian $jumlahPasienHarian): bool
    {
        return $user->hasPermissionTo('force_delete_jumlah_pasien_harian') ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can bulk delete models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete_any_jumlah_pasien_harian') ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can perform bulk actions.
     */
    public function restoreAny(User $user): bool
    {
        return $user->hasPermissionTo('restore_any_jumlah_pasien_harian') ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently bulk delete models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->hasPermissionTo('force_delete_any_jumlah_pasien_harian') ||
               $user->hasRole('admin');
    }
}