<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JumlahPasienHarian;
use App\Models\Jaspel;
use App\Services\ValidatedJaspelCalculationService;
use App\Constants\ValidationStatus;
use App\Models\User;
use App\Models\Dokter;
use Illuminate\Support\Facades\DB;

class TestValidationStatusFix extends Command
{
    protected $signature = 'test:validation-fix {--fix : Apply the migration to standardize statuses}';
    protected $description = 'Test the validation status fix and verify data integrity';

    public function handle()
    {
        $this->info('🔍 Testing Validation Status Fix');
        $this->newLine();
        
        // Step 1: Check current status distribution
        $this->checkStatusDistribution();
        
        // Step 2: Test ValidationStatus constants
        $this->testValidationConstants();
        
        // Step 3: Test JASPEL calculation with mixed statuses
        $this->testJaspelCalculation();
        
        // Step 4: Test access control
        $this->testAccessControl();
        
        // Step 5: Apply fix if requested
        if ($this->option('fix')) {
            $this->applyFix();
        }
        
        $this->newLine();
        $this->info('✅ Validation status fix test completed!');
    }
    
    private function checkStatusDistribution()
    {
        $this->info('📊 Current Status Distribution:');
        $this->newLine();
        
        $tables = [
            'jumlah_pasien_harians' => JumlahPasienHarian::class,
            'jaspels' => Jaspel::class,
        ];
        
        foreach ($tables as $table => $model) {
            $this->line("Table: {$table}");
            
            $distribution = DB::table($table)
                ->select('status_validasi', DB::raw('COUNT(*) as count'))
                ->groupBy('status_validasi')
                ->get();
            
            $total = $distribution->sum('count');
            
            $this->table(
                ['Status', 'Count', 'Percentage'],
                $distribution->map(function($item) use ($total) {
                    $percentage = $total > 0 ? round(($item->count / $total) * 100, 2) : 0;
                    return [
                        $item->status_validasi ?? 'NULL',
                        $item->count,
                        "{$percentage}%"
                    ];
                })
            );
            
            // Check for problematic statuses
            $legacyCount = $distribution->whereIn('status_validasi', ['disetujui', 'ditolak'])->sum('count');
            if ($legacyCount > 0) {
                $this->warn("⚠️  Found {$legacyCount} records with legacy status values");
            }
            
            $this->newLine();
        }
    }
    
    private function testValidationConstants()
    {
        $this->info('🧪 Testing ValidationStatus Constants:');
        $this->newLine();
        
        // Test approved statuses
        $approvedStatuses = ValidationStatus::approvedStatuses();
        $this->line('Approved statuses: ' . implode(', ', $approvedStatuses));
        
        // Test normalization
        $tests = [
            'disetujui' => 'approved',
            'approved' => 'approved',
            'ditolak' => 'rejected',
            'rejected' => 'rejected',
            'pending' => 'pending',
        ];
        
        $this->line('Testing normalization:');
        foreach ($tests as $input => $expected) {
            $result = ValidationStatus::normalize($input);
            $status = $result === $expected ? '✅' : '❌';
            $this->line("  {$status} normalize('{$input}') = '{$result}' (expected: '{$expected}')");
        }
        
        // Test approval check
        $this->line('Testing isApproved:');
        foreach (['approved', 'disetujui', 'rejected', 'pending', null] as $status) {
            $isApproved = ValidationStatus::isApproved($status);
            $this->line("  isApproved('{$status}') = " . ($isApproved ? 'true' : 'false'));
        }
        
        $this->newLine();
    }
    
