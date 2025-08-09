<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftTemplateResource\Pages;
use App\Filament\Resources\ShiftTemplateResource\RelationManagers;
use App\Models\ShiftTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShiftTemplateResource extends Resource
{
    protected static ?string $model = ShiftTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    
    protected static ?string $navigationGroup = '📅 KALENDAR DAN JADWAL';
    
    protected static ?string $navigationLabel = 'Template Shift';
    
    protected static ?string $modelLabel = 'Template Shift';
    
    protected static ?string $pluralModelLabel = 'Template Shift';

    protected static ?int $navigationSort = 31;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_shift')
                    ->label('Nama Shift')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TimePicker::make('jam_masuk')
                    ->label('Jam Masuk')
                    ->required()
                    ->seconds(false)
                    ->native(false)
                    ->helperText('Pilih jam mulai shift'),
                Forms\Components\TimePicker::make('jam_pulang')
                    ->label('Jam Pulang')
                    ->required()
                    ->seconds(false)
                    ->native(false)
                    ->helperText('Pilih jam selesai shift')
                    ->rules([
                        function (callable $get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                $jamMasuk = $get('jam_masuk');
                                $jamPulang = $value;
                                
                                if ($jamMasuk && $jamPulang) {
                                    $masuk = \Carbon\Carbon::parse($jamMasuk);
                                    $pulang = \Carbon\Carbon::parse($jamPulang);
                                    
                                    // Hitung durasi dalam menit untuk akurasi (termasuk kasus < 1 jam)
                                    $isOvernight = $pulang->lessThanOrEqualTo($masuk);
                                    $effectiveEnd = $isOvernight ? $pulang->copy()->addDay() : $pulang;
                                    $durationMinutes = $effectiveEnd->diffInMinutes($masuk);

                                    // Validasi durasi maksimal
                                    // Normal shift: maks 12 jam (720 menit), Overnight: maks 16 jam (960 menit)
                                    $maxMinutes = $isOvernight ? (16 * 60) : (12 * 60);
                                    if ($durationMinutes > $maxMinutes) {
                                        $fail('Shift terlalu panjang. Maksimal durasi shift adalah ' . ($isOvernight ? '16' : '12') . ' jam.');
                                        return;
                                    }
                                }
                            };
                        }
                    ]),
                Forms\Components\Placeholder::make('shift_info')
                    ->label('Informasi Shift')
                    ->content(function (callable $get) {
                        $jamMasuk = $get('jam_masuk');
                        $jamPulang = $get('jam_pulang');
                        
                        if (!$jamMasuk || !$jamPulang) {
                            return 'Pilih jam masuk dan jam pulang untuk melihat informasi shift.';
                        }
                        
                        $masuk = \Carbon\Carbon::parse($jamMasuk);
                        $pulang = \Carbon\Carbon::parse($jamPulang);
                        
                        $isOvernight = $pulang->lessThanOrEqualTo($masuk);
                        $effectiveEnd = $isOvernight ? $pulang->copy()->addDay() : $pulang;
                        $totalMinutes = $effectiveEnd->diffInMinutes($masuk);
                        $hours = intdiv($totalMinutes, 60);
                        $minutes = $totalMinutes % 60;
                        $durasiTeks = trim(($hours > 0 ? $hours . ' jam' : '') . ($minutes > 0 ? ' ' . $minutes . ' menit' : ($hours === 0 ? '0 menit' : '')));

                        if ($isOvernight) {
                            return "🌙 **Shift Malam** - Durasi: {$durasiTeks} (overnight shift)";
                        }

                        return "☀️ **Shift Normal** - Durasi: {$durasiTeks}";
                    })
                    ->visible(fn (callable $get) => $get('jam_masuk') && $get('jam_pulang')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_shift')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jam_masuk')
                    ->label('Jam Masuk')
                    ->time('H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jam_pulang')
                    ->label('Jam Pulang')
                    ->time('H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('durasi')
                    ->label('Durasi')
                    ->badge()
                    ->color('info')
                    ->sortable(false),
                Tables\Columns\TextColumn::make('shift_type')
                    ->label('Tipe')
                    ->getStateUsing(function (ShiftTemplate $record): string {
                        $masuk = \Carbon\Carbon::parse($record->jam_masuk);
                        $pulang = \Carbon\Carbon::parse($record->jam_pulang);
                        
                        if ($pulang->lessThanOrEqualTo($masuk)) {
                            return '🌙 Malam';
                        } else {
                            return '☀️ Normal';
                        }
                    })
                    ->badge()
                    ->color(fn (string $state): string => str_contains($state, 'Malam') ? 'warning' : 'success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShiftTemplates::route('/'),
            'create' => Pages\CreateShiftTemplate::route('/create'),
            'edit' => Pages\EditShiftTemplate::route('/{record}/edit'),
        ];
    }
}
