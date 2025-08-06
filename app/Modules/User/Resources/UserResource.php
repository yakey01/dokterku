<?php

namespace App\Modules\User\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User Resource
 * Transforms User model for API responses
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'phone' => $this->phone,
            'address' => $this->address,
            'bio' => $this->bio,
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'nip' => $this->nip,
            'tanggal_bergabung' => $this->tanggal_bergabung,
            'is_active' => $this->is_active,
            'last_login_at' => $this->last_login_at,
            'role' => $this->when($this->relationLoaded('role'), function () {
                return [
                    'id' => $this->role->id,
                    'name' => $this->role->name,
                ];
            }),
            'pegawai' => $this->when($this->relationLoaded('pegawai'), function () {
                return [
                    'id' => $this->pegawai->id,
                    'nama' => $this->pegawai->nama,
                    'nik' => $this->pegawai->nik,
                ];
            }),
            'work_location' => $this->when($this->relationLoaded('workLocation'), function () {
                return [
                    'id' => $this->workLocation->id,
                    'name' => $this->workLocation->name,
                    'address' => $this->workLocation->address,
                ];
            }),
            'permissions' => $this->when($this->relationLoaded('permissions'), function () {
                return $this->permissions->pluck('name');
            }),
            'settings' => [
                'language' => $this->language ?? 'en',
                'timezone' => $this->timezone ?? 'Asia/Jakarta',
                'theme' => $this->theme ?? 'light',
                'email_notifications' => $this->email_notifications ?? true,
                'push_notifications' => $this->push_notifications ?? true,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}