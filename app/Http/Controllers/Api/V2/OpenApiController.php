<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         version="2.0.0",
 *         title="Dokterku Clinic API",
 *         description="Comprehensive API for medical clinic management system with attendance tracking, Jaspel management, and healthcare operations",
 *         @OA\Contact(
 *             name="Dokterku Technical Team",
 *             email="admin@dokterkuklinik.com",
 *             url="https://dokterkuklinik.com"
 *         ),
 *         @OA\License(
 *             name="Proprietary",
 *             url="https://dokterkuklinik.com/license"
 *         )
 *     ),
 *     @OA\Server(
 *         url="/api/v2",
 *         description="Production API v2 Server"
 *     ),
 *     @OA\Server(
 *         url="http://localhost:8000/api/v2",
 *         description="Development API v2 Server"
 *     ),
 *     @OA\Components(
 *         @OA\SecurityScheme(
 *             securityScheme="sanctum",
 *             type="http",
 *             scheme="bearer",
 *             bearerFormat="JWT",
 *             description="Laravel Sanctum token authentication"
 *         ),
 *         @OA\Schema(
 *             schema="StandardResponse",
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Operation completed successfully"),
 *             @OA\Property(property="data", type="object", description="Response data"),
 *             @OA\Property(property="meta", type="object",
 *                 @OA\Property(property="version", type="string", example="2.0"),
 *                 @OA\Property(property="timestamp", type="string", format="datetime", example="2025-01-15T10:30:00Z"),
 *                 @OA\Property(property="request_id", type="string", format="uuid")
 *             )
 *         ),
 *         @OA\Schema(
 *             schema="ErrorResponse",
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Validation failed"),
 *             @OA\Property(property="errors", type="object", description="Validation errors"),
 *             @OA\Property(property="error_code", type="string", example="VALIDATION_ERROR"),
 *             @OA\Property(property="meta", type="object",
 *                 @OA\Property(property="timestamp", type="string", format="datetime"),
 *                 @OA\Property(property="request_id", type="string", format="uuid")
 *             )
 *         ),
 *         @OA\Schema(
 *             schema="User",
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="Dr. John Doe"),
 *             @OA\Property(property="email", type="string", format="email", example="john@dokterkuklinik.com"),
 *             @OA\Property(property="username", type="string", example="johndoe"),
 *             @OA\Property(property="role", type="string", example="paramedis"),
 *             @OA\Property(property="phone", type="string", example="+628123456789"),
 *             @OA\Property(property="avatar", type="string", format="uri", nullable=true),
 *             @OA\Property(property="is_active", type="boolean", example=true),
 *             @OA\Property(property="created_at", type="string", format="datetime"),
 *             @OA\Property(property="email_verified_at", type="string", format="datetime", nullable=true)
 *         ),
 *         @OA\Schema(
 *             schema="Attendance",
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=123),
 *             @OA\Property(property="user_id", type="integer", example=1),
 *             @OA\Property(property="date", type="string", format="date", example="2025-01-15"),
 *             @OA\Property(property="time_in", type="string", format="time", example="08:00:00"),
 *             @OA\Property(property="time_out", type="string", format="time", nullable=true, example="17:00:00"),
 *             @OA\Property(property="check_in_lat", type="number", format="float", example=-7.898878),
 *             @OA\Property(property="check_in_lng", type="number", format="float", example=111.961884),
 *             @OA\Property(property="check_out_lat", type="number", format="float", nullable=true),
 *             @OA\Property(property="check_out_lng", type="number", format="float", nullable=true),
 *             @OA\Property(property="status", type="string", example="present"),
 *             @OA\Property(property="work_duration", type="string", nullable=true, example="9 hours 0 minutes"),
 *             @OA\Property(property="notes", type="string", nullable=true),
 *             @OA\Property(property="created_at", type="string", format="datetime"),
 *             @OA\Property(property="updated_at", type="string", format="datetime")
 *         ),
 *         @OA\Schema(
 *             schema="WorkLocation",
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="Klinik Utama"),
 *             @OA\Property(property="address", type="string", example="Jl. Kesehatan No. 123"),
 *             @OA\Property(property="latitude", type="number", format="float", example=-7.898878),
 *             @OA\Property(property="longitude", type="number", format="float", example=111.961884),
 *             @OA\Property(property="radius", type="integer", example=100, description="Allowed radius in meters"),
 *             @OA\Property(property="is_active", type="boolean", example=true),
 *             @OA\Property(property="location_type", type="string", example="clinic"),
 *             @OA\Property(property="created_at", type="string", format="datetime"),
 *             @OA\Property(property="updated_at", type="string", format="datetime")
 *         ),
 *         @OA\Schema(
 *             schema="Jaspel",
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=456),
 *             @OA\Property(property="user_id", type="integer", example=1),
 *             @OA\Property(property="tindakan_id", type="integer", example=789),
 *             @OA\Property(property="tanggal", type="string", format="date", example="2025-01-15"),
 *             @OA\Property(property="nominal", type="number", format="float", example=150000.00),
 *             @OA\Property(property="jenis_jaspel", type="string", example="paramedis"),
 *             @OA\Property(property="status_validasi", type="string", example="approved", enum={"pending", "approved", "rejected", "disetujui"}),
 *             @OA\Property(property="validated_by", type="integer", nullable=true),
 *             @OA\Property(property="validated_at", type="string", format="datetime", nullable=true),
 *             @OA\Property(property="notes", type="string", nullable=true),
 *             @OA\Property(property="created_at", type="string", format="datetime"),
 *             @OA\Property(property="updated_at", type="string", format="datetime")
 *         ),
 *         @OA\Schema(
 *             schema="Tindakan",
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=789),
 *             @OA\Property(property="pasien_id", type="integer", example=101),
 *             @OA\Property(property="dokter_id", type="integer", example=2),
 *             @OA\Property(property="paramedis_id", type="integer", example=1),
 *             @OA\Property(property="jenis_tindakan_id", type="integer", example=5),
 *             @OA\Property(property="tanggal_tindakan", type="string", format="date", example="2025-01-15"),
 *             @OA\Property(property="biaya_tindakan", type="number", format="float", example=200000.00),
 *             @OA\Property(property="jasa_dokter", type="number", format="float", example=100000.00),
 *             @OA\Property(property="jasa_paramedis", type="number", format="float", example=50000.00),
 *             @OA\Property(property="status_validasi", type="string", example="approved"),
 *             @OA\Property(property="notes", type="string", nullable=true),
 *             @OA\Property(property="created_at", type="string", format="datetime"),
 *             @OA\Property(property="updated_at", type="string", format="datetime")
 *         ),
 *         @OA\Schema(
 *             schema="PaginationMeta",
 *             type="object",
 *             @OA\Property(property="current_page", type="integer", example=1),
 *             @OA\Property(property="per_page", type="integer", example=15),
 *             @OA\Property(property="total", type="integer", example=150),
 *             @OA\Property(property="last_page", type="integer", example=10),
 *             @OA\Property(property="from", type="integer", example=1),
 *             @OA\Property(property="to", type="integer", example=15),
 *             @OA\Property(property="path", type="string", example="/api/v2/attendance/history"),
 *             @OA\Property(property="first_page_url", type="string"),
 *             @OA\Property(property="last_page_url", type="string"),
 *             @OA\Property(property="next_page_url", type="string", nullable=true),
 *             @OA\Property(property="prev_page_url", type="string", nullable=true)
 *         ),
 *         @OA\Schema(
 *             schema="ValidationError",
 *             type="object",
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(property="errors", type="object",
 *                 @OA\Property(property="email", type="array",
 *                     @OA\Items(type="string", example="The email field is required.")
 *                 ),
 *                 @OA\Property(property="password", type="array",
 *                     @OA\Items(type="string", example="The password must be at least 8 characters.")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Tag(
 *         name="Authentication",
 *         description="User authentication and authorization"
 *     ),
 *     @OA\Tag(
 *         name="Dashboards",
 *         description="Dashboard data endpoints for different user roles"
 *     ),
 *     @OA\Tag(
 *         name="Attendance",
 *         description="Employee attendance management"
 *     ),
 *     @OA\Tag(
 *         name="Work Locations", 
 *         description="Work location management and GPS validation"
 *     ),
 *     @OA\Tag(
 *         name="Jaspel",
 *         description="Healthcare professional service fees (Jasa Pelayanan)"
 *     ),
 *     @OA\Tag(
 *         name="Tindakan",
 *         description="Medical procedures and treatments"
 *     ),
 *     @OA\Tag(
 *         name="Users",
 *         description="User management operations"
 *     ),
 *     @OA\Tag(
 *         name="Reports",
 *         description="Reporting and analytics endpoints"
 *     ),
 *     @OA\Tag(
 *         name="System",
 *         description="System health and information endpoints"
 *     )
 * )
 */
class OpenApiController extends Controller
{
    //
}