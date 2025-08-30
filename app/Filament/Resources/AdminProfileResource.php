<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminProfileResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class AdminProfileResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    
    protected static ?string $navigationGroup = '👤 Account';
    
    protected static ?string $navigationLabel = 'Profile Settings';
    
    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'admin-profile';

    // Only show current admin user's profile
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('id', Auth::id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Profile Section
                Forms\Components\Section::make('👤 Profile Information')
                    ->description('Your personal account information')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\FileUpload::make('avatar_url')
                                    ->label('Profile Photo')
                                    ->image()
                                    ->avatar()
                                    ->directory('admin-avatars')
                                    ->maxSize(2048)
                                    ->columnSpan(1),
                                    
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Full Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->helperText('Your display name'),
                                            
                                        Forms\Components\TextInput::make('username')
                                            ->label('Username')
                                            ->maxLength(50)
                                            ->placeholder('admin_user')
                                            ->unique(ignoreRecord: true),
                                            
                                        Forms\Components\TextInput::make('email')
                                            ->label('Email Address')
                                            ->email()
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->helperText('Primary contact email')
                                            ->columnSpan(2),
                                            
                                        Forms\Components\TextInput::make('no_telepon')
                                            ->label('Phone Number')
                                            ->tel()
                                            ->placeholder('+62 812-3456-7890'),
                                            
                                        Forms\Components\TextInput::make('nip')
                                            ->label('Employee ID')
                                            ->placeholder('ADM001'),
                                    ])
                                    ->columnSpan(2),
                            ])
                    ]),
                    
                // Security Section
                Forms\Components\Section::make('🔐 Security Settings')
                    ->description('Password and security configuration')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('security_info')
                                    ->content(function () {
                                        return new HtmlString('
                                            <div class="bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                                <div class="text-center">
                                                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mb-1">95%</div>
                                                    <div class="text-sm text-blue-700 dark:text-blue-300">Security Score</div>
                                                    <div class="w-full bg-blue-100 dark:bg-blue-900 rounded-full h-2 mt-2">
                                                        <div class="bg-blue-500 h-2 rounded-full" style="width: 95%"></div>
                                                    </div>
                                                    <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">Excellent security</div>
                                                </div>
                                            </div>
                                        ');
                                    })
                                    ->columnSpan(1),
                                    
                                Forms\Components\Grid::make(1)
                                    ->schema([
                                        Forms\Components\TextInput::make('current_password')
                                            ->label('Current Password')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('Enter current password')
                                            ->helperText('Required for password changes')
                                            ->dehydrated(false),
                                            
                                        Forms\Components\TextInput::make('password')
                                            ->label('New Password')
                                            ->password()
                                            ->revealable()
                                            ->confirmed()
                                            ->helperText('Strong password required'),
                                            
                                        Forms\Components\TextInput::make('password_confirmation')
                                            ->label('Confirm Password')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Must match new password'),
                                    ])
                                    ->columnSpan(2),
                            ])
                    ]),
                    
                // Preferences Section
                Forms\Components\Section::make('⚙️ Preferences')
                    ->description('Customize your admin experience')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Select::make('language')
                                    ->label('Language')
                                    ->options([
                                        'id' => 'Bahasa Indonesia',
                                        'en' => 'English',
                                        'es' => 'Español',
                                        'fr' => 'Français',
                                    ])
                                    ->default('id')
                                    ->selectablePlaceholder(false),
                                    
                                Forms\Components\Select::make('timezone')
                                    ->label('Timezone')
                                    ->options([
                                        'Asia/Jakarta' => 'Jakarta (UTC+7)',
                                        'Asia/Makassar' => 'Makassar (UTC+8)',
                                        'UTC' => 'UTC',
                                    ])
                                    ->default('Asia/Jakarta')
                                    ->selectablePlaceholder(false),
                                    
                                Forms\Components\ColorPicker::make('theme_color')
                                    ->label('Theme Color')
                                    ->default('#3b82f6'),
                                    
                                Forms\Components\Grid::make(1)
                                    ->schema([
                                        Forms\Components\Toggle::make('email_notifications')
                                            ->label('Email Notifications')
                                            ->default(true)
                                            ->inline(false),
                                            
                                        Forms\Components\Toggle::make('security_alerts')
                                            ->label('Security Alerts')
                                            ->default(true)
                                            ->inline(false),
                                    ])
                                    ->columnSpan(1),
                            ])
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email'),
                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // No bulk actions needed for profile
            ])
            ->emptyStateHeading('No profile found')
            ->emptyStateDescription('This should not happen for admin users.')
            ->paginated(false);
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
            'index' => Pages\ListAdminProfiles::route('/'),
            'edit' => Pages\EditAdminProfile::route('/{record}/edit'),
        ];
    }
    
    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }
    
    public static function canCreate(): bool
    {
        return false; // Admin can't create multiple profiles
    }
    
    public static function canDelete($record): bool
    {
        return false; // Admin can't delete their own profile
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}