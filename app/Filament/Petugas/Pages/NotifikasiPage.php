<?php

namespace App\Filament\Petugas\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Notifications\DatabaseNotification;

class NotifikasiPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationLabel = 'Notifikasi';
    protected static ?string $title = 'Pusat Notifikasi';
    protected static ?string $slug = 'notifikasi';
    protected static ?int $navigationSort = 14;
    protected static string $view = 'filament.petugas.pages.notifikasi';

    public function table(Table $table): Table
    {
        return $table
            ->query(DatabaseNotification::query()->where('notifiable_id', auth()->id()))
            ->columns([
                TextColumn::make('type')
                    ->label('Tipe Notifikasi')
                    ->formatStateUsing(fn ($state) => $this->formatNotificationType($state))
                    ->searchable(),
                TextColumn::make('data')
                    ->label('Pesan')
                    ->formatStateUsing(fn ($state) => $this->extractMessage($state))
                    ->limit(100)
                    ->searchable(),
                BadgeColumn::make('read_at')
                    ->label('Status')
                    ->colors([
                        'danger' => fn ($state) => $state === null,
                        'success' => fn ($state) => $state !== null,
                    ])
                    ->formatStateUsing(fn ($state) => $state === null ? 'Belum Dibaca' : 'Sudah Dibaca'),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('read_status')
                    ->label('Status Baca')
                    ->options([
                        'unread' => 'Belum Dibaca',
                        'read' => 'Sudah Dibaca',
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['value'] === 'unread',
                                fn ($query) => $query->whereNull('read_at')
                            )
                            ->when(
                                $data['value'] === 'read',
                                fn ($query) => $query->whereNotNull('read_at')
                            );
                    }),
                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai'),
                        DatePicker::make('tanggal_akhir')
                            ->label('Tanggal Akhir'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['tanggal_mulai'],
                                fn ($query, $date) => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['tanggal_akhir'],
                                fn ($query, $date) => $query->whereDate('created_at', '<=', $date)
                            );
                    })
            ])
            ->actions([
                ViewAction::make()
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->action(function ($record) {
                        $this->markAsRead($record);
                        $this->showNotificationDetail($record);
                    }),
                Action::make('mark_read')
                    ->label('Tandai Dibaca')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->read_at === null)
                    ->action(function ($record) {
                        $this->markAsRead($record);
                    }),
                Action::make('delete')
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $this->deleteNotification($record);
                    }),
            ])
            ->bulkActions([
                Action::make('mark_all_read')
                    ->label('Tandai Semua Dibaca')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function ($records) {
                        $this->markAllAsRead($records);
                    }),
                Action::make('delete_selected')
                    ->label('Hapus Terpilih')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $this->deleteSelectedNotifications($records);
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100]);
    }

    private function formatNotificationType(string $type): string
    {
        $types = [
            'App\Notifications\AdminPasswordReset' => 'Reset Password',
            'App\Notifications\GpsSpoofingAlert' => 'GPS Alert',
            'App\Notifications\PerformanceAlertNotification' => 'Performance Alert',
            'Illuminate\Auth\Notifications\ResetPassword' => 'Reset Password',
            'Illuminate\Auth\Notifications\VerifyEmail' => 'Verifikasi Email',
        ];

        return $types[$type] ?? class_basename($type);
    }

    private function extractMessage($data): string
    {
        if (is_array($data)) {
            if (isset($data['message'])) {
                return $data['message'];
            }
            if (isset($data['title'])) {
                return $data['title'];
            }
            return json_encode($data);
        }

        return (string) $data;
    }

    private function markAsRead($record): void
    {
        $record->markAsRead();
        
        \Filament\Notifications\Notification::make()
            ->title('Notifikasi ditandai dibaca')
            ->success()
            ->send();
    }

    private function markAllAsRead($records): void
    {
        $records->each(function ($record) {
            $record->markAsRead();
        });
        
        \Filament\Notifications\Notification::make()
            ->title('Semua notifikasi ditandai dibaca')
            ->success()
            ->send();
    }

    private function deleteNotification($record): void
    {
        $record->delete();
        
        \Filament\Notifications\Notification::make()
            ->title('Notifikasi berhasil dihapus')
            ->success()
            ->send();
    }

    private function deleteSelectedNotifications($records): void
    {
        $count = $records->count();
        $records->each(function ($record) {
            $record->delete();
        });
        
        \Filament\Notifications\Notification::make()
            ->title("{$count} notifikasi berhasil dihapus")
            ->success()
            ->send();
    }

    private function showNotificationDetail($record): void
    {
        // This would show a modal or redirect to show notification details
        \Filament\Notifications\Notification::make()
            ->title('Detail Notifikasi')
            ->body($this->extractMessage($record->data))
            ->info()
            ->send();
    }
}
