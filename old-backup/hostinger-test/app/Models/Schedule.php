<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shift_id',
        'date',
        'is_day_off',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'is_day_off' => 'boolean',
    ];

    /**
     * Get the user that owns the schedule
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shift for the schedule
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}