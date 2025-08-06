<?php

namespace Tests\Feature\Api\V2;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\Jaspel;
use App\Models\Tindakan;
use App\Models\JadwalJaga;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class ParamedisMobileDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Pegawai $paramedis;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'paramedis',
            'email' => 'test.paramedis@dokterku.com'
        ]);
        
        $this->paramedis = Pegawai::factory()->create([
            'user_id' => $this->user->id,
            'jenis_pegawai' => 'Paramedis'
        ]);
    }

    /** @test */
    public function it_returns_comprehensive_dashboard_data_for_paramedis_user()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/paramedis/dashboard');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'role',
                            'paramedis_id'
                        ],
                        'jaspel' => [
                            'monthly',
                            'weekly',
                            'approved',
                            'pending',
                            'total_potential'
                        ],
                        'attendance' => [
                            'shifts_this_month',
                            'today'
                        ],
                        'quick_stats' => [
                            'total_tindakan',
                            'approved_tindakan',
                            'pending_tindakan',
                            'approval_rate'
                        ],
                        'meta' => [
                            'generated_at',
                            'version',
                            'controller'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_calculates_jaspel_data_correctly()
    {
        Sanctum::actingAs($this->user);
        
        // Create approved Jaspel records for this month
        Jaspel::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'tanggal' => Carbon::now(),
            'nominal' => 100000,
            'status_validasi' => 'approved'
        ]);
        
        // Create pending Jaspel record
        Jaspel::factory()->create([
            'user_id' => $this->user->id,
            'tanggal' => Carbon::now(),
            'nominal' => 50000,
            'status_validasi' => 'pending'
        ]);
        
        $response = $this->getJson('/api/paramedis/dashboard');
        
        $response->assertStatus(200);
        
        $jaspelData = $response->json('data.jaspel');
        
        // Should have 300000 in approved (3 x 100000)
        $this->assertEquals(300000, $jaspelData['approved']);
        $this->assertEquals(300000, $jaspelData['monthly']);
        
        // Should have 50000 in pending
        $this->assertEquals(50000, $jaspelData['pending']);
        
        // Total potential should be approved + pending
        $this->assertEquals(350000, $jaspelData['total_potential']);
    }

    /** @test */
    public function it_calculates_pending_jaspel_from_approved_tindakan()
    {
        Sanctum::actingAs($this->user);
        
        // Create approved Tindakan without corresponding Jaspel
        Tindakan::factory()->create([
            'paramedis_id' => $this->paramedis->id,
            'tanggal_tindakan' => Carbon::now(),
            'jasa_paramedis' => 200000,
            'status_validasi' => 'approved'
        ]);
        
        $response = $this->getJson('/api/paramedis/dashboard');
        
        $response->assertStatus(200);
        
        $jaspelData = $response->json('data.jaspel');
        
        // Should calculate 15% of 200000 = 30000 as pending
        $this->assertEquals(30000, $jaspelData['pending']);
    }

    /** @test */
    public function it_returns_attendance_information()
    {
        Sanctum::actingAs($this->user);
        
        // Create schedule for this month
        JadwalJaga::factory()->count(5)->create([
            'pegawai_id' => $this->user->id,
            'tanggal_jaga' => Carbon::now()
        ]);
        
        // Create today's attendance
        $todayAttendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'time_in' => '08:00:00',
            'time_out' => null
        ]);
        
        $response = $this->getJson('/api/paramedis/dashboard');
        
        $response->assertStatus(200);
        
        $attendanceData = $response->json('data.attendance');
        
        $this->assertEquals(5, $attendanceData['shifts_this_month']);
        $this->assertNotNull($attendanceData['today']);
        $this->assertEquals($todayAttendance->id, $attendanceData['today']['id']);
    }

    /** @test */
    public function it_calculates_quick_statistics()
    {
        Sanctum::actingAs($this->user);
        
        // Create Tindakan records
        Tindakan::factory()->count(8)->create([
            'paramedis_id' => $this->paramedis->id,
            'tanggal_tindakan' => Carbon::now(),
            'status_validasi' => 'approved'
        ]);
        
        Tindakan::factory()->count(2)->create([
            'paramedis_id' => $this->paramedis->id,
            'tanggal_tindakan' => Carbon::now(),
            'status_validasi' => 'pending'
        ]);
        
        $response = $this->getJson('/api/paramedis/dashboard');
        
        $response->assertStatus(200);
        
        $quickStats = $response->json('data.quick_stats');
        
        $this->assertEquals(10, $quickStats['total_tindakan']);
        $this->assertEquals(8, $quickStats['approved_tindakan']);
        $this->assertEquals(2, $quickStats['pending_tindakan']);
        $this->assertEquals(80.0, $quickStats['approval_rate']);
    }

    /** @test */
    public function it_requires_paramedis_user()
    {
        // Create non-paramedis user
        $doctorUser = User::factory()->create(['role' => 'dokter']);
        Sanctum::actingAs($doctorUser);
        
        $response = $this->getJson('/api/paramedis/dashboard');
        
        $response->assertStatus(404)
                ->assertJson([
                    'error' => 'Paramedis data not found'
                ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/paramedis/dashboard');
        
        $response->assertStatus(401)
                ->assertJson([
                    'error' => 'Not authenticated'
                ]);
    }

    /** @test */
    public function it_handles_zero_jaspel_gracefully()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/paramedis/dashboard');
        
        $response->assertStatus(200);
        
        $jaspelData = $response->json('data.jaspel');
        
        $this->assertEquals(0, $jaspelData['monthly']);
        $this->assertEquals(0, $jaspelData['weekly']);
        $this->assertEquals(0, $jaspelData['approved']);
        $this->assertEquals(0, $jaspelData['pending']);
        $this->assertEquals(0, $jaspelData['total_potential']);
    }

    /** @test */
    public function it_filters_jaspel_by_current_month()
    {
        Sanctum::actingAs($this->user);
        
        // Create Jaspel for this month
        Jaspel::factory()->create([
            'user_id' => $this->user->id,
            'tanggal' => Carbon::now(),
            'nominal' => 100000,
            'status_validasi' => 'approved'
        ]);
        
        // Create Jaspel for last month (should not be included)
        Jaspel::factory()->create([
            'user_id' => $this->user->id,
            'tanggal' => Carbon::now()->subMonth(),
            'nominal' => 200000,
            'status_validasi' => 'approved'
        ]);
        
        $response = $this->getJson('/api/paramedis/dashboard');
        
        $response->assertStatus(200);
        
        $jaspelData = $response->json('data.jaspel');
        
        // Should only include this month's Jaspel (100000, not 300000)
        $this->assertEquals(100000, $jaspelData['monthly']);
    }

    /** @test */
    public function it_includes_metadata_in_response()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/paramedis/dashboard');
        
        $response->assertStatus(200);
        
        $meta = $response->json('data.meta');
        
        $this->assertArrayHasKey('generated_at', $meta);
        $this->assertEquals('2.0', $meta['version']);
        $this->assertEquals('ParamedisMobileDashboardController', $meta['controller']);
    }

    /** @test */
    public function it_handles_user_without_attendance_today()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/paramedis/dashboard');
        
        $response->assertStatus(200);
        
        $attendanceData = $response->json('data.attendance');
        
        $this->assertNull($attendanceData['today']);
        $this->assertEquals(0, $attendanceData['shifts_this_month']);
    }
}