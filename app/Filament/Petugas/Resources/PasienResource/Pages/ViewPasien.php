<?php

namespace App\Filament\Petugas\Resources\PasienResource\Pages;

use App\Filament\Petugas\Resources\PasienResource;
use App\Filament\Petugas\Resources\TindakanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewPasien extends ViewRecord
{
    protected static string $resource = PasienResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_tindakan')
                ->label('🏥 Buat Tindakan')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->url(fn (): string => TindakanResource::getUrl('create', [], panel: 'petugas') . '?pasien_id=' . $this->record->id),
            Actions\EditAction::make()
                ->label('✏️ Edit Data'),
            Actions\DeleteAction::make()
                ->label('🗑️ Hapus'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('📋 Informasi Pasien')
                    ->schema([
                        Infolists\Components\TextEntry::make('no_rekam_medis')
                            ->label('No. Rekam Medis')
                            ->icon('heroicon-o-identification')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('nama')
                            ->label('Nama Lengkap')
                            ->icon('heroicon-o-user')
                            ->size('lg')
                            ->weight('semibold'),
                        Infolists\Components\TextEntry::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->icon('heroicon-o-calendar-days')
                            ->date('d/m/Y')
                            ->suffix(fn ($record) => $record->umur ? ' (' . $record->umur . ' tahun)' : ''),
                        Infolists\Components\TextEntry::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'L' => '👨 Laki-laki',
                                'P' => '👩 Perempuan',
                            })
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'L' => 'info',
                                'P' => 'success',
                            }),
                    ])
                    ->columns(2),


                Infolists\Components\Section::make('💼 Informasi Personal')
                    ->schema([
                        Infolists\Components\TextEntry::make('pekerjaan')
                            ->label('Pekerjaan')
                            ->icon('heroicon-o-briefcase')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('status_pernikahan')
                            ->label('Status Pernikahan')
                            ->formatStateUsing(fn (string $state = null): string => match ($state) {
                                'belum_menikah' => '👤 Belum Menikah',
                                'menikah' => '💑 Menikah',
                                'janda' => '👩 Janda',
                                'duda' => '👨 Duda',
                                default => '-',
                            })
                            ->badge()
                            ->color(fn (string $state = null): string => match ($state) {
                                'belum_menikah' => 'gray',
                                'menikah' => 'success',
                                'janda' => 'warning',
                                'duda' => 'info',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('ℹ️ Status & Verifikasi')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status Verifikasi')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'pending' => 'Menunggu Verifikasi',
                                'verified' => 'Terverifikasi',
                                'rejected' => 'Ditolak',
                                default => 'Menunggu Verifikasi',
                            })
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'verified' => 'success',
                                'rejected' => 'danger',
                                default => 'warning',
                            }),
                        Infolists\Components\TextEntry::make('verified_at')
                            ->label('Tanggal Verifikasi')
                            ->icon('heroicon-o-check-circle')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Belum diverifikasi'),
                        Infolists\Components\TextEntry::make('inputBy.name')
                            ->label('Diinput Oleh')
                            ->icon('heroicon-o-user')
                            ->placeholder('Tidak diketahui'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Tanggal Pendaftaran')
                            ->icon('heroicon-o-calendar-days')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('🏥 Riwayat Tindakan')
                    ->schema([
                        Infolists\Components\TextEntry::make('tindakan_count')
                            ->label('Total Tindakan')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->numeric(),
                        Infolists\Components\TextEntry::make('lastTindakan.tanggal_tindakan')
                            ->label('Tindakan Terakhir')
                            ->icon('heroicon-o-clock')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Belum ada tindakan'),
                    ])
                    ->columns(2),
            ]);
    }
}