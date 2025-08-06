<?php

namespace Tests\Feature\Api\V2;

use Tests\TestCase;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class WorkLocationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_active_work_locations()
    {
        // Create active and inactive work locations
        WorkLocation::factory()->count(3)->create(['is_active' => true]);
        WorkLocation::factory()->count(2)->create(['is_active' => false]);
        
        $response = $this->getJson('/api/work-locations/active');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'address',
                            'latitude',
                            'longitude',
                            'radius',
                            'is_active'
                        ]
                    ],
                    'meta' => [
                        'total',
                        'cached_until',
                        'cache_key'
                    ]
                ]);
        
        $data = $response->json('data');
        $this->assertCount(3, $data);
        
        // Ensure all returned locations are active
        foreach ($data as $location) {
            $this->assertTrue($location['is_active']);
        }
    }

    /** @test */
    public function it_returns_v2_work_locations_with_gps_validation_info()
    {
        WorkLocation::factory()->count(2)->create([
            'is_active' => true,
            'latitude' => -7.898878,
            'longitude' => 111.961884,
            'radius' => 100
        ]);
        
        $response = $this->getJson('/api/v2/locations/work-locations');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'locations' => [
                            '*' => [
                                'id',
                                'name',
                                'address',
                                'coordinates' => [
                                    'lat',
                                    'lng'
                                ],
                                'radius',
                                'validation_enabled'
                            ]
                        ],
                        'gps_validation' => [
                            'enabled',
                            'tolerance_meters',
                            'accuracy_required',
                            'anti_spoofing'
                        ]
                    ],
                    'meta' => [
                        'total',
                        'version',
                        'cached_until'
                    ]
                ]);
        
        $response->assertJsonFragment([
            'gps_validation' => [
                'enabled' => true,
                'tolerance_meters' => 100,
                'accuracy_required' => 20,
                'anti_spoofing' => true
            ]
        ]);
    }

    /** @test */
    public function it_validates_user_position_within_work_location_radius()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        
        // Create work location
        $workLocation = WorkLocation::factory()->create([
            'is_active' => true,
            'latitude' => -7.898878,
            'longitude' => 111.961884,
            'radius' => 100
        ]);
        
        // Test position within radius (same coordinates)
        $response = $this->postJson('/api/v2/locations/validate-position', [
            'latitude' => -7.898878,
            'longitude' => 111.961884,
            'accuracy' => 5.0
        ]);
        
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'is_valid' => true,
                        'validation_details' => [
                            'accuracy_acceptable' => true,
                            'within_radius' => true
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_rejects_position_outside_work_location_radius()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        
        WorkLocation::factory()->create([
            'is_active' => true,
            'latitude' => -7.898878,
            'longitude' => 111.961884,
            'radius' => 50 // Small radius
        ]);
        
        // Test position far from work location
        $response = $this->postJson('/api/v2/locations/validate-position', [
            'latitude' => -7.908878, // ~1km away
            'longitude' => 111.971884,
            'accuracy' => 5.0
        ]);
        
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'is_valid' => false,
                        'validation_details' => [
                            'accuracy_acceptable' => true,
                            'within_radius' => false
                        ]
                    ]
                ]);
        
        $data = $response->json('data');
        $this->assertGreaterThan(50, $data['validation_details']['min_distance_to_location']);
    }

    /** @test */
    public function it_rejects_position_with_poor_gps_accuracy()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        
        WorkLocation::factory()->create([
            'is_active' => true,
            'latitude' => -7.898878,
            'longitude' => 111.961884,
            'radius' => 100
        ]);
        
        // Test with poor GPS accuracy
        $response = $this->postJson('/api/v2/locations/validate-position', [
            'latitude' => -7.898878,
            'longitude' => 111.961884,
            'accuracy' => 50.0 // Poor accuracy > 20m threshold
        ]);
        
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'is_valid' => false,
                        'validation_details' => [
                            'accuracy_acceptable' => false,
                            'within_radius' => true
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_validates_coordinates_within_valid_ranges()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        
        // Test invalid latitude
        $response = $this->postJson('/api/v2/locations/validate-position', [
            'latitude' => 91, // Invalid latitude > 90
            'longitude' => 111.961884,
            'accuracy' => 5.0
        ]);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors('latitude');
        
        // Test invalid longitude
        $response = $this->postJson('/api/v2/locations/validate-position', [
            'latitude' => -7.898878,
            'longitude' => 181, // Invalid longitude > 180
            'accuracy' => 5.0
        ]);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors('longitude');
    }

    /** @test */
    public function it_requires_authentication_for_position_validation()
    {
        $response = $this->postJson('/api/v2/locations/validate-position', [
            'latitude' => -7.898878,
            'longitude' => 111.961884,
            'accuracy' => 5.0
        ]);
        
        $response->assertStatus(401);
    }

    /** @test */
    public function it_finds_nearest_work_location()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        
        // Create multiple work locations
        $location1 = WorkLocation::factory()->create([
            'name' => 'Close Location',
            'is_active' => true,
            'latitude' => -7.898878,
            'longitude' => 111.961884,
            'radius' => 50
        ]);
        
        $location2 = WorkLocation::factory()->create([
            'name' => 'Far Location',
            'is_active' => true,
            'latitude' => -7.908878, // Further away
            'longitude' => 111.971884,
            'radius' => 50
        ]);
        
        $response = $this->postJson('/api/v2/locations/validate-position', [
            'latitude' => -7.898978, // Very close to location1
            'longitude' => 111.961984,
            'accuracy' => 5.0
        ]);
        
        $response->assertStatus(200);
        
        $nearestLocation = $response->json('data.nearest_location');
        $this->assertEquals('Close Location', $nearestLocation['name']);
        $this->assertLessThan(50, $nearestLocation['distance_meters']);
    }

    /** @test */
    public function work_locations_are_cached_properly()
    {
        WorkLocation::factory()->count(3)->create(['is_active' => true]);
        
        // First request should cache the results
        $response1 = $this->getJson('/api/work-locations/active');
        $response1->assertStatus(200);
        
        // Create additional location after cache
        WorkLocation::factory()->create(['is_active' => true]);
        
        // Second request should return cached results (still 3 locations)
        $response2 = $this->getJson('/api/work-locations/active');
        $response2->assertStatus(200);
        
        $data = $response2->json('data');
        $this->assertCount(3, $data); // Should still be 3 due to caching
    }

    /** @test */
    public function it_handles_no_active_work_locations()
    {
        // Create only inactive locations
        WorkLocation::factory()->count(2)->create(['is_active' => false]);
        
        $response = $this->getJson('/api/work-locations/active');
        
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [],
                    'meta' => [
                        'total' => 0
                    ]
                ]);
    }
}