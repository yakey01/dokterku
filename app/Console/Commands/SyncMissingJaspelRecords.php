<?php

namespace App\Console\Commands;

use App\Models\Tindakan;
use App\Models\Jaspel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncMissingJaspelRecords extends Command
{
    protected $signature = 'jaspel:sync-missing {--dry-run : Show what would be created without actually creating}';
    protected $description = 'Sync missing JASPEL records for approved tindakan';

    public function handle()
    {
        $this->info('🔍 Scanning for approved tindakan without JASPEL records...');
        
        // Find approved tindakan without corresponding jaspel
        $missingJaspelTindakan = Tindakan::where('status_validasi', 'disetujui')
            ->whereHas('dokter')
            ->whereDoesntHave('jaspel')
            ->with(['dokter.user', 'jenisTindakan'])
            ->get();
        
        $this->info("Found {$missingJaspelTindakan->count()} tindakan without JASPEL records");
        
        if ($missingJaspelTindakan->isEmpty()) {
            $this->info('✅ All approved tindakan already have JASPEL records');
            return 0;
        }
        
        $created = 0;
        $failed = 0;
        
        foreach ($missingJaspelTindakan as $tindakan) {
            try {
                $dokterUser = $tindakan->dokter->user;
                if (!$dokterUser) {
                    $this->warn("⚠️ Skipping tindakan {$tindakan->id}: No user found for dokter");
                    $failed++;
                    continue;
                }
                
                // Calculate JASPEL amount
                $jaspelAmount = $this->calculateJaspelAmount($tindakan);
                if ($jaspelAmount <= 0) {
                    $this->warn("⚠️ Skipping tindakan {$tindakan->id}: No JASPEL amount calculated");
                    $failed++;
                    continue;
                }
                
                $jaspelData = [
                    'user_id' => $dokterUser->id,
                    'tindakan_id' => $tindakan->id,
                    'tanggal' => $tindakan->tanggal_tindakan->format('Y-m-d'),
                    'jenis_jaspel' => $this->determineJaspelCategory($tindakan),
                    'nominal' => $jaspelAmount,
                    'persentase' => $tindakan->jenisTindakan->persentase_jaspel ?? 40,
                    'keterangan' => "AUTO-SYNC: {$tindakan->jenisTindakan->nama}",
                    'status_validasi' => 'disetujui',
                    'validasi_at' => $tindakan->validated_at ?: now(),
                    'validasi_by' => $tindakan->validated_by ?: 1,
                    'input_by' => $tindakan->input_by ?: $dokterUser->id,
                ];
                
                if ($this->option('dry-run')) {
                    $this->line("📋 Would create JASPEL for tindakan {$tindakan->id}: {$tindakan->jenisTindakan->nama} (Rp " . number_format($jaspelAmount) . ")");
                } else {
                    // Create JASPEL record
                    $jaspel = Jaspel::create($jaspelData);
                    $this->info("✅ Created JASPEL ID {$jaspel->id} for tindakan {$tindakan->id}: {$tindakan->jenisTindakan->nama} (Rp " . number_format($jaspelAmount) . ")");
                    $created++;
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Failed to create JASPEL for tindakan {$tindakan->id}: " . $e->getMessage());
                $failed++;
            }
        }
        
        if (!$this->option('dry-run')) {
            $this->info("\n📊 Summary:");
            $this->info("✅ Created: {$created} JASPEL records");
            if ($failed > 0) {
                $this->warn("❌ Failed: {$failed} records");
            }
        } else {
            $this->info("\n📋 Dry run complete. Remove --dry-run to actually create the records.");
        }
        
        return 0;
    }
    
    private function calculateJaspelAmount(Tindakan $tindakan): float
    {
        // Priority: use actual jasa_dokter from tindakan
        if ($tindakan->jasa_dokter > 0) {
            return $tindakan->jasa_dokter;
        }
        
        // Fallback: calculate from tarif and persentase
        if ($tindakan->jenisTindakan && $tindakan->jenisTindakan->persentase_jaspel > 0) {
            return $tindakan->tarif * ($tindakan->jenisTindakan->persentase_jaspel / 100);
        }
        
        return 0;
    }
    
    private function determineJaspelCategory(Tindakan $tindakan): string
    {
        if ($tindakan->dokter_id && $tindakan->jasa_dokter > 0) {
            return 'dokter_umum';
        }
        
        if ($tindakan->paramedis_id && $tindakan->jasa_paramedis > 0) {
            return 'paramedis';
        }
        
        // Default to dokter if dokter_id exists
        return $tindakan->dokter_id ? 'dokter_umum' : 'paramedis';
    }
}
