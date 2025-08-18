<?php

namespace App\DTOs\Admin;

use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * User Management Data Transfer Object
 * 
 * Handles user data validation, transformation, and type safety
 * for admin user management operations.
 */
class UserManagementDTO
{
    public ?int $id;
    public string $name;
    public string $email;
    public ?string $password;
    public int $roleId;
    public ?string $nip;
    public ?string $phone;
    public ?string $address;
    public bool $isActive;
    public array $permissions;
    public array $metadata;

    public function __construct(
        ?int $id = null,
        string $name = '',
        string $email = '',
        ?string $password = null,
        int $roleId = 0,
        ?string $nip = null,
        ?string $phone = null,
        ?string $address = null,
        bool $isActive = true,
        array $permissions = [],
        array $metadata = []
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->roleId = $roleId;
        $this->nip = $nip;
        $this->phone = $phone;
        $this->address = $address;
        $this->isActive = $isActive;
        $this->permissions = $permissions;
        $this->metadata = $metadata;
    }

    /**
     * Create DTO from request data
     *
     * @param array $data
     * @return self
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: trim($data['name'] ?? ''),
            email: strtolower(trim($data['email'] ?? '')),
            password: $data['password'] ?? null,
            roleId: (int) ($data['role_id'] ?? 0),
            nip: $data['nip'] ?? null,
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
            isActive: (bool) ($data['is_active'] ?? true),
            permissions: $data['permissions'] ?? [],
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Create DTO from User model
     *
     * @param User $user
     * @return self
     */
    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            password: null, // Never expose password
            roleId: $user->role_id,
            nip: $user->nip,
            phone: $user->phone,
            address: $user->address,
            isActive: $user->email_verified_at !== null,
            permissions: $user->getAllPermissions()->pluck('name')->toArray(),
            metadata: $user->metadata ?? []
        );
    }

    /**
     * Convert to array for database operations
     *
     * @param bool $includePassword
     * @return array
     */
    public function toArray(bool $includePassword = false): array
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => $this->roleId,
            'nip' => $this->nip,
            'phone' => $this->phone,
            'address' => $this->address,
            'email_verified_at' => $this->isActive ? now() : null,
            'metadata' => $this->metadata
        ];

        if ($includePassword && $this->password !== null) {
            $data['password'] = $this->password;
        }

        // Remove null values
        return array_filter($data, fn($value) => $value !== null);
    }

    /**
     * Convert to array for API responses
     *
     * @return array
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => $this->roleId,
            'nip' => $this->nip,
            'phone' => $this->phone,
            'address' => $this->address,
            'is_active' => $this->isActive,
            'permissions' => $this->permissions,
            'metadata' => $this->metadata
        ];
    }

    /**
     * Validate user data
     *
     * @param bool $isUpdate
     * @return array
     * @throws ValidationException
     */
    public function validate(bool $isUpdate = false): array
    {
        $errors = [];

        // Name validation
        if (empty($this->name)) {
            $errors['name'] = 'Name is required';
        } elseif (strlen($this->name) < 2) {
            $errors['name'] = 'Name must be at least 2 characters';
        } elseif (strlen($this->name) > 255) {
            $errors['name'] = 'Name cannot exceed 255 characters';
        }

        // Email validation
        if (empty($this->email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        // Password validation (required for new users)
        if (!$isUpdate && empty($this->password)) {
            $errors['password'] = 'Password is required for new users';
        } elseif ($this->password !== null && strlen($this->password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }

        // Role validation
        if ($this->roleId <= 0) {
            $errors['role_id'] = 'Valid role is required';
        }

        // NIP validation (if provided)
        if ($this->nip !== null) {
            if (strlen($this->nip) < 3) {
                $errors['nip'] = 'NIP must be at least 3 characters';
            } elseif (strlen($this->nip) > 20) {
                $errors['nip'] = 'NIP cannot exceed 20 characters';
            }
        }

        // Phone validation (if provided)
        if ($this->phone !== null && !empty($this->phone)) {
            if (!preg_match('/^[\+]?[0-9\-\(\)\s]+$/', $this->phone)) {
                $errors['phone'] = 'Invalid phone number format';
            }
        }

        return $errors;
    }

    /**
     * Check if DTO represents a valid user
     *
     * @param bool $isUpdate
     * @return bool
     */
    public function isValid(bool $isUpdate = false): bool
    {
        return empty($this->validate($isUpdate));
    }

    /**
     * Get sanitized data for logging
     *
     * @return array
     */
    public function toLogArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => $this->roleId,
            'nip' => $this->nip,
            'is_active' => $this->isActive,
            'has_password' => $this->password !== null
        ];
    }

    /**
     * Create DTO for user creation
     *
     * @param array $data
     * @return self
     */
    public static function forCreation(array $data): self
    {
        $dto = self::fromRequest($data);
        
        // Generate NIP if not provided
        if (empty($dto->nip)) {
            $nip = 'USR' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            return new self(
                id: $dto->id,
                name: $dto->name,
                email: $dto->email,
                password: $dto->password,
                roleId: $dto->roleId,
                nip: $nip,
                phone: $dto->phone,
                address: $dto->address,
                isActive: $dto->isActive,
                permissions: $dto->permissions,
                metadata: $dto->metadata
            );
        }

        return $dto;
    }

    /**
     * Create DTO for user update (excludes sensitive fields)
     *
     * @param array $data
     * @param User $existingUser
     * @return self
     */
    public static function forUpdate(array $data, User $existingUser): self
    {
        return new self(
            id: $existingUser->id,
            name: trim($data['name'] ?? $existingUser->name),
            email: strtolower(trim($data['email'] ?? $existingUser->email)),
            password: $data['password'] ?? null, // Only include if changing
            roleId: (int) ($data['role_id'] ?? $existingUser->role_id),
            nip: $data['nip'] ?? $existingUser->nip,
            phone: $data['phone'] ?? $existingUser->phone,
            address: $data['address'] ?? $existingUser->address,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : ($existingUser->email_verified_at !== null),
            permissions: $data['permissions'] ?? [],
            metadata: array_merge($existingUser->metadata ?? [], $data['metadata'] ?? [])
        );
    }

    /**
     * Get changes between this DTO and an existing user
     *
     * @param User $existingUser
     * @return array
     */
    public function getChanges(User $existingUser): array
    {
        $changes = [];
        $currentData = self::fromModel($existingUser);

        if ($this->name !== $currentData->name) {
            $changes['name'] = ['from' => $currentData->name, 'to' => $this->name];
        }

        if ($this->email !== $currentData->email) {
            $changes['email'] = ['from' => $currentData->email, 'to' => $this->email];
        }

        if ($this->roleId !== $currentData->roleId) {
            $changes['role_id'] = ['from' => $currentData->roleId, 'to' => $this->roleId];
        }

        if ($this->nip !== $currentData->nip) {
            $changes['nip'] = ['from' => $currentData->nip, 'to' => $this->nip];
        }

        if ($this->phone !== $currentData->phone) {
            $changes['phone'] = ['from' => $currentData->phone, 'to' => $this->phone];
        }

        if ($this->address !== $currentData->address) {
            $changes['address'] = ['from' => $currentData->address, 'to' => $this->address];
        }

        if ($this->isActive !== $currentData->isActive) {
            $changes['is_active'] = ['from' => $currentData->isActive, 'to' => $this->isActive];
        }

        if ($this->password !== null) {
            $changes['password'] = ['from' => 'encrypted', 'to' => 'updated'];
        }

        return $changes;
    }

    /**
     * Check if this is a new user (no ID)
     *
     * @return bool
     */
    public function isNewUser(): bool
    {
        return $this->id === null;
    }

    /**
     * Check if password is being changed
     *
     * @return bool
     */
    public function hasPasswordChange(): bool
    {
        return $this->password !== null && !empty($this->password);
    }
}