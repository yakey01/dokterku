<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaceRecognitionResource\Pages;
use App\Models\FaceRecognition;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FaceRecognitionResource extends Resource
{
    protected static ?string $model = FaceRecognition::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-face-smile';
    protected static ?string $navigationLabel = 'Face Recognition';
    protected static ?string $modelLabel = 'Face Recognition';
    protected static ?string $pluralModelLabel = 'Face Recognitions';
    protected static ?string $navigationGroup = 'Attendance';
    protected static ?int $navigationSort = 43;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                Forms\Components\Section::make('Face Recognition Data')
                    ->schema([
                        Forms\Components\FileUpload::make('face_image_path')
                            ->label('Face Image')
                            ->image()
                            ->directory('face-recognition')
                            ->visibility('public')
                            ->imageEditor()
                            ->imageCropAspectRatio('1:1')
                            ->maxSize(2048),

                        Forms\Components\Textarea::make('face_encoding')
                            ->label('Face Encoding')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Generated automatically from face image'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('confidence_score')
                                    ->label('Confidence Score')
                                    ->numeric()
                                    ->step(0.0001)
                                    ->minValue(0)
                                    ->maxValue(1)
                                    ->placeholder('0.0000'),

                                Forms\Components\Select::make('encoding_algorithm')
                                    ->label('Algorithm')
                                    ->options([
                                        'dlib' => 'DLib',
                                        'opencv' => 'OpenCV',
                                        'facenet' => 'FaceNet',
                                        'arcface' => 'ArcFace',
                                    ])
                                    ->default('dlib')
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('Status & Verification')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),

                                Forms\Components\Toggle::make('is_verified')
                                    ->label('Verified')
                                    ->default(false),

                                Forms\Components\Select::make('verified_by')
                                    ->label('Verified By')
                                    ->relationship('verifiedBy', 'name')
                                    ->searchable()
                                    ->preload(),
                            ]),

                        Forms\Components\DateTimePicker::make('verified_at')
                            ->label('Verified At')
                            ->placeholder('Select verification date'),

                        Forms\Components\KeyValue::make('metadata')
                            ->label('Additional Metadata')
                            ->keyLabel('Property')
                            ->valueLabel('Value'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('face_image_path')
                    ->label('Face Image')
                    ->circular()
                    ->size(60),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('confidence_score')
                    ->label('Confidence')
                    ->numeric(4)
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state >= 0.9 ? 'success' : ($state >= 0.7 ? 'warning' : 'danger')),

                Tables\Columns\TextColumn::make('encoding_algorithm')
                    ->label('Algorithm')
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Verified')
                    ->boolean(),

                Tables\Columns\TextColumn::make('verifiedBy.name')
                    ->label('Verified By')
                    ->placeholder('Not verified')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('verified_at')
                    ->label('Verified At')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('encoding_algorithm')
                    ->label('Algorithm')
                    ->options([
                        'dlib' => 'DLib',
                        'opencv' => 'OpenCV',
                        'facenet' => 'FaceNet',
                        'arcface' => 'ArcFace',
                    ]),

                Filter::make('verified')
                    ->label('Verified Only')
                    ->query(fn (Builder $query): Builder => $query->verified()),

                Filter::make('active')
                    ->label('Active Only')
                    ->query(fn (Builder $query): Builder => $query->active()),

                Filter::make('high_confidence')
                    ->label('High Confidence (≥0.9)')
                    ->query(fn (Builder $query): Builder => $query->where('confidence_score', '>=', 0.9)),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Action::make('verify')
                    ->label('Verify')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_verified)
                    ->action(function ($record) {
                        $record->verify(auth()->id());
                        Notification::make()
                            ->title('Face recognition verified successfully')
                            ->success()
                            ->send();
                    }),

                Action::make('test_recognition')
                    ->label('Test')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->url(fn ($record) => route('filament.admin.resources.face-recognitions.test', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaceRecognitions::route('/'),
            'create' => Pages\CreateFaceRecognition::route('/create'),
            'view' => Pages\ViewFaceRecognition::route('/{record}'),
            'edit' => Pages\EditFaceRecognition::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
