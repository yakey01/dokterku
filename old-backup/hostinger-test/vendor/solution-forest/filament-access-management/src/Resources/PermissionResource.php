<?php

namespace SolutionForest\FilamentAccessManagement\Resources;

use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate;
use SolutionForest\FilamentAccessManagement\Resources\PermissionResource\Pages;
use SolutionForest\FilamentAccessManagement\Resources\PermissionResource\RelationManagers;
use SolutionForest\FilamentAccessManagement\Support\Utils;

class PermissionResource extends Resource
{
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->label(strval(__('filament-access-management::filament-access-management.field.name'))),

                            Forms\Components\TextInput::make('guard_name')
                                ->required()
                                ->label(strval(__('filament-access-management::filament-access-management.field.guard_name')))
                                ->default(config('auth.defaults.guard')),

                            Forms\Components\Select::make('http_path')
                                ->options(FilamentAuthenticate::allRoutes())
                                ->searchable()
                                ->label(strval(__('filament-access-management::filament-access-management.field.http_path'))),

                            // Forms\Components\BelongsToManyMultiSelect::make('roles')
                            //     ->label(strval(__('filament-access-management::filament-access-management.field.roles')))
                            //     ->relationship('roles', 'name')
                            //     ->preload()
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->label(strval(__('filament-access-management::filament-access-management.field.id'))),

                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->label(strval(__('filament-access-management::filament-access-management.field.name'))),

                Tables\Columns\TextColumn::make('guard_name')
                    ->label(strval(__('filament-access-management::filament-access-management.field.guard_name'))),

                Tables\Columns\TextColumn::make('http_path')
                    ->label(strval(__('filament-access-management::filament-access-management.field.http_path'))),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('Y-m-d H:i:s')
                    ->label(strval(__('filament-access-management::filament-access-management.field.created_at'))),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RolesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
            'view' => Pages\ViewPermission::route('/{record}'),
        ];
    }

    public static function getNavigationIcon(): string
    {
        return config('filament-access-management.filament.navigationIcon.permission') ?? parent::getNavigationIcon();
    }

    public static function getModel(): string
    {
        return Utils::getPermissionModel() ?? parent::getModel();
    }

    public static function getNavigationGroup(): ?string
    {
        return strval(__('filament-access-management::filament-access-management.section.group'));
    }

    public static function getLabel(): string
    {
        return strval(__('filament-access-management::filament-access-management.section.permission'));
    }

    public static function getPluralLabel(): string
    {
        return strval(__('filament-access-management::filament-access-management.section.permissions'));
    }
}
