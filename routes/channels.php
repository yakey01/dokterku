<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// 🎯 ROLE-SPECIFIC PRIVATE CHANNELS
// Each user role gets their own private channel for targeted notifications

// Dokter private channels
Broadcast::channel('dokter.{userId}', function (User $user, int $userId) {
    return (int) $user->id === (int) $userId && $user->hasRole('dokter');
});

// Paramedis private channels  
Broadcast::channel('paramedis.{userId}', function (User $user, int $userId) {
    return (int) $user->id === (int) $userId && $user->hasRole('paramedis');
});

// Petugas private channels
Broadcast::channel('petugas.{userId}', function (User $user, int $userId) {
    return (int) $user->id === (int) $userId && $user->hasRole('petugas');
});

// Bendahara private channels
Broadcast::channel('bendahara.{userId}', function (User $user, int $userId) {
    return (int) $user->id === (int) $userId && $user->hasRole('bendahara');
});

// Manajer private channels
Broadcast::channel('manajer.{userId}', function (User $user, int $userId) {
    return (int) $user->id === (int) $userId && $user->hasRole('manajer');
});

// Admin private channels
Broadcast::channel('admin.{userId}', function (User $user, int $userId) {
    return (int) $user->id === (int) $userId && $user->hasRole('admin');
});

// General user private channel (fallback)
Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return (int) $user->id === (int) $userId;
});

// 🌐 PUBLIC CHANNELS FOR SYSTEM-WIDE UPDATES

// Public channel for validation updates (all roles can listen)
Broadcast::channel('validation.updates', function () {
    return true; // Public - anyone can listen to validation updates
});

// Public channel for medical procedures (dokter, paramedis, petugas)
Broadcast::channel('medical.procedures', function () {
    return true; // Public - medical staff can listen
});

// Public channel for financial updates (bendahara, manajer, admin)
Broadcast::channel('financial.updates', function () {
    return true; // Public - financial stakeholders can listen
});

// 🏥 ROLE-SPECIFIC PUBLIC CHANNELS

// Channel for all medical staff
Broadcast::channel('medical.staff', function (User $user) {
    return $user->hasRole(['dokter', 'paramedis', 'petugas']);
});

// Channel for all administrative staff
Broadcast::channel('admin.staff', function (User $user) {
    return $user->hasRole(['admin', 'bendahara', 'manajer']);
});

// Channel for all managers and supervisors
Broadcast::channel('management.oversight', function (User $user) {
    return $user->hasRole(['admin', 'manajer']);
});

// 👥 PRESENCE CHANNELS FOR COLLABORATION

// Presence channel for active medical staff
Broadcast::channel('presence.medical', function (User $user) {
    if ($user->hasRole(['dokter', 'paramedis', 'petugas'])) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->roles->first()->name ?? 'medical',
            'panel' => $user->getCurrentPanel(), // If available
            'status' => 'online',
            'last_activity' => now()->toISOString(),
        ];
    }
    return false;
});

// Presence channel for active administrators
Broadcast::channel('presence.admin', function (User $user) {
    if ($user->hasRole(['admin', 'bendahara', 'manajer'])) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->roles->first()->name ?? 'admin',
            'panel' => $user->getCurrentPanel(), // If available
            'status' => 'online',
            'last_activity' => now()->toISOString(),
        ];
    }
    return false;
});

// 🚨 EMERGENCY CHANNELS

// Emergency broadcast channel (system-wide critical alerts)
Broadcast::channel('emergency.alerts', function () {
    return true; // Public - everyone should receive emergency alerts
});

// System maintenance channel (for maintenance notifications)
Broadcast::channel('system.maintenance', function () {
    return true; // Public - all users need maintenance updates
});

// 🏢 MANAGER-SPECIFIC CHANNELS FOR REAL-TIME UPDATES

// Manager KPI updates (real-time dashboard data)
Broadcast::channel('manajer.kpi-updates', function (User $user) {
    return $user->hasRole('manajer') ? [
        'id' => $user->id,
        'name' => $user->name,
        'role' => 'manajer',
        'dashboard_access' => true,
    ] : false;
});

// Critical alerts for management (urgent approvals, overdue goals)
Broadcast::channel('manajer.critical-alerts', function (User $user) {
    return $user->hasRole('manajer') ? [
        'id' => $user->id,
        'name' => $user->name,
        'alert_level' => 'executive',
    ] : false;
});

// Department performance updates
Broadcast::channel('manajer.performance-updates', function (User $user) {
    return $user->hasRole('manajer');
});

// Strategic goal progress updates
Broadcast::channel('manajer.strategic-updates', function (User $user) {
    return $user->hasRole('manajer');
});

// High-value approval notifications
Broadcast::channel('manajer.approval-alerts', function (User $user) {
    return $user->hasRole('manajer');
});

// Executive dashboard real-time data
Broadcast::channel('executive.dashboard', function (User $user) {
    return $user->hasRole(['manajer', 'admin']) ? [
        'id' => $user->id,
        'name' => $user->name,
        'role' => $user->roles->first()->name,
        'access_level' => 'executive',
        'last_activity' => now()->toISOString(),
    ] : false;
});