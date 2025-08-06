<?php

namespace App\Modules\User\DTOs;

use App\Core\Traits\HasDTO;

/**
 * Data Transfer Object for updating a user
 * Following Single Responsibility Principle
 */
class UpdateUserDTO
{
    use HasDTO;

    public ?string $name = null;
    public ?string $email = null;
    public ?string $username = null;
    public ?string $phone = null;
    public ?string $address = null;
    public ?string $bio = null;
    public ?string $dateOfBirth = null;
    public ?string $gender = null;
    public ?int $roleId = null;
    public ?int $workLocationId = null;
    public ?string $nip = null;
    public ?bool $isActive = null;
    public ?string $language = null;
    public ?string $timezone = null;
    public ?string $theme = null;

    /**
     * Validate DTO data
     */
    public function validate(): array
    {
        $errors = [];

        if (isset($this->email) && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (isset($this->gender) && !in_array($this->gender, ['male', 'female', 'other'])) {
            $errors['gender'] = 'Invalid gender value';
        }

        if (isset($this->language) && !in_array($this->language, ['en', 'id'])) {
            $errors['language'] = 'Invalid language';
        }

        if (isset($this->theme) && !in_array($this->theme, ['light', 'dark', 'auto'])) {
            $errors['theme'] = 'Invalid theme';
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
     * Convert to model attributes (only non-null values)
     */
    public function toModelAttributes(): array
    {
        $attributes = [];

        $fields = [
            'name' => 'name',
            'email' => 'email',
            'username' => 'username',
            'phone' => 'phone',
            'address' => 'address',
            'bio' => 'bio',
            'dateOfBirth' => 'date_of_birth',
            'gender' => 'gender',
            'roleId' => 'role_id',
            'workLocationId' => 'work_location_id',
            'nip' => 'nip',
            'isActive' => 'is_active',
            'language' => 'language',
            'timezone' => 'timezone',
            'theme' => 'theme',
        ];

        foreach ($fields as $dtoField => $modelField) {
            if ($this->$dtoField !== null) {
                $attributes[$modelField] = $this->$dtoField;
            }
        }

        return $attributes;
    }

    /**
     * Check if DTO has any updates
     */
    public function hasUpdates(): bool
    {
        return !empty($this->toModelAttributes());
    }
}