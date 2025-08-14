<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JumlahPasienHarian;
use App\Services\Jaspel\UnifiedJaspelCalculationService;

class FixMissingJaspelValues extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'fix:jaspel-values {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Fix missing jaspel_rupiah values by recalculating them using UnifiedJaspelCalculationService';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info('🔍 Scanning for records with missing jaspel_rupiah values...');
        
        // Find records with null or zero jaspel_rupiah that have patient data
        $records = JumlahPasienHarian::where(function($query) {
                $query->whereNull('jaspel_rupiah')
                      ->orWhere('jaspel_rupiah', 0);
            })
            ->where(function($query) {
                $query->where('jumlah_pasien_umum', '>', 0)
                      ->orWhere('jumlah_pasien_bpjs', '>', 0);
            })
            ->with(['dokter'])
            ->get();

        if ($records->isEmpty()) {
            $this->info('✅ No records found with missing jaspel values.');
            return 0;
        }

        $this->info("📊 Found {$records->count()} records that need jaspel calculation:");
        
        $calculationService = app(UnifiedJaspelCalculationService::class);
        $updated = 0;
        $errors = 0;

        foreach ($records as $record) {
            $doctorName = $record->dokter ? $record->dokter->nama_lengkap : 'Unknown Doctor';
            
            try {
                $calculation = $calculationService->calculateEstimated(
                    $record->jumlah_pasien_umum,
                    $record->jumlah_pasien_bpjs,
                    $record->shift ?? 'Pagi'
                );

                if (isset($calculation['error'])) {
                    $this->error("❌ ID {$record->id} ({$record->tanggal->format('d/m/Y')}) - {$doctorName}: {$calculation['error']}");
                    $errors++;
                    continue;
                }

                $jaspelAmount = $calculation['total'];
                
                if ($isDryRun) {
                    $this->line("🔄 [DRY-RUN] ID {$record->id} ({$record->tanggal->format('d/m/Y')}) - {$doctorName}: Would set jaspel to Rp " . number_format($jaspelAmount, 0, ',', '.'));
                } else {
                    $record->jaspel_rupiah = $jaspelAmount;
                    $record->save();
                    
                    $this->info("✅ ID {$record->id} ({$record->tanggal->format('d/m/Y')}) - {$doctorName}: Updated jaspel to Rp " . number_format($jaspelAmount, 0, ',', '.'));
                }
                
                $updated++;
                
            } catch (\Exception $e) {
                $this->error("❌ ID {$record->id} ({$record->tanggal->format('d/m/Y')}) - {$doctorName}: Exception - {$e->getMessage()}");
                $errors++;
            }
        }

        if ($isDryRun) {
            $this->warn("🔍 DRY-RUN completed. {$updated} records would be updated, {$errors} errors found.");
            $this->info("Run without --dry-run to actually update the records.");
        } else {
            $this->info("✅ Processing completed. {$updated} records updated, {$errors} errors found.");
        }

        return 0;
    }
}