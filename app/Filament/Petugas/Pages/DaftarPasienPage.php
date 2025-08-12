<?php

namespace App\Filament\Petugas\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use App\Models\Pasien;

class DaftarPasienPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Daftar Pasien';
    protected static ?string $title = 'Daftar Pasien';
    protected static ?string $slug = 'daftar-pasien';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.petugas.pages.daftar-pasien';

    public function table(Table $table): Table
    {
        return $table
            ->query(Pasien::query())
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama Pasien')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('no_rm')
                    ->label('No. RM')
                    ->searchable(),
                TextColumn::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'L' => 'success',
                        'P' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('tanggal_lahir')
                    ->label('Tanggal Lahir')
                    ->date('d/m/Y'),
                TextColumn::make('alamat')
                    ->label('Alamat')
                    ->limit(50),
                TextColumn::make('created_at')
                    ->label('Tanggal Daftar')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ]),
                Filter::make('tanggal_daftar')
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
                    ->label('Lihat')
                    ->icon('heroicon-o-eye'),
                EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil'),
            ])
            ->bulkActions([
                // Bulk actions can be added here
            ])
            ->defaultSort('created_at', 'desc');
    }
}
