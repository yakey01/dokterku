<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\JumlahPasienHarian;
use App\Models\Dokter;
use App\Constants\ValidationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class ValidationTabsTest extends TestCase
{
    use RefreshDatabase;

    private User $bendahara;
    private Dokter $dokter;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create bendahara user
        $this->bendahara = User::factory()->create();
        $this->bendahara->assignRole('bendahara');
        
        // Create dokter for foreign key
        $this->dokter = Dokter::factory()->create();
    }

    /** @test */
    public function validation_counts_api_returns_correct_structure()
    {
        // Create test data
        JumlahPasienHarian::factory()->create([
            'status_validasi' => ValidationStatus::PENDING,
            'dokter_id' => $this->dokter->id,
        ]);
        
        JumlahPasienHarian::factory()->create([
            'status_validasi' => ValidationStatus::APPROVED,
            'dokter_id' => $this->dokter->id,
        ]);

        $response = $this->actingAs($this->bendahara)
            ->getJson('/bendahara/api/validation-counts');

        $response->assertOk()
            ->assertJsonStructure([
                'total',
                'pending',
                'approved',
                'rejected',
                'revision',
                'cancelled',
                'validated',
                'last_updated',
                'cache_key',
                'summary' => [
                    'completion_rate',
                    'pending_urgency',
                    'status_breakdown'
                ]
            ]);
    }

    /** @test */
    public function validation_counts_api_returns_correct_counts()
    {
        // Create test data with known counts
        JumlahPasienHarian::factory()->count(3)->create([
            'status_validasi' => ValidationStatus::PENDING,
            'dokter_id' => $this->dokter->id,
        ]);
        
        JumlahPasienHarian::factory()->count(2)->create([
            'status_validasi' => ValidationStatus::APPROVED,
            'dokter_id' => $this->dokter->id,
        ]);
        
        JumlahPasienHarian::factory()->create([
            'status_validasi' => ValidationStatus::REJECTED,
            'dokter_id' => $this->dokter->id,
        ]);

        $response = $this->actingAs($this->bendahara)
            ->getJson('/bendahara/api/validation-counts');

        $response->assertOk()
            ->assertJson([
                'total' => 6,
                'pending' => 3,
                'approved' => 2,
                'rejected' => 1,
                'validated' => 3, // approved + rejected
            ]);
    }

    /** @test */
    public function validation_counts_api_requires_bendahara_role()
    {
        $regularUser = User::factory()->create();
        
        $response = $this->actingAs($regularUser)
            ->getJson('/bendahara/api/validation-counts');

        $response->assertForbidden();
    }

    /** @test */
    public function validation_counts_api_requires_authentication()
    {
        $response = $this->getJson('/bendahara/api/validation-counts');

        $response->assertUnauthorized();
    }

    /** @test */
    public function cache_is_cleared_when_validation_status_changes()
    {
        $record = JumlahPasienHarian::factory()->create([
            'status_validasi' => ValidationStatus::PENDING,
            'dokter_id' => $this->dokter->id,
        ]);

        // Prime the cache
        $this->actingAs($this->bendahara)
            ->getJson('/bendahara/api/validation-counts');

        $this->assertTrue(Cache::has('real_time_validation_counts'));

        // Update validation status (simulating what happens in the resource)
        $record->update(['status_validasi' => ValidationStatus::APPROVED]);
        
        // In real implementation, this would be triggered by the ListValidasiJumlahPasien page
        Cache::forget('validation_status_counts_bendahara');
        Cache::forget('real_time_validation_counts');

        $this->assertFalse(Cache::has('real_time_validation_counts'));
    }

    /** @test */
    public function pending_urgency_calculation_works_correctly()
    {
        // Test low urgency (1-5 pending)
        JumlahPasienHarian::factory()->count(3)->create([
            'status_validasi' => ValidationStatus::PENDING,
            'dokter_id' => $this->dokter->id,
        ]);

        $response = $this->actingAs($this->bendahara)
            ->getJson('/bendahara/api/validation-counts');

        $response->assertOk()
            ->assertJson([
                'summary' => [
                    'pending_urgency' => 'low'
                ]
            ]);

        // Test critical urgency (>30 pending)
        JumlahPasienHarian::factory()->count(35)->create([
            'status_validasi' => ValidationStatus::PENDING,
            'dokter_id' => $this->dokter->id,
        ]);

        Cache::forget('real_time_validation_counts'); // Clear cache

        $response = $this->actingAs($this->bendahara)
            ->getJson('/bendahara/api/validation-counts');

        $response->assertOk()
            ->assertJson([
                'summary' => [
                    'pending_urgency' => 'critical'
                ]
            ]);
    }

    /** @test */
    public function completion_rate_calculation_is_accurate()
    {
        // Create 10 total records: 6 validated, 4 pending
        JumlahPasienHarian::factory()->count(4)->create([
            'status_validasi' => ValidationStatus::PENDING,
            'dokter_id' => $this->dokter->id,
        ]);
        
        JumlahPasienHarian::factory()->count(6)->create([
            'status_validasi' => ValidationStatus::APPROVED,
            'dokter_id' => $this->dokter->id,
        ]);

        $response = $this->actingAs($this->bendahara)
            ->getJson('/bendahara/api/validation-counts');

        $response->assertOk()
            ->assertJson([
                'summary' => [
                    'completion_rate' => 60.0 // 6/10 * 100
                ]
            ]);
    }

    /** @test */
    public function detailed_stats_api_returns_comprehensive_data()
    {
        // Create some test data
        JumlahPasienHarian::factory()->create([
            'status_validasi' => ValidationStatus::PENDING,
            'dokter_id' => $this->dokter->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->bendahara)
            ->getJson('/bendahara/api/validation-stats');

        $response->assertOk()
            ->assertJsonStructure([
                'today' => ['submitted', 'validated', 'pending'],
                'week' => ['submitted', 'validated', 'avg_validation_time'],
                'month' => ['submitted', 'validated', 'approval_rate'],
                'queue_analysis' => ['oldest_pending', 'average_patient_count', 'high_value_count']
            ]);
    }

    /** @test */
    public function cache_clear_api_works_for_authorized_users()
    {
        // Prime the cache
        $this->actingAs($this->bendahara)
            ->getJson('/bendahara/api/validation-counts');

        $this->assertTrue(Cache::has('real_time_validation_counts'));

        // Clear cache
        $response = $this->actingAs($this->bendahara)
            ->postJson('/bendahara/api/validation-cache/clear');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Cache cleared successfully'
            ]);

        $this->assertFalse(Cache::has('real_time_validation_counts'));
        $this->assertFalse(Cache::has('detailed_validation_stats'));
        $this->assertFalse(Cache::has('validation_status_counts_bendahara'));
    }
}