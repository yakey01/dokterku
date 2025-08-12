<?php

namespace App\Filament\Petugas\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use App\Models\Income;

class PendapatanPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';
    protected static ?string $navigationLabel = 'Pendapatan';
    protected static ?string $title = 'Manajemen Pendapatan';
    protected static ?string $slug = 'pendapatan';
    protected static ?int $navigationSort = 8;
    protected static string $view = 'filament.petugas.pages.pendapatan';

    public function table(Table $table): Table
    {
        return $table
            ->query(Income::query())
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->searchable(),
                TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'diterima',
                        'warning' => 'pending',
                        'danger' => 'ditolak',
                        'info' => 'verifikasi',
                    ]),
                TextColumn::make('metode_pembayaran')
                    ->label('Metode Pembayaran')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Tanggal Input')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('kategori')
                    ->label('Kategori Pendapatan')
                    ->options([
                        'konsultasi' => 'Konsultasi',
                        'tindakan' => 'Tindakan Medis',
                        'obat' => 'Obat-obatan',
                        'alat' => 'Alat Medis',
                        'lainnya' => 'Lainnya',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'diterima' => 'Diterima',
                        'pending' => 'Pending',
                        'ditolak' => 'Ditolak',
                        'verifikasi' => 'Verifikasi',
                    ]),
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
                                fn ($query, $date) => $query->whereDate('tanggal', '>=', $date)
                            )
                            ->when(
                                $data['tanggal_akhir'],
                                fn ($query, $date) => $query->whereDate('tanggal', '<=', $date)
                            );
                    })
            ])
            ->actions([
                ViewAction::make()
                    ->label('Detail')
                    ->icon('heroicon-o-eye'),
                EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil'),
                Action::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-o-check-circle')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\Select::make('status')
                            ->label('Status Baru')
                            ->options([
                                'diterima' => 'Diterima',
                                'pending' => 'Pending',
                                'ditolak' => 'Ditolak',
                                'verifikasi' => 'Verifikasi',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data, Income $record): void {
                        $record->update(['status' => $data['status']]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Status berhasil diupdate')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('tanggal', 'desc')
            ->paginated([10, 25, 50, 100]);
    }
}
