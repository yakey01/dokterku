<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class SessionManagementService
{
    /**
     * Handle unified logout for all user types
     * Ensures proper session cleanup and CSRF token regeneration
     *
     * @param Request $request
     * @param string|null $userType
     * @return void
     */
    public function logout(Request $request, ?string $userType = null): void
    {
        $user = Auth::user();
        
        // Log the logout event
        if ($user) {
            Log::info('Unified logout initiated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role?->name ?? $userType,
                'user_type' => $userType,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => $request->session()->getId()
            ]);
            
            // Clear remember token if exists
            if ($user->remember_token) {
                $user->remember_token = null;
                $user->save();
            }
            
            // Clear sessions from database for this user
            $this->clearUserSessions($user->id);
        }
        
        // Logout from all guards
        $guards = ['web', 'sanctum'];
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::guard($guard)->logout();
            }
        }
        
        // Invalidate the session
        $request->session()->invalidate();
        
        // Regenerate CSRF token (only on logout)
        $request->session()->regenerateToken();
        
        // Clear all session data
        $request->session()->flush();
        
        // Clear any cookies
        $this->clearAuthCookies($request);
        
        Log::info('Unified logout completed', [
            'user_type' => $userType,
            'ip' => $request->ip(),
            'session_cleared' => true,
            'csrf_regenerated' => true
        ]);
    }
    
    /**
     * Clear all sessions for a specific user from database
     *
     * @param int $userId
     * @return void
     */
    protected function clearUserSessions(int $userId): void
    {
        try {
            DB::table('sessions')
                ->where('user_id', $userId)
                ->delete();
                
            Log::info('User sessions cleared from database', [
                'user_id' => $userId
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear user sessions from database', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Clear authentication cookies
     *
     * @param Request $request
     * @return void
     */
    protected function clearAuthCookies(Request $request): void
    {
        // Clear remember me cookie if exists
        if ($request->hasCookie('remember_web')) {
            setcookie('remember_web', '', time() - 3600, '/');
        }
        
        // Clear any Filament auth cookies for different panels
        $panels = ['admin', 'bendahara', 'manajer', 'dokter', 'paramedis', 'petugas', 'verifikator'];
        foreach ($panels as $panel) {
            $cookieName = "filament_{$panel}_auth";
            if ($request->hasCookie($cookieName)) {
                setcookie($cookieName, '', time() - 3600, '/');
            }
        }
    }
    
    /**
     * Check if user session is still valid
     * Used to verify persistent login status
     *
     * @param Request $request
     * @return bool
     */
    public function isSessionValid(Request $request): bool
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return false;
        }
        
        $user = Auth::user();
        $sessionId = $request->session()->getId();
        
        // Check if session exists in database
        $sessionExists = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->exists();
            
        if (!$sessionExists) {
            Log::warning('Session validation failed - session not in database', [
                'user_id' => $user->id,
                'session_id' => $sessionId
            ]);
            return false;
        }
        
        // Check if session has not expired (based on last_activity)
        $sessionLifetime = config('session.lifetime') * 60; // Convert to seconds
        $session = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->first();
            
        if ($session) {
            $lastActivity = $session->last_activity;
            $currentTime = time();
            
            if (($currentTime - $lastActivity) > $sessionLifetime) {
                Log::info('Session validation failed - session expired', [
                    'user_id' => $user->id,
                    'session_id' => $sessionId,
                    'last_activity' => $lastActivity,
                    'current_time' => $currentTime,
                    'lifetime' => $sessionLifetime
                ]);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Extend session lifetime for active users
     * Called on user activity to keep session alive
     *
     * @param Request $request
     * @return void
     */
    public function extendSession(Request $request): void
    {
        if (Auth::check()) {
            $sessionId = $request->session()->getId();
            $userId = Auth::id();
            
            // Update last_activity in sessions table
            DB::table('sessions')
                ->where('id', $sessionId)
                ->where('user_id', $userId)
                ->update([
                    'last_activity' => time()
                ]);
                
            Log::debug('Session extended', [
                'user_id' => $userId,
                'session_id' => $sessionId
            ]);
        }
    }
}