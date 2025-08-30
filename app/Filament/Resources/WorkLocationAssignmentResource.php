<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkLocationAssignmentResource\Pages;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\AssignmentHistory;
use App\Services\SmartWorkLocationAssignmentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class WorkLocationAssignmentResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = '🎯 Smart Assignment';
    
    protected static ?string $modelLabel = 'Work Location Assignment';
    
    protected static ?string $pluralModelLabel = 'Work Location Assignments';
    
    protected static ?string $navigationGroup = 'Attendance';
    
    protected static ?int $navigationSort = 42;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🎯 Smart User Selection')
                    ->description('Select user for intelligent work location assignment')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('👤 Select User for Assignment')
                            ->options(function () {
                                return User::with(['role', 'pegawai', 'workLocation'])
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($user) {
                                        // Prioritize user name and make it prominent
                                        $roleEmoji = match(strtolower($user->role?->name ?? '')) {
                                            'dokter' => '👨‍⚕️',
                                            'paramedis' => '👩‍⚕️',
                                            'admin' => '👨‍💼',
                                            'manajer' => '👨‍💼',
                                            'bendahara' => '💰',
                                            'petugas' => '👨‍💻',
                                            default => '👤'
                                        };
                                        
                                        $statusEmoji = $user->work_location_id ? '✅' : '⚠️';
                                        
                                        // Simple, user-friendly format: Status + Name + Role
                                        $label = "{$statusEmoji} {$user->name} {$roleEmoji}";
                                        
                                        // Add current assignment info only if assigned
                                        if ($user->workLocation) {
                                            $label .= " - {$user->workLocation->name}";
                                        }
                                        
                                        return [$user->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state) {
                                    $user = User::with(['role', 'pegawai', 'workLocation', 'dokter'])
                                        ->find($state);
                                    
                                    if ($user) {
                                        // Set user information
                                        $set('selected_user_info', [
                                            'id' => $user->id,
                                            'name' => $user->name,
                                            'email' => $user->email,
                                            'role' => $user->role?->name ?? 'No Role',
                                            'unit_kerja' => $user->pegawai?->unit_kerja ?? 'Not Set',
                                            'jenis_pegawai' => $user->pegawai?->jenis_pegawai ?? 'Not Set',
                                            'current_location' => $user->workLocation?->name ?? 'Not Assigned',
                                            'specialization' => $user->dokter?->spesialisasi ?? null
                                        ]);
                                        
                                        // Get smart recommendations
                                        $service = app(\App\Services\SmartWorkLocationAssignmentService::class);
                                        $recommendations = $service->getAssignmentRecommendations($user);
                                        $set('smart_recommendations', $recommendations);
                                        
                                        // Auto-select top recommendation if confidence is high
                                        $topRec = $recommendations['top_recommendation'];
                                        if ($topRec && $topRec['confidence'] === 'very_high') {
                                            $set('work_location_id', $topRec['location']->id);
                                        }
                                    }
                                }
                            })
                            ->placeholder('🔍 Search by name to find user...')
                            ->helperText('✅ Assigned users | ⚠️ Unassigned users | Sorted alphabetically for easy selection'),

                        Forms\Components\Placeholder::make('selected_user_info')
                            ->label('📋 Selected User Information')
                            ->content(function ($get) {
                                $userInfo = $get('selected_user_info');
                                if (!$userInfo) {
                                    return '👆 Select a user above to see detailed information';
                                }
                                
                                $roleEmoji = match(strtolower($userInfo['role'])) {
                                    'dokter' => '👨‍⚕️',
                                    'paramedis' => '👩‍⚕️',
                                    'admin' => '👨‍💼',
                                    'manajer' => '👨‍💼',
                                    'bendahara' => '💰',
                                    'petugas' => '👨‍💻',
                                    default => '👤'
                                };
                                
                                $content = "👤 **{$userInfo['name']}**\n";
                                $content .= "📧 {$userInfo['email']}\n";
                                $content .= "{$roleEmoji} {$userInfo['role']}\n";
                                $content .= "🏥 Unit: {$userInfo['unit_kerja']}\n";
                                $content .= "👨‍⚕️ Type: {$userInfo['jenis_pegawai']}\n";
                                
                                if ($userInfo['specialization']) {
                                    $content .= "🩺 Specialization: {$userInfo['specialization']}\n";
                                }
                                
                                $statusEmoji = $userInfo['current_location'] !== 'Not Assigned' ? '✅' : '🚫';
                                $content .= "{$statusEmoji} Current: {$userInfo['current_location']}";
                                
                                return $content;
                            })
                            ->visible(fn ($get) => $get('selected_user_info')),
                    ]),

                Forms\Components\Section::make('🧠 Smart Recommendations')
                    ->description('AI-powered location recommendations based on user profile')
                    ->schema([
                        Forms\Components\Placeholder::make('smart_recommendations_display')
                            ->label('💡 Top Recommendations')
                            ->content(function ($get) {
                                $recommendations = $get('smart_recommendations');
                                if (!$recommendations || empty($recommendations['recommendations'])) {
                                    return '🔍 Select a user to see smart recommendations';
                                }
                                
                                $content = '';
                                $count = 0;
                                foreach ($recommendations['recommendations'] as $rec) {
                                    if ($count >= 3) break;
                                    
                                    $emoji = match($rec['recommendation_level']) {
                                        'excellent' => '🟢',
                                        'very_good' => '🔵',
                                        'good' => '🟡',
                                        'fair' => '🟠',
                                        default => '🔴'
                                    };
                                    
                                    $confidenceEmoji = match($rec['confidence']) {
                                        'very_high' => '⭐⭐⭐',
                                        'high' => '⭐⭐',
                                        'medium' => '⭐',
                                        default => '⚪'
                                    };
                                    
                                    $content .= "{$emoji} **{$rec['location']->name}** {$confidenceEmoji}\n";
                                    $content .= "   📊 Score: {$rec['score']}/100 | 🎯 Confidence: " . ucfirst(str_replace('_', ' ', $rec['confidence'])) . "\n";
                                    
                                    if ($rec['location']->unit_kerja) {
                                        $content .= "   🏥 Unit: {$rec['location']->unit_kerja}\n";
                                    }
                                    
                                    if (!empty($rec['reasons'])) {
                                        $content .= "   💭 Reasons: " . implode(', ', array_slice($rec['reasons'], 0, 2)) . "\n";
                                    }
                                    $content .= "\n";
                                    $count++;
                                }
                                
                                return $content;
                            })
                            ->visible(fn ($get) => $get('smart_recommendations')),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('apply_top_recommendation')
                                ->label('🎯 Apply Top Recommendation')
                                ->icon('heroicon-o-cpu-chip')
                                ->color('success')
                                ->action(function ($get, $set) {
                                    $recommendations = $get('smart_recommendations');
                                    if ($recommendations && !empty($recommendations['recommendations'])) {
                                        $topRec = $recommendations['recommendations'][0];
                                        $set('work_location_id', $topRec['location']->id);
                                        
                                        \Filament\Notifications\Notification::make()
                                            ->title('🎯 Top Recommendation Applied')
                                            ->body("Selected: {$topRec['location']->name} (Score: {$topRec['score']}/100)")
                                            ->success()
                                            ->send();
                                    }
                                })
                                ->visible(fn ($get) => $get('smart_recommendations'))
                        ])
                    ])
                    ->visible(fn ($get) => $get('selected_user_info')),

                Forms\Components\Section::make('🎯 Smart Assignment')
                    ->description('AI-powered work location assignment with confidence scoring')
                    ->schema([
                        Forms\Components\Select::make('work_location_id')
                            ->label('Work Location')
                            ->relationship('workLocation', 'name')
                            ->options(function () {
                                return WorkLocation::where('is_active', true)
                                    ->get()
                                    ->mapWithKeys(function ($location) {
                                        $utilization = $location->getCapacityUtilization();
                                        $emoji = match($utilization['status']) {
                                            'optimal' => '🟢',
                                            'high_utilization' => '🟡',
                                            'over_capacity' => '🔴',
                                            default => '⚪'
                                        };
                                        
                                        $label = "{$emoji} {$location->name}";
                                        if ($location->unit_kerja) {
                                            $label .= " ({$location->unit_kerja})";
                                        }
                                        $label .= " - {$utilization['current_users']} users ({$utilization['utilization_percentage']}%)";
                                        
                                        return [$location->id => $label];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state) {
                                    $location = WorkLocation::find($state);
                                    if ($location) {
                                        $set('assignment_preview', [
                                            'location_name' => $location->name,
                                            'location_type' => $location->location_type,
                                            'unit_kerja' => $location->unit_kerja,
                                            'capacity' => $location->getCapacityUtilization()
                                        ]);
                                    }
                                }
                            }),

                        Forms\Components\Placeholder::make('assignment_preview')
                            ->label('Assignment Preview')
                            ->content(function ($get) {
                                $preview = $get('assignment_preview');
                                if (!$preview) {
                                    return 'Select a location to see assignment details';
                                }
                                
                                $capacity = $preview['capacity'];
                                $statusEmoji = match($capacity['status']) {
                                    'optimal' => '🟢 Optimal',
                                    'high_utilization' => '🟡 High Utilization',
                                    'over_capacity' => '🔴 Over Capacity',
                                    'low_utilization' => '🔵 Low Utilization',
                                    default => '⚪ Unknown'
                                };
                                
                                return "📍 {$preview['location_name']}\n" .
                                       "🏢 Type: {$preview['location_type']}\n" .
                                       "🏥 Unit: " . ($preview['unit_kerja'] ?? 'Not specified') . "\n" .
                                       "👥 Capacity: {$capacity['current_users']}/{$capacity['optimal_capacity']} ({$capacity['utilization_percentage']}%)\n" .
                                       "📊 Status: {$statusEmoji}";
                            })
                            ->visible(fn ($get) => $get('assignment_preview')),

                        Forms\Components\Textarea::make('assignment_notes')
                            ->label('Assignment Notes')
                            ->placeholder('Optional notes about this assignment...')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('👤 User')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('role.name')
                    ->label('🎭 Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'dokter' => 'success',
                        'paramedis' => 'info',
                        'admin' => 'warning',
                        'manajer' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('pegawai.unit_kerja')
                    ->label('🏥 Unit Kerja')
                    ->placeholder('Not Set')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('workLocation.name')
                    ->label('📍 Current Location')
                    ->placeholder('🚫 Not Assigned')
                    ->color(fn ($record) => $record->work_location_id ? 'success' : 'danger')
                    ->weight(fn ($record) => $record->work_location_id ? 'normal' : 'bold'),

                Tables\Columns\TextColumn::make('assignment_status')
                    ->label('📊 Status')
                    ->getStateUsing(function ($record) {
                        if (!$record->work_location_id) {
                            return '🚫 Needs Assignment';
                        }
                        
                        $location = $record->workLocation;
                        if (!$location || !$location->is_active) {
                            return '⚠️ Inactive Location';
                        }
                        
                        return '✅ Assigned';
                    })
                    ->badge()
                    ->color(function ($record) {
                        if (!$record->work_location_id) return 'danger';
                        
                        $location = $record->workLocation;
                        if (!$location || !$location->is_active) return 'warning';
                        
                        return 'success';
                    }),

                Tables\Columns\TextColumn::make('last_assignment')
                    ->label('📅 Last Assignment')
                    ->getStateUsing(function ($record) {
                        $lastAssignment = AssignmentHistory::where('user_id', $record->id)
                            ->latest()
                            ->first();
                        
                        return $lastAssignment ? $lastAssignment->created_at->diffForHumans() : 'Never';
                    })
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->relationship('role', 'name')
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('work_location_id')
                    ->label('Work Location')
                    ->relationship('workLocation', 'name')
                    ->multiple()
                    ->preload(),

                Tables\Filters\Filter::make('unassigned')
                    ->label('Unassigned Users')
                    ->query(fn (Builder $query): Builder => $query->whereNull('work_location_id'))
                    ->default(),

                Tables\Filters\Filter::make('needs_attention')
                    ->label('Needs Attention')
                    ->query(function (Builder $query): Builder {
                        return $query->where(function ($q) {
                            $q->whereNull('work_location_id')
                              ->orWhereHas('workLocation', function ($wq) {
                                  $wq->where('is_active', false);
                              });
                        });
                    }),
            ])
            ->actions([
                Action::make('smart_assign')
                    ->label('🧠 Smart Assign')
                    ->icon('heroicon-o-cpu-chip')
                    ->color('success')
                    ->visible(fn ($record) => !$record->work_location_id)
                    ->action(function ($record) {
                        $service = app(SmartWorkLocationAssignmentService::class);
                        $result = $service->intelligentAssignment($record);
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title('🎯 Smart Assignment Successful!')
                                ->body("Assigned {$record->name} to {$result['data']['location']['name']} with {$result['confidence_level']} confidence")
                                ->success()
                                ->duration(5000)
                                ->send();
                        } else {
                            Notification::make()
                                ->title('❌ Smart Assignment Failed')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('view_recommendations')
                    ->label('💡 View Recommendations')
                    ->icon('heroicon-o-light-bulb')
                    ->color('info')
                    ->modalHeading(fn ($record) => "🎯 Smart Assignment Recommendations for {$record->name}")
                    ->modalContent(function ($record) {
                        $service = app(SmartWorkLocationAssignmentService::class);
                        $recommendations = $service->getAssignmentRecommendations($record);
                        
                        $content = '<div class="space-y-4">';
                        
                        foreach ($recommendations['recommendations'] as $rec) {
                            $location = $rec['location'];
                            $confidence = $rec['confidence'];
                            $score = $rec['score'];
                            
                            $emoji = match($rec['recommendation_level']) {
                                'excellent' => '🟢',
                                'very_good' => '🔵',
                                'good' => '🟡',
                                'fair' => '🟠',
                                default => '🔴'
                            };
                            
                            $content .= '<div class="border rounded-lg p-4 bg-gray-50">';
                            $content .= "<h4 class='font-bold'>{$emoji} {$location->name} (Score: {$score})</h4>";
                            $content .= "<p class='text-sm text-gray-600'>Type: {$location->location_type}</p>";
                            if ($location->unit_kerja) {
                                $content .= "<p class='text-sm text-gray-600'>Unit: {$location->unit_kerja}</p>";
                            }
                            $content .= "<p class='text-sm font-medium'>Confidence: " . ucfirst(str_replace('_', ' ', $confidence)) . "</p>";
                            
                            if (!empty($rec['reasons'])) {
                                $content .= '<ul class="text-sm mt-2 list-disc list-inside text-gray-700">';
                                foreach ($rec['reasons'] as $reason) {
                                    $content .= "<li>{$reason}</li>";
                                }
                                $content .= '</ul>';
                            }
                            
                            $content .= '</div>';
                        }
                        
                        $content .= '</div>';
                        
                        return new \Illuminate\Support\HtmlString($content);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Tables\Actions\EditAction::make()
                    ->label('✏️ Manual Assign')
                    ->color('warning'),

                Action::make('remove_assignment')
                    ->label('🗑️ Remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn ($record) => $record->work_location_id)
                    ->requiresConfirmation()
                    ->modalHeading('Remove Work Location Assignment')
                    ->modalDescription('Are you sure you want to remove the work location assignment for this user?')
                    ->action(function ($record) {
                        $previousLocation = $record->workLocation?->name;
                        $record->work_location_id = null;
                        $record->save();
                        
                        // Create history record
                        AssignmentHistory::create([
                            'user_id' => $record->id,
                            'work_location_id' => $record->getOriginal('work_location_id'),
                            'previous_work_location_id' => $record->getOriginal('work_location_id'),
                            'assigned_by' => auth()->id(),
                            'assignment_method' => 'manual',
                            'assignment_reasons' => ['Assignment removed via admin panel'],
                            'metadata' => [
                                'action' => 'removed',
                                'removed_by' => auth()->user()->name,
                                'timestamp' => now()->toISOString()
                            ]
                        ]);
                        
                        Notification::make()
                            ->title('🗑️ Assignment Removed')
                            ->body("Removed work location assignment for {$record->name}")
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('bulk_smart_assign')
                    ->label('🧠 Smart Assign Selected')
                    ->icon('heroicon-o-cpu-chip')
                    ->color('success')
                    ->action(function (Collection $records) {
                        $service = app(SmartWorkLocationAssignmentService::class);
                        $result = $service->bulkIntelligentAssignment($records);
                        
                        Notification::make()
                            ->title('🎯 Bulk Smart Assignment Completed')
                            ->body("Successfully assigned {$result['successful']} users. {$result['failed']} failed.")
                            ->success()
                            ->duration(5000)
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('bulk_remove_assignment')
                    ->label('🗑️ Remove Assignments')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Bulk Remove Work Location Assignments')
                    ->modalDescription('Are you sure you want to remove work location assignments for the selected users?')
                    ->action(function (Collection $records) {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record->work_location_id) {
                                // Create history record
                                AssignmentHistory::create([
                                    'user_id' => $record->id,
                                    'work_location_id' => $record->work_location_id,
                                    'previous_work_location_id' => $record->work_location_id,
                                    'assigned_by' => auth()->id(),
                                    'assignment_method' => 'bulk',
                                    'assignment_reasons' => ['Bulk assignment removal via admin panel'],
                                    'metadata' => [
                                        'action' => 'bulk_removed',
                                        'removed_by' => auth()->user()->name,
                                        'timestamp' => now()->toISOString()
                                    ]
                                ]);
                                
                                $record->work_location_id = null;
                                $record->save();
                                $count++;
                            }
                        }
                        
                        Notification::make()
                            ->title('🗑️ Bulk Assignment Removal Completed')
                            ->body("Removed work location assignments for {$count} users")
                            ->warning()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('name')
            ->poll('30s') // Auto-refresh every 30 seconds
            ->heading('🎯 Smart Work Location Assignment System')
            ->description('AI-powered assignment system with intelligent matching algorithms');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkLocationAssignments::route('/'),
            'create' => Pages\CreateWorkLocationAssignment::route('/create'),
            'edit' => Pages\EditWorkLocationAssignment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return User::whereNull('work_location_id')->count() > 0 
            ? User::whereNull('work_location_id')->count() 
            : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return User::whereNull('work_location_id')->count() > 0 ? 'danger' : null;
    }
}