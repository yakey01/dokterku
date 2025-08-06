<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WorkLocation;
use App\Services\WorkLocationDeletionService;

class TestWorkLocationDeletion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'work-location:test-deletion {location_id? : ID of work location to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test work location deletion service functionality';

    /**
     * Execute the console command.
     */
    public function handle(WorkLocationDeletionService $service)
    {
        $this->info('🧪 Testing Work Location Deletion Service');
        $this->newLine();

        $locationId = $this->argument('location_id');
        
        if ($locationId) {
            $location = WorkLocation::find($locationId);
            if (!$location) {
                $this->error("Work location with ID {$locationId} not found.");
                return 1;
            }
        } else {
            // Get the first available location
            $location = WorkLocation::first();
            if (!$location) {
                $this->error('No work locations found in the database.');
                return 1;
            }
        }

        $this->info("Testing deletion for: {$location->name} (ID: {$location->id})");
        $this->newLine();

        // Get deletion preview
        $this->info('📋 Getting deletion preview...');
        $preview = $service->getDeletePreview($location);
        
        $this->table(
            ['Property', 'Value'],
            [
                ['Location Name', $preview['work_location']['name']],
                ['Location Type', $preview['work_location']['location_type']],
                ['Unit Kerja', $preview['work_location']['unit_kerja'] ?? 'Not Set'],
                ['Can Delete', $preview['dependencies']['can_delete'] ? '✅ Yes' : '❌ No'],
                ['Assigned Users', $preview['dependencies']['assigned_users_count']],
                ['Assignment Histories', $preview['dependencies']['assignment_histories_count']],
                ['Attendances', $preview['dependencies']['attendances_count']],
                ['Location Validations', $preview['dependencies']['location_validations_count']],
            ]
        );

        if (!empty($preview['dependencies']['blocking_dependencies'])) {
            $this->newLine();
            $this->error('🚫 Blocking Dependencies:');
            foreach ($preview['dependencies']['blocking_dependencies'] as $dependency) {
                $this->line("  • {$dependency}");
            }
        }

        if (!empty($preview['dependencies']['warnings'])) {
            $this->newLine();
            $this->warn('⚠️  Warnings:');
            foreach ($preview['dependencies']['warnings'] as $warning) {
                $this->line("  • {$warning}");
            }
        }

        if (!empty($preview['recommendations'])) {
            $this->newLine();
            $this->info('💡 Recommendations:');
            foreach ($preview['recommendations'] as $rec) {
                $icon = match($rec['type']) {
                    'error' => '❌',
                    'warning' => '⚠️',
                    default => '✅'
                };
                $this->line("  {$icon} {$rec['message']}");
            }
        }

        if (!empty($preview['alternative_locations'])) {
            $this->newLine();
            $this->info('🔄 Alternative Locations:');
            
            $altTable = collect($preview['alternative_locations'])->take(3)->map(function ($alt) {
                return [
                    $alt['name'],
                    $alt['location_type'],
                    $alt['unit_kerja'],
                    $alt['current_users'],
                    $alt['utilization_percentage'] . '%',
                    $alt['same_unit_kerja'] ? '✅' : '❌',
                    $alt['recommendation_score']
                ];
            })->toArray();
            
            $this->table(
                ['Name', 'Type', 'Unit', 'Users', 'Util%', 'Same Unit', 'Score'],
                $altTable
            );
        }

        $this->newLine();
        $this->info("📊 Estimated Impact: {$preview['estimated_impact']['severity']} severity");
        
        if ($preview['dependencies']['can_delete']) {
            if ($this->confirm('Would you like to perform a test deletion (this will actually delete the location)?', false)) {
                $this->info('🗑️  Performing safe deletion...');
                
                try {
                    $result = $service->safeDelete($location, [
                        'reassign_users' => true,
                        'preserve_history' => true,
                        'reason' => 'CLI testing deletion functionality',
                        'assigned_by' => 1, // Assuming admin user ID 1 exists
                    ]);
                    
                    if ($result['success']) {
                        $this->info('✅ ' . $result['message']);
                        $this->table(
                            ['Metric', 'Count'],
                            [
                                ['Users Reassigned', $result['data']['users_reassigned']],
                                ['Data Archived', $result['data']['data_archived']],
                                ['Deletion Type', $result['data']['deleted_location']['deletion_type']],
                            ]
                        );
                    } else {
                        $this->error('❌ ' . $result['message']);
                        return 1;
                    }
                    
                } catch (\Exception $e) {
                    $this->error('❌ Deletion failed: ' . $e->getMessage());
                    return 1;
                }
            }
        } else {
            $this->warn('🚫 Cannot perform deletion due to blocking dependencies.');
        }

        $this->newLine();
        $this->info('✅ Test completed successfully!');
        return 0;
    }
}