<?php

namespace App\Filament\Manajer\Resources\StrategicGoalResource\Pages;

use App\Filament\Manajer\Resources\StrategicGoalResource;
use App\Models\StrategicGoal;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListStrategicGoals extends ListRecords
{
    protected static string $resource = StrategicGoalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('analytics')
                ->label('📊 Analytics')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->url(fn (): string => route('filament.manajer.resources.strategic-goals.analytics')),
                
            Actions\CreateAction::make()
                ->label('🎯 New Strategic Goal')
                ->icon('heroicon-o-plus-circle')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['created_by'] = Auth::id();
                    return $data;
                }),
        ];
    }

    public function getTitle(): string
    {
        return '🎯 Strategic Goals Management';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Add strategic overview widget here
        ];
    }

    public function getSubheading(): ?string
    {
        $activeCount = StrategicGoal::active()->count();
        $overdueCount = StrategicGoal::overdue()->count();
        $completedThisMonth = StrategicGoal::where('status', 'completed')
            ->whereMonth('completed_at', now()->month)
            ->count();

        return "Active Goals: {$activeCount} | Overdue: {$overdueCount} | Completed This Month: {$completedThisMonth}";
    }
}