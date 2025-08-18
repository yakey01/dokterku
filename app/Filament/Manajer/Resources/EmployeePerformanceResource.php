<?php

namespace App\Filament\Manajer\Resources;

use App\Models\Pegawai;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeePerformanceResource extends Resource
{
    protected static ?string $model = Pegawai::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationLabel = 'Employee Performance';
    
    protected static ?string $navigationGroup = '👥 Staff Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_lengkap')
                    ->label('Nama Pegawai')
                    ->required(),
                    
                Forms\Components\TextInput::make('nik')
                    ->label('NIK')
                    ->required(),
                    
                Forms\Components\Select::make('jenis_pegawai')
                    ->label('Jenis Pegawai')
                    ->options([
                        'Paramedis' => 'Paramedis',
                        'Non-Paramedis' => 'Non-Paramedis',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('jabatan')
                    ->label('Jabatan')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('jenis_pegawai')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Paramedis' => 'primary',
                        'Non-Paramedis' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('jabatan')
                    ->label('Position')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('monthly_activities')
                    ->label('Monthly Activities')
                    ->state(function (Pegawai $record): string {
                        // Simulate activity count based on procedures performed
                        $activities = \App\Models\Tindakan::whereRaw('strftime("%m", created_at) = ?', [str_pad(now()->month, 2, '0', STR_PAD_LEFT)])
                            ->where(function($query) use ($record) {
                                $query->where('paramedis_id', $record->id)
                                      ->orWhere('non_paramedis_id', $record->id);
                            })
                            ->count();
                        return $activities . ' activities';
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        $count = (int) str_replace(' activities', '', $state);
                        return match (true) {
                            $count >= 20 => 'success',
                            $count >= 10 => 'warning',
                            default => 'danger',
                        };
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('calculated_performance')
                    ->label('Performance Score')
                    ->state(function (Pegawai $record): string {
                        // Calculate performance based on monthly activities
                        $activities = \App\Models\Tindakan::whereRaw('strftime("%m", created_at) = ?', [str_pad(now()->month, 2, '0', STR_PAD_LEFT)])
                            ->where(function($query) use ($record) {
                                $query->where('paramedis_id', $record->id)
                                      ->orWhere('non_paramedis_id', $record->id);
                            })
                            ->count();
                        
                        $score = min(100, ($activities * 3)); // 3% per activity
                        return $score . '%';
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        $score = (int) str_replace('%', '', $state);
                        return match (true) {
                            $score >= 80 => 'success',
                            $score >= 60 => 'warning',
                            default => 'danger',
                        };
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Bergabung')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_pegawai')
                    ->label('Employee Type')
                    ->options([
                        'Paramedis' => 'Paramedis',
                        'Non-Paramedis' => 'Non-Paramedis',
                    ]),

                Tables\Filters\Filter::make('high_performers')
                    ->label('High Performers (>20 activities)')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereRaw('(SELECT COUNT(*) FROM tindakan WHERE strftime("%m", tindakan.created_at) = ? AND (tindakan.paramedis_id = pegawais.id OR tindakan.non_paramedis_id = pegawais.id) AND tindakan.deleted_at IS NULL) > 20', [str_pad(now()->month, 2, '0', STR_PAD_LEFT)])
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Manajer\Resources\EmployeePerformanceResource\Pages\ListEmployeePerformances::route('/'),
        ];
    }
}