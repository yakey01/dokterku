<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'livewire/update',
        'livewire/upload-file',
        'livewire/message/*',
        'api/v2/dashboards/dokter/*',
        'api/v2/dashboards/dokter/checkin',
        'api/v2/dashboards/dokter/checkout',
        // Removed login and unified-login to enforce CSRF protection
    ];
    
}