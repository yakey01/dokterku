<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\WorkLocation;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Tag(
 *     name="Work Locations",
 *     description="Work location management endpoints"
 * )
 */
class WorkLocationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/work-locations/active",
     *     summary="Get all active work locations",
     *     tags={"Work Locations"},
     *     @OA\Response(
     *         response=200,
     *         description="Active work locations retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Klinik Utama"),
     *                     @OA\Property(property="address", type="string", example="Jl. Kesehatan No. 123"),
     *                     @OA\Property(property="latitude", type="number", format="float", example=-7.898878),
     *                     @OA\Property(property="longitude", type="number", format="float", example=111.961884),
     *                     @OA\Property(property="radius", type="integer", example=100),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total", type="integer", example=3),
     *                 @OA\Property(property="cached_until", type="string", format="datetime")
     *             )
     *         )
     *     )
     * )
     */
    public function active(): JsonResponse
    {
        $cacheKey = 'work_locations_active';
        $cacheTTL = 1800; // 30 minutes
        
        $workLocations = Cache::remember($cacheKey, $cacheTTL, function () {
            return WorkLocation::where('is_active', true)
                ->select([
                    'id',
                    'name',
                    'address',
                    'latitude',
                    'longitude',
                    'radius',
                    'is_active',
                    'created_at',
                    'updated_at'
                ])
                ->orderBy('name')
                ->get();
        });
        
        return response()->json([
            'success' => true,
            'data' => $workLocations->map(function ($location) {
                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'address' => $location->address,
                    'latitude' => (float) $location->latitude,
                    'longitude' => (float) $location->longitude,
                    'radius' => (int) $location->radius,
                    'is_active' => (bool) $location->is_active
                ];
            }),
            'meta' => [
                'total' => $workLocations->count(),
                'cached_until' => now()->addSeconds($cacheTTL)->toISOString(),
                'cache_key' => $cacheKey
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v2/locations/work-locations",
     *     summary="Get work locations for v2 API",
     *     tags={"Work Locations"},
     *     @OA\Response(
     *         response=200,
     *         description="Work locations retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="locations", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Klinik Utama"),
     *                         @OA\Property(property="coordinates", type="object",
     *                             @OA\Property(property="lat", type="number", format="float", example=-7.898878),
     *                             @OA\Property(property="lng", type="number", format="float", example=111.961884)
     *                         ),
     *                         @OA\Property(property="radius", type="integer", example=100),
     *                         @OA\Property(property="address", type="string", example="Jl. Kesehatan No. 123")
     *                     )
     *                 ),
     *                 @OA\Property(property="gps_validation", type="object",
     *                     @OA\Property(property="enabled", type="boolean", example=true),
     *                     @OA\Property(property="tolerance_meters", type="integer", example=100),
     *                     @OA\Property(property="accuracy_required", type="integer", example=20)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function v2Locations(): JsonResponse
    {
        $cacheKey = 'work_locations_v2';
        $cacheTTL = 1800; // 30 minutes
        
        $locations = Cache::remember($cacheKey, $cacheTTL, function () {
            return WorkLocation::where('is_active', true)
                ->select(['id', 'name', 'address', 'latitude', 'longitude', 'radius'])
                ->orderBy('name')
                ->get();
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'locations' => $locations->map(function ($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->name,
                        'address' => $location->address,
                        'coordinates' => [
                            'lat' => (float) $location->latitude,
                            'lng' => (float) $location->longitude
                        ],
                        'radius' => (int) $location->radius,
                        'validation_enabled' => true
                    ];
                }),
                'gps_validation' => [
                    'enabled' => true,
                    'tolerance_meters' => 100,
                    'accuracy_required' => 20,
                    'anti_spoofing' => true
                ]
            ],
            'meta' => [
                'total' => $locations->count(),
                'version' => '2.0',
                'cached_until' => now()->addSeconds($cacheTTL)->toISOString()
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v2/locations/validate-position",
     *     summary="Validate user position against work locations",
     *     tags={"Work Locations"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="latitude", type="number", format="float", example=-7.898878),
     *             @OA\Property(property="longitude", type="number", format="float", example=111.961884),
     *             @OA\Property(property="accuracy", type="number", format="float", example=5.2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Position validation result",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="is_valid", type="boolean", example=true),
     *                 @OA\Property(property="nearest_location", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Klinik Utama"),
     *                     @OA\Property(property="distance_meters", type="number", format="float", example=25.5)
     *                 ),
     *                 @OA\Property(property="validation_details", type="object",
     *                     @OA\Property(property="accuracy_acceptable", type="boolean", example=true),
     *                     @OA\Property(property="within_radius", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function validatePosition(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0'
        ]);
        
        $userLat = $request->latitude;
        $userLng = $request->longitude;
        $accuracy = $request->accuracy ?? 999;
        
        $workLocations = WorkLocation::where('is_active', true)->get();
        $nearestLocation = null;
        $minDistance = PHP_FLOAT_MAX;
        $isValid = false;
        
        foreach ($workLocations as $location) {
            $distance = $this->calculateDistance(
                $userLat, $userLng, 
                $location->latitude, $location->longitude
            );
            
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearestLocation = [
                    'id' => $location->id,
                    'name' => $location->name,
                    'distance_meters' => round($distance, 2)
                ];
            }
            
            // Check if within allowed radius
            if ($distance <= $location->radius) {
                $isValid = true;
            }
        }
        
        $accuracyAcceptable = $accuracy <= 20; // 20m accuracy requirement
        
        return response()->json([
            'success' => true,
            'data' => [
                'is_valid' => $isValid && $accuracyAcceptable,
                'nearest_location' => $nearestLocation,
                'validation_details' => [
                    'accuracy_acceptable' => $accuracyAcceptable,
                    'within_radius' => $isValid,
                    'provided_accuracy' => $accuracy,
                    'min_distance_to_location' => round($minDistance, 2)
                ]
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'user_coordinates' => [
                    'lat' => $userLat,
                    'lng' => $userLng
                ]
            ]
        ]);
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // Earth radius in meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }
}