    private function testJaspelCalculation()
    {
        $this->info('💰 Testing JASPEL Calculation with Mixed Statuses:');
        $this->newLine();
        
        // Find a doctor with both types of approved records
        $doctorWithMixedRecords = DB::table('jumlah_pasien_harians as jph')
            ->join('dokters as d', 'jph.dokter_id', '=', 'd.id')
            ->whereIn('jph.status_validasi', ['approved', 'disetujui'])
            ->select('d.id', 'd.user_id', 'd.nama_lengkap')
            ->groupBy('d.id', 'd.user_id', 'd.nama_lengkap')
            ->first();
        
        if (!$doctorWithMixedRecords) {
            $this->warn('No doctor found with mixed status records for testing');
            return;
        }
        
        $user = User::find($doctorWithMixedRecords->user_id);
        if (!$user) {
            $this->warn('User not found for doctor');
            return;
        }
        
        $this->line("Testing with doctor: {$doctorWithMixedRecords->nama_lengkap}");
        
        // Get records before fix
        $approvedCount = JumlahPasienHarian::where('dokter_id', $doctorWithMixedRecords->id)
            ->where('status_validasi', 'approved')
            ->count();
            
        $disetujuiCount = JumlahPasienHarian::where('dokter_id', $doctorWithMixedRecords->id)
            ->where('status_validasi', 'disetujui')
            ->count();
            
        $this->line("Records with 'approved': {$approvedCount}");
        $this->line("Records with 'disetujui': {$disetujuiCount}");
        
        // Test calculation service
        try {
            $service = app(ValidatedJaspelCalculationService::class);
            $result = $service->getValidatedJaspelData($user);
            
            $this->line("JASPEL calculation result:");
            $this->line("  Total items: " . count($result['items']));
            $this->line("  Total amount: Rp " . number_format($result['summary']['total'], 0, ',', '.'));
            
            // Check if all approved records are included
            $expectedTotal = $approvedCount + $disetujuiCount;
            if (count($result['items']) >= $expectedTotal) {
                $this->info("✅ All approved records are included in calculation");
            } else {
                $this->warn("⚠️  Some approved records might be missing");
            }
            
        } catch (\Exception $e) {
            $this->error("Error in JASPEL calculation: " . $e->getMessage());
        }
        
        $this->newLine();
    }
    
    private function testAccessControl()
    {
        $this->info('🔐 Testing Access Control:');
        $this->newLine();
        
        // Find a bendahara user
        $bendahara = User::whereHas('roles', function($q) {
            $q->where('name', 'bendahara');
        })->first();
        
        if ($bendahara) {
            $this->line("Testing with bendahara user: {$bendahara->name}");
            
            // Simulate auth
            auth()->login($bendahara);
            
            // Test ValidasiJumlahPasienResource access
            $canAccess = \App\Filament\Bendahara\Resources\ValidasiJumlahPasienResource::canAccess();
            $this->line("  ValidasiJumlahPasienResource::canAccess() = " . ($canAccess ? 'true ✅' : 'false ❌'));
            
            auth()->logout();
        } else {
            $this->warn('No bendahara user found for testing');
        }
        
        // Test with non-bendahara user
        $nonBendahara = User::whereDoesntHave('roles', function($q) {
            $q->where('name', 'bendahara');
        })->first();
        
        if ($nonBendahara) {
            $this->line("Testing with non-bendahara user: {$nonBendahara->name}");
            
            auth()->login($nonBendahara);
            
            $canAccess = \App\Filament\Bendahara\Resources\ValidasiJumlahPasienResource::canAccess();
            $this->line("  ValidasiJumlahPasienResource::canAccess() = " . ($canAccess ? 'true ❌' : 'false ✅'));
            
            auth()->logout();
        }
        
        $this->newLine();
    }
    
    private function applyFix()
    {
        $this->info('🔧 Applying Status Standardization Fix...');
        $this->newLine();
        
        if (!$this->confirm('This will update all legacy status values. Continue?')) {
            return;
        }
        
        $tables = [
            'jumlah_pasien_harians',
            'jaspels',
            'tindakans',
            'pendapatan_harians',
            'pengeluaran_harians',
        ];
        
        DB::transaction(function() use ($tables) {
            foreach ($tables as $table) {
                if (!Schema::hasTable($table)) {
                    continue;
                }
                
                // Update disetujui to approved
                $updated = DB::table($table)
                    ->where('status_validasi', 'disetujui')
                    ->update(['status_validasi' => 'approved']);
                    
                if ($updated > 0) {
                    $this->line("Updated {$updated} records in {$table} from 'disetujui' to 'approved'");
                }
                
                // Update ditolak to rejected
                $updated = DB::table($table)
                    ->where('status_validasi', 'ditolak')
                    ->update(['status_validasi' => 'rejected']);
                    
                if ($updated > 0) {
                    $this->line("Updated {$updated} records in {$table} from 'ditolak' to 'rejected'");
                }
            }
        });
        
        $this->newLine();
        $this->info('✅ Status standardization completed!');
        
        // Show new distribution
        $this->newLine();
        $this->checkStatusDistribution();
    }
}