<?php

namespace App\Core\Traits;

/**
 * Trait for Data Transfer Object support
 */
trait HasDTO
{
    /**
     * Create instance from array
     */
    public static function fromArray(array $data): static
    {
        $instance = new static();
        
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                $instance->$key = $value;
            }
        }
        
        return $instance;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Create from JSON
     */
    public static function fromJson(string $json): static
    {
        return static::fromArray(json_decode($json, true));
    }
}