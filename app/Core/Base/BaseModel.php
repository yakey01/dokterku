<?php

namespace App\Core\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use App\Traits\Cacheable;

/**
 * Base Model implementing common model features
 * Following DRY principle
 */
abstract class BaseModel extends Model
{
    use SoftDeletes, Auditable, Cacheable;

    /**
     * The attributes that should be cast to dates
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be hidden for arrays
     */
    protected $hidden = ['deleted_at'];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-set created_by and updated_by
        static::creating(function ($model) {
            if (auth()->check() && $model->hasAttribute('created_by')) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check() && $model->hasAttribute('updated_by')) {
                $model->updated_by = auth()->id();
            }
        });
    }

    /**
     * Check if model has attribute
     */
    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes) || 
               array_key_exists($key, $this->casts) ||
               $this->hasGetMutator($key) ||
               $this->hasAttributeMutator($key) ||
               method_exists($this, $key);
    }

    /**
     * Scope for active records
     */
    public function scopeActive($query)
    {
        if ($this->hasAttribute('is_active')) {
            return $query->where('is_active', true);
        }
        return $query;
    }

    /**
     * Scope for ordering by latest
     */
    public function scopeLatest($query, $column = 'created_at')
    {
        return $query->orderBy($column, 'desc');
    }

    /**
     * Scope for ordering by oldest
     */
    public function scopeOldest($query, $column = 'created_at')
    {
        return $query->orderBy($column, 'asc');
    }

    /**
     * Get model's display name
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->hasAttribute('name')) {
            return $this->name;
        }
        
        if ($this->hasAttribute('title')) {
            return $this->title;
        }
        
        return class_basename($this) . ' #' . $this->getKey();
    }

    /**
     * Convert model to searchable array
     */
    public function toSearchableArray(): array
    {
        return $this->toArray();
    }
}