<?php

namespace App\Filament\Manajer\Resources\HighValueApprovalResource\Pages;

use App\Filament\Manajer\Resources\HighValueApprovalResource;
use App\Models\ManagerApproval;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListHighValueApprovals extends ListRecords
{
    protected static string $resource = HighValueApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approval_summary')
                ->label('📊 Summary')
                ->icon('heroicon-o-chart-pie')
                ->color('info')
                ->action(function () {
                    $pending = ManagerApproval::pending()->count();
                    $urgent = ManagerApproval::urgent()->pending()->count();
                    $overdue = ManagerApproval::overdue()->count();
                    $highValue = ManagerApproval::highValue()->pending()->count();
                    $totalAmount = ManagerApproval::pending()->sum('amount');
                    
                    $message = "📊 **APPROVAL DASHBOARD SUMMARY**\n\n";
                    $message .= "⏳ **PENDING APPROVALS: {$pending}**\n";
                    $message .= "🚨 Urgent: {$urgent}\n";
                    $message .= "⚠️ Overdue: {$overdue}\n";
                    $message .= "💰 High Value (>500K): {$highValue}\n";
                    $message .= "💵 Total Pending Amount: Rp " . number_format($totalAmount, 0, ',', '.');
                    
                    $this->notify('info', $message);
                }),
                
            Actions\CreateAction::make()
                ->label('📋 New Approval Request')
                ->icon('heroicon-o-plus-circle')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['requested_by'] = Auth::id();
                    $data['requester_role'] = Auth::user()?->roles?->first()?->name ?? 'manajer';
                    return $data;
                }),
        ];
    }

    public function getTitle(): string
    {
        return '✅ High-Value Approval Center';
    }

    public function getSubheading(): ?string
    {
        $pendingCount = ManagerApproval::pending()->count();
        $urgentCount = ManagerApproval::urgent()->pending()->count();
        $totalValue = ManagerApproval::pending()->sum('amount');
        
        return "Pending: {$pendingCount} | Urgent: {$urgentCount} | Total Value: Rp " . number_format($totalValue, 0, ',', '.');
    }
}