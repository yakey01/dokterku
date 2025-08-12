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
use App\Models\Tindakan;

class TimelineTindakanPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Timeline Tindakan';
    protected static ?string $title = 'Timeline Tindakan Medis';
    protected static ?string $slug = 'timeline-tindakan';
    protected static ?int $navigationSort = 6;
    protected static string $view = 'filament.petugas.pages.timeline-tindakan';

    public function table(Table $table): Table
    {
        return $table
            ->query(Tindakan::query()->with(['pasien', 'dokter']))
            ->columns([
                TextColumn::make('pasien.nama')
                    ->label('Nama Pasien')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jenis_tindakan')
                    ->label('Jenis Tindakan')
                    ->searchable(),
                TextColumn::make('dokter.nama')
                    ->label('Dokter')
                    ->searchable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'selesai',
                        'warning' => 'proses',
                        'danger' => 'batal',
                        'info' => 'jadwal',
                    ]),
                TextColumn::make('tanggal_tindakan')
                    ->label('Tanggal Tindakan')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('durasi')
                    ->label('Durasi')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} menit" : '-'),
                TextColumn::make('biaya')
                    ->label('Biaya')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Tindakan')
                    ->options([
                        'jadwal' => 'Terjadwal',
                        'proses' => 'Sedang Proses',
                        'selesai' => 'Selesai',
                        'batal' => 'Dibatalkan',
                    ]),
                SelectFilter::make('jenis_tindakan')
                    ->label('Jenis Tindakan')
                    ->options(function () {
                        return Tindakan::distinct()->pluck('jenis_tindakan', 'jenis_tindakan')->toArray();
                    }),
                Filter::make('tanggal_tindakan')
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
                                fn ($query, $date) => $query->whereDate('tanggal_tindakan', '>=', $date)
                            )
                            ->when(
                                $data['tanggal_akhir'],
                                fn ($query, $date) => $query->whereDate('tanggal_tindakan', '<=', $date)
                            );
                    })
            ])
            ->actions([
                ViewAction::make()
                    ->label('Detail')
                    ->icon('heroicon-o-eye'),
                Action::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\Select::make('status')
                            ->label('Status Baru')
                            ->options([
                                'jadwal' => 'Terjadwal',
                                'proses' => 'Sedang Proses',
                                'selesai' => 'Selesai',
                                'batal' => 'Dibatalkan',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data, Tindakan $record): void {
                        $record->update(['status' => $data['status']]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Status berhasil diupdate')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('tanggal_tindakan', 'desc')
            ->paginated([10, 25, 50, 100]);
    }
}
