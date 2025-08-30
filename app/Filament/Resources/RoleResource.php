<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';
    
    protected static ?string $navigationGroup = 'User Management';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $modelLabel = 'Role';
    
    protected static ?string $pluralModelLabel = 'Roles';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['admin']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Role Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Unique role identifier (e.g., admin, manager)'),
                            
                        Forms\Components\TextInput::make('display_name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Human-readable role name'),
                            
                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000)
                            ->helperText('Role description and responsibilities'),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Whether this role is active'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Permissions')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->options([
                                'view_admin_panel' => 'View Admin Panel',
                                'view_any_user' => 'View Any User',
                                'view_user' => 'View User',
                                'create_user' => 'Create User',
                                'update_user' => 'Update User',
                                'delete_user' => 'Delete User',
                                'delete_any_user' => 'Delete Any User',
                                'view_any_role' => 'View Any Role',
                                'view_role' => 'View Role',
                                'create_role' => 'Create Role',
                                'update_role' => 'Update Role',
                                'delete_role' => 'Delete Role',
                                'delete_any_role' => 'Delete Any Role',
                                'view_any_pasien' => 'View Any Patient',
                                'view_pasien' => 'View Patient',
                                'create_pasien' => 'Create Patient',
                                'update_pasien' => 'Update Patient',
                                'delete_pasien' => 'Delete Patient',
                                'delete_any_pasien' => 'Delete Any Patient',
                                'view_any_tindakan' => 'View Any Procedure',
                                'view_tindakan' => 'View Procedure',
                                'create_tindakan' => 'Create Procedure',
                                'update_tindakan' => 'Update Procedure',
                                'delete_tindakan' => 'Delete Procedure',
                                'delete_any_tindakan' => 'Delete Any Procedure',
                                'view_any_pendapatan' => 'View Any Income',
                                'view_pendapatan' => 'View Income',
                                'create_pendapatan' => 'Create Income',
                                'update_pendapatan' => 'Update Income',
                                'delete_pendapatan' => 'Delete Income',
                                'delete_any_pendapatan' => 'Delete Any Income',
                                'view_any_pengeluaran' => 'View Any Expense',
                                'view_pengeluaran' => 'View Expense',
                                'create_pengeluaran' => 'Create Expense',
                                'update_pengeluaran' => 'Update Expense',
                                'delete_pengeluaran' => 'Delete Expense',
                                'delete_any_pengeluaran' => 'Delete Any Expense',
                                'manage_users' => 'Manage Users',
                                'manage_roles' => 'Manage Roles',
                                'view_reports' => 'View Reports',
                                'manage_finance' => 'Manage Finance',
                                'validate_transactions' => 'Validate Transactions',
                                'export_data' => 'Export Data',
                            ])
                            ->columns(3)
                            ->searchable()
                            ->bulkToggleable(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('display_name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('clinicUsers_count')
                    ->counts('clinicUsers')
                    ->label('Users')
                    ->sortable(),
                    
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
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => Pages\ViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}