<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'System Administration';
    
    protected static ?string $navigationLabel = 'Audit & Security Logs';
    
    protected static ?string $modelLabel = 'Audit Log';
    
    protected static ?string $pluralModelLabel = 'Audit Logs';
    
    protected static ?int $navigationSort = 1;
    
    public static function getNavigationBadge(): ?string
    {
        // Show count of critical security events in last 24 hours
        $criticalCount = static::getModel()::whereIn('action', [
            'login_failed',
            'login_rate_limited', 
            'suspicious_activity',
            'account_locked',
            'security_event'
        ])
        ->where('created_at', '>=', now()->subDay())
        ->count();
        
        return $criticalCount > 0 ? (string) $criticalCount : null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        $criticalCount = static::getModel()::whereIn('action', [
            'login_failed',
            'login_rate_limited',
            'suspicious_activity', 
            'account_locked',
            'security_event'
        ])
        ->where('created_at', '>=', now()->subDay())
        ->count();
        
        if ($criticalCount > 10) return 'danger';
        if ($criticalCount > 5) return 'warning';
        if ($criticalCount > 0) return 'info';
        
        return null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('user_name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->placeholder('System'),

                TextColumn::make('action')
                    ->label('Aksi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        // Security actions (from SecurityLogResource)
                        'login_success' => 'success',
                        'login_failed' => 'danger',
                        'login_rate_limited' => 'warning',
                        'login_inactive_user' => 'danger',
                        'logout' => 'gray',
                        'password_changed' => 'info',
                        'password_reset' => 'warning',
                        'two_factor_enabled' => 'success',
                        'two_factor_disabled' => 'warning',
                        'two_factor_verified' => 'success',
                        'two_factor_failed' => 'danger',
                        'session_terminated' => 'warning',
                        'account_locked' => 'danger',
                        'account_unlocked' => 'success',
                        'security_event' => 'danger',
                        'suspicious_activity' => 'danger',
                        // Standard actions
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'login' => 'info',
                        'exported' => 'primary',
                        'imported' => 'primary',
                        'bulk_update' => 'warning',
                        'bulk_delete' => 'danger',
                        'validation_approved' => 'success',
                        'validation_rejected' => 'danger',
                        'validation_submitted' => 'info',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('model_type')
                    ->label('Model')
                    ->formatStateUsing(fn (?string $state): string => 
                        $state ? class_basename($state) : '-'
                    )
                    ->searchable()
                    ->sortable(),

                TextColumn::make('model_id')
                    ->label('ID Model')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->searchable(),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('user_role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'admin' => 'danger',
                        'manager' => 'warning',
                        'supervisor' => 'primary',
                        'petugas' => 'info',
                        'paramedis' => 'success',
                        'dokter' => 'success',
                        default => 'gray',
                    })
                    ->searchable()
                    ->placeholder('-'),
            ])
            ->filters([
                // Security preset filter
                Filter::make('security_events')
                    ->label('Security Events Only')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereIn('action', [
                            'login_success',
                            'login_failed',
                            'login_rate_limited',
                            'login_inactive_user',
                            'logout',
                            'password_changed',
                            'password_reset',
                            'two_factor_enabled',
                            'two_factor_disabled',
                            'two_factor_verified',
                            'two_factor_failed',
                            'session_terminated',
                            'account_locked',
                            'account_unlocked',
                            'security_event',
                            'suspicious_activity',
                        ])
                    )
                    ->toggle(),
                
                SelectFilter::make('action')
                    ->label('Aksi')
                    ->options([
                        // Security actions
                        'login_success' => 'Login Success',
                        'login_failed' => 'Login Failed',
                        'login_rate_limited' => 'Login Rate Limited',
                        'login_inactive_user' => 'Login Inactive User',
                        'logout' => 'Logout',
                        'password_changed' => 'Password Changed',
                        'password_reset' => 'Password Reset',
                        'two_factor_enabled' => '2FA Enabled',
                        'two_factor_disabled' => '2FA Disabled',
                        'security_event' => 'Security Event',
                        'suspicious_activity' => 'Suspicious Activity',
                        'account_locked' => 'Account Locked',
                        'account_unlocked' => 'Account Unlocked',
                        // Standard actions
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'exported' => 'Exported',
                        'imported' => 'Imported',
                        'bulk_update' => 'Bulk Update',
                        'bulk_delete' => 'Bulk Delete',
                        'validation_approved' => 'Validation Approved',
                        'validation_rejected' => 'Validation Rejected',
                        'validation_submitted' => 'Validation Submitted',
                    ])
                    ->multiple(),

                SelectFilter::make('model_type')
                    ->label('Model')
                    ->options([
                        'App\Models\Pasien' => 'Pasien',
                        'App\Models\Dokter' => 'Dokter',
                        'App\Models\Tindakan' => 'Tindakan',
                        'App\Models\Pendapatan' => 'Pendapatan',
                        'App\Models\Pengeluaran' => 'Pengeluaran',
                        'App\Models\PendapatanHarian' => 'Pendapatan Harian',
                        'App\Models\PengeluaranHarian' => 'Pengeluaran Harian',
                        'App\Models\JumlahPasienHarian' => 'Jumlah Pasien Harian',
                        'App\Models\User' => 'User',
                    ])
                    ->multiple(),

                SelectFilter::make('user_role')
                    ->label('Role User')
                    ->options([
                        'admin' => 'Admin',
                        'manager' => 'Manager',
                        'supervisor' => 'Supervisor',
                        'petugas' => 'Petugas',
                        'paramedis' => 'Paramedis',
                        'dokter' => 'Dokter',
                    ])
                    ->multiple(),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info'),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}