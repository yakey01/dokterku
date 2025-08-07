<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\WorkLocation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HospitalLocationController extends Controller
{
    /**
     * Get hospital location data
     */
    public function getLocation(Request $request): JsonResponse
    {
        try {
            // Get the main hospital location (assuming it's the primary work location)
            $hospitalLocation = WorkLocation::where('location_type', 'main_office')
                ->where('is_active', true)
                ->first();

            if (!$hospitalLocation) {
                // Fallback to any active location
                $hospitalLocation = WorkLocation::where('is_active', true)
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            if (!$hospitalLocation) {
                // Return default data if no location found
                return response()->json([
                    'success' => true,
                    'data' => [
                        'name' => 'RS. Kediri Medical Center',
                        'address' => 'Jl. Ahmad Yani No. 123, Kediri, Jawa Timur',
                        'latitude' => -7.8481,
                        'longitude' => 112.0178,
                        'radius' => 50,
                        'location_type' => 'main_office',
                        'is_default' => true
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'name' => $hospitalLocation->name,
                    'address' => $hospitalLocation->address,
                    'latitude' => (float) $hospitalLocation->latitude,
                    'longitude' => (float) $hospitalLocation->longitude,
                    'radius' => $hospitalLocation->radius_meters ?? 50,
                    'location_type' => $hospitalLocation->location_type,
                    'description' => $hospitalLocation->description,
                    'contact_person' => $hospitalLocation->contact_person,
                    'contact_phone' => $hospitalLocation->contact_phone,
                    'working_hours' => $hospitalLocation->working_hours,
                    'is_default' => false
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load hospital location data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all active work locations
     */
    public function getAllLocations(Request $request): JsonResponse
    {
        try {
            $locations = WorkLocation::where('is_active', true)
                ->select([
                    'id',
                    'name',
                    'address',
                    'latitude',
                    'longitude',
                    'radius_meters',
                    'location_type',
                    'description'
                ])
                ->get()
                ->map(function ($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->name,
                        'address' => $location->address,
                        'latitude' => (float) $location->latitude,
                        'longitude' => (float) $location->longitude,
                        'radius' => $location->radius_meters ?? 50,
                        'location_type' => $location->location_type,
                        'description' => $location->description
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $locations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load locations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get location by ID
     */
    public function getLocationById(Request $request, int $id): JsonResponse
    {
        try {
            $location = WorkLocation::where('id', $id)
                ->where('is_active', true)
                ->first();

            if (!$location) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'address' => $location->address,
                    'latitude' => (float) $location->latitude,
                    'longitude' => (float) $location->longitude,
                    'radius' => $location->radius_meters ?? 50,
                    'location_type' => $location->location_type,
                    'description' => $location->description,
                    'contact_person' => $location->contact_person,
                    'contact_phone' => $location->contact_phone,
                    'working_hours' => $location->working_hours
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load location data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
