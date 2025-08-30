<?php

namespace App\Filament\Resources\AdminProfileResource\Pages;

use App\Filament\Resources\AdminProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListAdminProfiles extends ListRecords
{
    protected static string $resource = AdminProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit_profile')
                ->label('Edit Profile')
                ->icon('heroicon-o-pencil')
                ->color('primary')
                ->url(fn () => AdminProfileResource::getUrl('edit', ['record' => Auth::id()])),
        ];
    }
    
    public function getTitle(): string
    {
        return 'My Profile';
    }
    
    public function getSubheading(): ?string
    {
        return 'Manage your account settings and preferences';
    }
    
    // Automatically redirect to edit page since there's only one profile
    public function mount(): void
    {
        parent::mount();
        
        // Auto-redirect to edit page for better UX
        $this->redirect(AdminProfileResource::getUrl('edit', ['record' => Auth::id()]));
    }
}