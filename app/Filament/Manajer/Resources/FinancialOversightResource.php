<?php

namespace App\Filament\Manajer\Resources;

use App\Models\Pendapatan;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FinancialOversightResource extends Resource
{
    protected static ?string $model = Pendapatan::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    
    protected static ?string $navigationLabel = 'Financial Overview';
    
    protected static ?string $navigationGroup = '💰 Financial Control';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_pendapatan')
                    ->label('Pendapatan')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR'),
                    
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date(),
                    
                Tables\Columns\BadgeColumn::make('status_validasi')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'disetujui',
                        'danger' => 'ditolak',
                    ]),
            ])
            ->defaultSort('tanggal', 'desc');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Manajer\Resources\FinancialOversightResource\Pages\ListFinancialOversights::route('/'),
        ];
    }
}