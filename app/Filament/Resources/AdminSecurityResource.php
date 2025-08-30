<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminSecurityResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class AdminSecurityResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static ?string $navigationGroup = '👤 Account';
    
    protected static ?string $navigationLabel = 'Security & Sessions';
    
    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'admin-security';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Activity Overview Section
                Forms\Components\Section::make('📊 Account Activity')
                    ->description('Your account activity and security metrics')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Forms\Components\Grid::make(['default' => 2, 'md' => 4, 'lg' => 6])
                            ->schema([
                                Forms\Components\Placeholder::make('logins')
                                    ->content(new HtmlString('
                                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center hover:shadow-md transition-shadow">
                                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">847</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Logins</div>
                                        </div>
                                    ')),
                                    
                                Forms\Components\Placeholder::make('sessions')
                                    ->content(new HtmlString('
                                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center hover:shadow-md transition-shadow">
                                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">3</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">Active Sessions</div>
                                        </div>
                                    ')),
                                    
                                Forms\Components\Placeholder::make('failed')
                                    ->content(new HtmlString('
                                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center hover:shadow-md transition-shadow">
                                            <div class="text-2xl font-bold text-red-600 dark:text-red-400">0</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">Failed Today</div>
                                        </div>
                                    ')),
                                    
                                Forms\Components\Placeholder::make('age')
                                    ->content(new HtmlString('
                                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center hover:shadow-md transition-shadow">
                                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">156</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">Account Days</div>
                                        </div>
                                    ')),
                                    
                                Forms\Components\Placeholder::make('users')
                                    ->content(new HtmlString('
                                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center hover:shadow-md transition-shadow">
                                            <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">1,247</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Users</div>
                                        </div>
                                    ')),
                                    
                                Forms\Components\Placeholder::make('uptime')
                                    ->content(new HtmlString('
                                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center hover:shadow-md transition-shadow">
                                            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">99.9%</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">Uptime</div>
                                        </div>
                                    ')),
                            ])
                    ]),
                    
                // Recent Activity Section
                Forms\Components\Section::make('🕒 Recent Activity')
                    ->description('Your recent login and system activity')
                    ->icon('heroicon-o-clock')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Placeholder::make('recent_activity')
                            ->content(new HtmlString('
                                <div class="space-y-3">
                                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                        <div class="flex-1">
                                            <div class="text-sm font-medium">Login successful</div>
                                            <div class="text-xs text-gray-500">Today at 14:23 from 127.0.0.1</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                        <div class="flex-1">
                                            <div class="text-sm font-medium">Profile updated</div>
                                            <div class="text-xs text-gray-500">Yesterday at 09:15</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                        <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
                                        <div class="flex-1">
                                            <div class="text-sm font-medium">Password changed</div>
                                            <div class="text-xs text-gray-500">3 days ago at 16:45</div>
                                        </div>
                                    </div>
                                </div>
                            '))
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ])
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
            'index' => Pages\ListAdminSecurity::route('/'),
        ];
    }
    
    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}