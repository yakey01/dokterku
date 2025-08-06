<?php

namespace Tests\Feature\Api\V2;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Pegawai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Pegawai $paramedis;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user and paramedis
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
    public function it_returns_attendance_status_for_authenticated_user()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/paramedis/attendance/status');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'has_checked_in',
                        'has_checked_out',
                        'can_check_in',
                        'can_check_out',
                        'attendance'
                    ],
                    'meta' => [
                        'date',
                        'timestamp',
                        'user_id'
                    ]
                ]);
        
        $response->assertJson([
            'success' => true,
            'data' => [
                'has_checked_in' => false,
                'has_checked_out' => false,
                'can_check_in' => true,
                'can_check_out' => false,
                'attendance' => null
            ]
        ]);
    }

    /** @test */
    public function it_returns_correct_status_when_user_has_checked_in()
    {
        Sanctum::actingAs($this->user);
        
        // Create attendance record for today
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'time_in' => '08:00:00',
            'time_out' => null
        ]);
        
        $response = $this->getJson('/api/paramedis/attendance/status');
        
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'has_checked_in' => true,
                        'has_checked_out' => false,
                        'can_check_in' => false,
                        'can_check_out' => true
                    ]
                ]);
        
        $response->assertJsonFragment([
            'id' => $attendance->id,
            'date' => $attendance->date->format('Y-m-d'),
            'time_in' => '08:00:00',
            'time_out' => null
        ]);
    }

    /** @test */
    public function it_returns_correct_status_when_user_has_checked_out()
    {
        Sanctum::actingAs($this->user);
        
        // Create completed attendance record
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'time_in' => '08:00:00',
            'time_out' => '17:00:00'
        ]);
        
        $response = $this->getJson('/api/paramedis/attendance/status');
        
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'has_checked_in' => true,
                        'has_checked_out' => true,
                        'can_check_in' => false,
                        'can_check_out' => false
                    ]
                ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/paramedis/attendance/status');
        
        $response->assertStatus(401);
    }

    /** @test */
    public function dashboard_status_returns_comprehensive_attendance_data()
    {
        Sanctum::actingAs($this->user);
        
        // Create some attendance records
        Attendance::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'date' => Carbon::now()->startOfWeek()->addDays(rand(0, 4))
        ]);
        
        $response = $this->getJson('/api/v2/dashboards/paramedis/attendance/status');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'today_status',
                        'today_attendance',
                        'this_week_attendance',
                        'this_month_attendance',
                        'attendance_percentage',
                        'working_days_this_month'
                    ],
                    'meta' => [
                        'date',
                        'timestamp'
                    ]
                ]);
    }

    /** @test */
    public function it_calculates_attendance_percentage_correctly()
    {
        Sanctum::actingAs($this->user);
        
        // Create attendance for 10 days this month
        for ($i = 1; $i <= 10; $i++) {
            if (Carbon::create(null, null, $i)->isWeekday()) {
                Attendance::factory()->create([
                    'user_id' => $this->user->id,
                    'date' => Carbon::create(null, null, $i)
                ]);
            }
        }
        
        $response = $this->getJson('/api/v2/dashboards/paramedis/attendance/status');
        
        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertIsNumeric($data['attendance_percentage']);
        $this->assertGreaterThanOrEqual(0, $data['attendance_percentage']);
        $this->assertLessThanOrEqual(100, $data['attendance_percentage']);
    }

    /** @test */
    public function it_handles_edge_case_with_no_attendance_records()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/v2/dashboards/paramedis/attendance/status');
        
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'today_status' => 'not_checked_in',
                        'today_attendance' => null,
                        'this_week_attendance' => 0,
                        'this_month_attendance' => 0,
                        'attendance_percentage' => 0
                    ]
                ]);
    }

    /** @test */
    public function it_includes_location_data_in_attendance_response()
    {
        Sanctum::actingAs($this->user);
        
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'check_in_lat' => -7.898878,
            'check_in_lng' => 111.961884,
            'check_out_lat' => -7.898878,
            'check_out_lng' => 111.961884
        ]);
        
        $response = $this->getJson('/api/paramedis/attendance/status');
        
        $response->assertStatus(200)
                ->assertJsonFragment([
                    'location' => [
                        'check_in_lat' => -7.898878,
                        'check_in_lng' => 111.961884,
                        'check_out_lat' => -7.898878,
                        'check_out_lng' => 111.961884
                    ]
                ]);
    }
}