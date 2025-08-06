<?php

namespace App\Modules\User\DTOs;

use App\Core\Traits\HasDTO;

/**
 * Data Transfer Object for creating a user
 * Following Single Responsibility Principle
 */
class CreateUserDTO
{
    use HasDTO;

    public string $name;
    public string $email;
    public ?string $username;
    public string $password;
    public ?string $phone;
    public ?string $address;
    public ?string $role;
    public ?int $roleId;
    public ?int $workLocationId;
    public ?int $pegawaiId;
    public ?string $nip;
    public ?string $tanggalBergabung;
    public bool $isActive = true;

    /**
     * Validate DTO data
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->name)) {
            $errors['name'] = 'Name is required';
        }

        if (empty($this->email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (empty($this->password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($this->password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }

        return $errors;
    }

    /**
     * Check if DTO is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Convert to model attributes
     */
    public function toModelAttributes(): array
    {
        $attributes = [
            'name' => $this->name,
            'email' => $this->email,
            'password' => bcrypt($this->password),
            'is_active' => $this->isActive,
        ];

        if (isset($this->username)) {
            $attributes['username'] = $this->username;
        }

        if (isset($this->phone)) {
            $attributes['phone'] = $this->phone;
        }

        if (isset($this->address)) {
            $attributes['address'] = $this->address;
        }

        if (isset($this->roleId)) {
            $attributes['role_id'] = $this->roleId;
        }

        if (isset($this->workLocationId)) {
            $attributes['work_location_id'] = $this->workLocationId;
        }

        if (isset($this->pegawaiId)) {
            $attributes['pegawai_id'] = $this->pegawaiId;
        }

        if (isset($this->nip)) {
            $attributes['nip'] = $this->nip;
        }

        if (isset($this->tanggalBergabung)) {
            $attributes['tanggal_bergabung'] = $this->tanggalBergabung;
        }

        return $attributes;
    }
}