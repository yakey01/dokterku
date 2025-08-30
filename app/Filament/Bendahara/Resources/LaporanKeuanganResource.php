<?php

namespace App\Filament\Bendahara\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\Action;
use Carbon\Carbon;

class LaporanKeuanganResource extends Resource
{
    protected static ?string $model = null; // This is a utility resource for financial reports

    protected static ?string $navigationIcon = null;

    // DISABLED: Prevent showing in bendahara panel due to missing model
    protected static ?string $navigationGroup = null;

    protected static ?string $navigationLabel = 'Laporan Keuangan';

    protected static ?string $modelLabel = 'Laporan Keuangan';

    protected static ?string $pluralModelLabel = 'Laporan Keuangan';

    // DISABLED: Prevent navigation registration due to missing model
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('report_type')
                    ->label('Jenis Laporan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('period')
                    ->label('Periode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Total Pendapatan')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('total_expense')
                    ->label('Total Pengeluaran')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('net_income')
                    ->label('Laba Bersih')
                    ->money('IDR'),
            ])
            ->headerActions([
                Action::make('generate_report')
                    ->label('📄 Generate Report')
                    ->color('success')
                    ->action(function () {
                        Notification::make()
                            ->title('📄 Financial Report')
                            ->body('Advanced financial reporting system coming soon!')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function canAccess(): bool
    {
        return true; // Override access control for bendahara
    }

    public static function getPages(): array
    {
        return [
            'index' => LaporanKeuanganResource\Pages\ListLaporanKeuangan::route('/'),
        ];
    }
}