<?php

namespace App\Http\Controllers\Api\V2\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalJaga;
use App\Models\ShiftTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AdminJadwalJagaController extends Controller
{
    /**
     * Get all jadwal jaga with pagination and filters
     */
    public function index(Request $request)
    {
        try {
            $query = JadwalJaga::with(['shiftTemplate', 'pegawai']);

            // Apply filters
            if ($request->has('pegawai_id')) {
                $query->where('pegawai_id', $request->pegawai_id);
            }

            if ($request->has('status_jaga')) {
                $query->where('status_jaga', $request->status_jaga);
            }

            if ($request->has('unit_kerja')) {
                $query->where('unit_kerja', $request->unit_kerja);
            }

            if ($request->has('peran')) {
                $query->where('peran', $request->peran);
            }

            if ($request->has('tanggal_jaga')) {
                $query->whereDate('tanggal_jaga', $request->tanggal_jaga);
            }

            if ($request->has('month') && $request->has('year')) {
                $query->whereMonth('tanggal_jaga', $request->month)
                      ->whereYear('tanggal_jaga', $request->year);
            }

            // Apply search
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('pegawai', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhereHas('shiftTemplate', function($q) use ($search) {
                    $q->where('nama_shift', 'like', "%{$search}%");
                })->orWhere('unit_kerja', 'like', "%{$search}%");
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'tanggal_jaga');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $jadwalJagas = $query->paginate($perPage);

            // Format response
            $formattedData = $jadwalJagas->getCollection()->map(function ($jadwal) {
                $shiftTemplate = $jadwal->shiftTemplate;
                
                return [
                    'id' => $jadwal->id,
                    'tanggal_jaga' => $jadwal->tanggal_jaga->format('Y-m-d'),
                    'tanggal_formatted' => $jadwal->tanggal_jaga->format('d/m/Y'),
                    'pegawai_id' => $jadwal->pegawai_id,
                    'employee_name' => $jadwal->pegawai->name ?? 'Unknown',
                    'employee_email' => $jadwal->pegawai->email ?? null,
                    'shift_template_id' => $jadwal->shift_template_id,
                    'unit_kerja' => $jadwal->unit_kerja,
                    'unit_instalasi' => $jadwal->unit_instalasi,
                    'peran' => $jadwal->peran,
                    'status_jaga' => $jadwal->status_jaga,
                    'keterangan' => $jadwal->keterangan,
                    'jam_jaga_custom' => $jadwal->jam_jaga_custom?->format('H:i'),
                    'shift_template' => $shiftTemplate ? [
                        'id' => $shiftTemplate->id,
                        'nama_shift' => $shiftTemplate->nama_shift,
                        'jam_masuk' => $shiftTemplate->jam_masuk,
                        'jam_pulang' => $shiftTemplate->jam_pulang,
                        'durasi_jam' => $shiftTemplate->durasi_jam,
                        'warna' => $shiftTemplate->warna ?? '#3b82f6'
                    ] : null,
                    'created_at' => $jadwal->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $jadwal->updated_at->format('Y-m-d H:i:s')
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Jadwal jaga berhasil dimuat',
                'data' => [
                    'schedules' => $formattedData,
                    'pagination' => [
                        'current_page' => $jadwalJagas->currentPage(),
                        'last_page' => $jadwalJagas->lastPage(),
                        'per_page' => $jadwalJagas->perPage(),
                        'total' => $jadwalJagas->total(),
                        'from' => $jadwalJagas->firstItem(),
                        'to' => $jadwalJagas->lastItem()
                    ]
                ],
                'meta' => [
                    'timezone' => 'Asia/Jakarta',
                    'date_format' => 'Y-m-d',
                    'display_format' => 'd/m/Y',
                    'api_version' => '2.0'
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('AdminJadwalJagaController::index error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat jadwal jaga: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Create new jadwal jaga
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'pegawai_id' => 'required|exists:users,id',
                'shift_template_id' => 'required|exists:shift_templates,id',
                'tanggal_jaga' => 'required|date',
                'unit_kerja' => 'required|string|max:255',
                'peran' => 'required|string|max:255',
                'status_jaga' => 'required|string|in:Aktif,Cuti,Izin,OnCall',
                'keterangan' => 'nullable|string',
                'jam_jaga_custom' => 'nullable|date_format:H:i'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for conflicts
            $existingJadwal = JadwalJaga::where('pegawai_id', $request->pegawai_id)
                ->whereDate('tanggal_jaga', $request->tanggal_jaga)
                ->first();

            if ($existingJadwal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jadwal jaga untuk pegawai ini pada tanggal tersebut sudah ada',
                    'data' => [
                        'existing_schedule' => [
                            'id' => $existingJadwal->id,
                            'tanggal_jaga' => $existingJadwal->tanggal_jaga->format('Y-m-d'),
                            'shift' => $existingJadwal->shiftTemplate->nama_shift ?? 'Unknown'
                        ]
                    ]
                ], 409);
            }

            $jadwalJaga = JadwalJaga::create($request->all());

            // Load relationships
            $jadwalJaga->load(['shiftTemplate', 'pegawai']);

            return response()->json([
                'success' => true,
                'message' => 'Jadwal jaga berhasil dibuat',
                'data' => [
                    'id' => $jadwalJaga->id,
                    'tanggal_jaga' => $jadwalJaga->tanggal_jaga->format('Y-m-d'),
                    'tanggal_formatted' => $jadwalJaga->tanggal_jaga->format('d/m/Y'),
                    'employee_name' => $jadwalJaga->pegawai->name ?? 'Unknown',
                    'shift_name' => $jadwalJaga->shiftTemplate->nama_shift ?? 'Unknown',
                    'unit_kerja' => $jadwalJaga->unit_kerja,
                    'peran' => $jadwalJaga->peran,
                    'status_jaga' => $jadwalJaga->status_jaga
                ]
            ], 201);

        } catch (\Exception $e) {
            \Log::error('AdminJadwalJagaController::store error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat jadwal jaga: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Update jadwal jaga
     */
    public function update(Request $request, $id)
    {
        try {
            $jadwalJaga = JadwalJaga::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'pegawai_id' => 'sometimes|required|exists:users,id',
                'shift_template_id' => 'sometimes|required|exists:shift_templates,id',
                'tanggal_jaga' => 'sometimes|required|date',
                'unit_kerja' => 'sometimes|required|string|max:255',
                'peran' => 'sometimes|required|string|max:255',
                'status_jaga' => 'sometimes|required|string|in:Aktif,Cuti,Izin,OnCall',
                'keterangan' => 'nullable|string',
                'jam_jaga_custom' => 'nullable|date_format:H:i'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for conflicts if tanggal_jaga is being updated
            if ($request->has('tanggal_jaga') && $request->tanggal_jaga != $jadwalJaga->tanggal_jaga->format('Y-m-d')) {
                $existingJadwal = JadwalJaga::where('pegawai_id', $request->pegawai_id ?? $jadwalJaga->pegawai_id)
                    ->whereDate('tanggal_jaga', $request->tanggal_jaga)
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingJadwal) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Jadwal jaga untuk pegawai ini pada tanggal tersebut sudah ada',
                        'data' => [
                            'existing_schedule' => [
                                'id' => $existingJadwal->id,
                                'tanggal_jaga' => $existingJadwal->tanggal_jaga->format('Y-m-d'),
                                'shift' => $existingJadwal->shiftTemplate->nama_shift ?? 'Unknown'
                            ]
                        ]
                    ], 409);
                }
            }

            $jadwalJaga->update($request->all());

            // Load relationships
            $jadwalJaga->load(['shiftTemplate', 'pegawai']);

            return response()->json([
                'success' => true,
                'message' => 'Jadwal jaga berhasil diperbarui',
                'data' => [
                    'id' => $jadwalJaga->id,
                    'tanggal_jaga' => $jadwalJaga->tanggal_jaga->format('Y-m-d'),
                    'tanggal_formatted' => $jadwalJaga->tanggal_jaga->format('d/m/Y'),
                    'employee_name' => $jadwalJaga->pegawai->name ?? 'Unknown',
                    'shift_name' => $jadwalJaga->shiftTemplate->nama_shift ?? 'Unknown',
                    'unit_kerja' => $jadwalJaga->unit_kerja,
                    'peran' => $jadwalJaga->peran,
                    'status_jaga' => $jadwalJaga->status_jaga
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('AdminJadwalJagaController::update error', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui jadwal jaga: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Delete jadwal jaga
     */
    public function destroy($id)
    {
        try {
            $jadwalJaga = JadwalJaga::findOrFail($id);
            $jadwalJaga->delete();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal jaga berhasil dihapus',
                'data' => [
                    'id' => $id,
                    'deleted_at' => now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('AdminJadwalJagaController::destroy error', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus jadwal jaga: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get jadwal jaga by ID
     */
    public function show($id)
    {
        try {
            $jadwalJaga = JadwalJaga::with(['shiftTemplate', 'pegawai'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Jadwal jaga berhasil dimuat',
                'data' => [
                    'id' => $jadwalJaga->id,
                    'tanggal_jaga' => $jadwalJaga->tanggal_jaga->format('Y-m-d'),
                    'tanggal_formatted' => $jadwalJaga->tanggal_jaga->format('d/m/Y'),
                    'pegawai_id' => $jadwalJaga->pegawai_id,
                    'employee_name' => $jadwalJaga->pegawai->name ?? 'Unknown',
                    'employee_email' => $jadwalJaga->pegawai->email ?? null,
                    'shift_template_id' => $jadwalJaga->shift_template_id,
                    'unit_kerja' => $jadwalJaga->unit_kerja,
                    'unit_instalasi' => $jadwalJaga->unit_instalasi,
                    'peran' => $jadwalJaga->peran,
                    'status_jaga' => $jadwalJaga->status_jaga,
                    'keterangan' => $jadwalJaga->keterangan,
                    'jam_jaga_custom' => $jadwalJaga->jam_jaga_custom?->format('H:i'),
                    'shift_template' => $jadwalJaga->shiftTemplate ? [
                        'id' => $jadwalJaga->shiftTemplate->id,
                        'nama_shift' => $jadwalJaga->shiftTemplate->nama_shift,
                        'jam_masuk' => $jadwalJaga->shiftTemplate->jam_masuk,
                        'jam_pulang' => $jadwalJaga->shiftTemplate->jam_pulang,
                        'durasi_jam' => $jadwalJaga->shiftTemplate->durasi_jam,
                        'warna' => $jadwalJaga->shiftTemplate->warna ?? '#3b82f6'
                    ] : null,
                    'created_at' => $jadwalJaga->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $jadwalJaga->updated_at->format('Y-m-d H:i:s')
                ],
                'meta' => [
                    'timezone' => 'Asia/Jakarta',
                    'date_format' => 'Y-m-d',
                    'display_format' => 'd/m/Y',
                    'api_version' => '2.0'
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('AdminJadwalJagaController::show error', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat jadwal jaga: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
