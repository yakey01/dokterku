<?php

namespace App\Filament\Petugas\Resources\PasienResource\Pages;

use App\Filament\Petugas\Resources\PasienResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPasiens extends ListRecords
{
    protected static string $resource = PasienResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('➕ Input Pasien Baru')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->size('lg')
                ->visible(fn (): bool => true),
        ];
    }

    protected function getTableEmptyStateActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('➕ Input Pasien Baru')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->size('lg'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua Pasien')
                ->icon('heroicon-o-users')
                ->badge(fn () => $this->getModel()::where('input_by', auth()->id())->count()),
            
            'pending' => Tab::make('Menunggu Verifikasi')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => $this->getModel()::where('input_by', auth()->id())->where('status', 'pending')->count())
                ->badgeColor('warning'),
            
            'verified' => Tab::make('Terverifikasi')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'verified'))
                ->badge(fn () => $this->getModel()::where('input_by', auth()->id())->where('status', 'verified')->count())
                ->badgeColor('success'),
            
            'rejected' => Tab::make('Ditolak')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected'))
                ->badge(fn () => $this->getModel()::where('input_by', auth()->id())->where('status', 'rejected')->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-user-plus';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'Belum ada data pasien';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Mulai dengan menambahkan data pasien baru menggunakan tombol "Input Pasien Baru" di atas.';
    }

}