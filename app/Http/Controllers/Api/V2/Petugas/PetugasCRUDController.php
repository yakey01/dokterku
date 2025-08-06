<?php

namespace App\Http\Controllers\Api\V2\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JumlahPasienHarian;
use App\Models\JenisTindakan;
use App\Models\Dokter;
use App\Models\Pegawai;
use App\Models\ShiftTemplate;
use App\Services\AutoCodeGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PetugasCRUDController extends Controller
{
    /**
     * Get paginated list of patients with search and filters
     */
    public function patients(Request $request): JsonResponse
    {
        try {
            $query = Pasien::query()
                ->where('input_by', Auth::id())
                ->with(['inputBy']);

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('no_rekam_medis', 'like', "%{$search}%")
                      ->orWhere('no_telepon', 'like', "%{$search}%")
                      ->orWhere('alamat', 'like', "%{$search}%");
                });
            }

            // Filter by gender
            if ($request->has('jenis_kelamin') && !empty($request->jenis_kelamin)) {
                $query->where('jenis_kelamin', $request->jenis_kelamin);
            }

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Date range filter
            if ($request->has('tanggal_lahir_dari') && !empty($request->tanggal_lahir_dari)) {
                $query->whereDate('tanggal_lahir', '>=', $request->tanggal_lahir_dari);
            }
            if ($request->has('tanggal_lahir_sampai') && !empty($request->tanggal_lahir_sampai)) {
                $query->whereDate('tanggal_lahir', '<=', $request->tanggal_lahir_sampai);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDir = $request->get('sort_dir', 'desc');
            $query->orderBy($sortBy, $sortDir);

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $patients = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $patients->items(),
                'pagination' => [
                    'current_page' => $patients->currentPage(),
                    'per_page' => $patients->perPage(),
                    'total' => $patients->total(),
                    'last_page' => $patients->lastPage(),
                    'has_more' => $patients->hasMorePages(),
                ],
                'filters' => [
                    'jenis_kelamin_options' => [
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan'
                    ],
                    'status_options' => [
                        'pending' => 'Menunggu Verifikasi',
                        'verified' => 'Terverifikasi',
                        'rejected' => 'Ditolak'
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pasien: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new patient
     */
    public function storePatient(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nama' => 'required|string|max:255',
                'tanggal_lahir' => 'required|date|before_or_equal:today',
                'jenis_kelamin' => 'required|in:L,P',
                'alamat' => 'nullable|string|max:500',
                'no_telepon' => 'nullable|string|max:20',
                'no_rekam_medis' => 'nullable|string|max:20|unique:pasien,no_rekam_medis',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            
            // Generate no_rekam_medis if not provided
            if (empty($data['no_rekam_medis'])) {
                $codeGenerator = new AutoCodeGeneratorService();
                $data['no_rekam_medis'] = $codeGenerator->generateCode('pasien');
            }

            $data['input_by'] = Auth::id();
            $data['status'] = 'pending';

            $patient = Pasien::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Data pasien berhasil disimpan',
                'data' => $patient->load('inputBy')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data pasien: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific patient
     */
    public function showPatient(Pasien $patient): JsonResponse
    {
        try {
            // Check if user can access this patient
            if ($patient->input_by !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke data pasien ini'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $patient->load(['inputBy', 'tindakan'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pasien: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update patient
     */
    public function updatePatient(Request $request, Pasien $patient): JsonResponse
    {
        try {
            // Check if user can update this patient
            if ($patient->input_by !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk mengupdate data pasien ini'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nama' => 'required|string|max:255',
                'tanggal_lahir' => 'required|date|before_or_equal:today',
                'jenis_kelamin' => 'required|in:L,P',
                'alamat' => 'nullable|string|max:500',
                'no_telepon' => 'nullable|string|max:20',
                'no_rekam_medis' => 'nullable|string|max:20|unique:pasien,no_rekam_medis,' . $patient->id,
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $patient->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Data pasien berhasil diupdate',
                'data' => $patient->load('inputBy')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate data pasien: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete patient
     */
    public function deletePatient(Pasien $patient): JsonResponse
    {
        try {
            // Check if user can delete this patient
            if ($patient->input_by !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk menghapus data pasien ini'
                ], 403);
            }

            $patient->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data pasien berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data pasien: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get paginated list of tindakan with search and filters
     */
    public function tindakan(Request $request): JsonResponse
    {
        try {
            $query = Tindakan::query()
                ->where('input_by', Auth::id())
                ->with(['jenisTindakan', 'pasien', 'dokter', 'paramedis', 'nonParamedis', 'shift']);

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('jenisTindakan', function ($jq) use ($search) {
                        $jq->where('nama', 'like', "%{$search}%");
                    })->orWhereHas('pasien', function ($pq) use ($search) {
                        $pq->where('nama', 'like', "%{$search}%")
                          ->orWhere('no_rekam_medis', 'like', "%{$search}%");
                    })->orWhereHas('dokter', function ($dq) use ($search) {
                        $dq->where('nama_lengkap', 'like', "%{$search}%");
                    });
                });
            }

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Filter by jenis tindakan
            if ($request->has('jenis_tindakan_id') && !empty($request->jenis_tindakan_id)) {
                $query->where('jenis_tindakan_id', $request->jenis_tindakan_id);
            }

            // Filter by dokter
            if ($request->has('dokter_id') && !empty($request->dokter_id)) {
                $query->where('dokter_id', $request->dokter_id);
            }

            // Date range filter
            if ($request->has('tanggal_dari') && !empty($request->tanggal_dari)) {
                $query->whereDate('tanggal_tindakan', '>=', $request->tanggal_dari);
            }
            if ($request->has('tanggal_sampai') && !empty($request->tanggal_sampai)) {
                $query->whereDate('tanggal_tindakan', '<=', $request->tanggal_sampai);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'tanggal_tindakan');
            $sortDir = $request->get('sort_dir', 'desc');
            $query->orderBy($sortBy, $sortDir);

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $tindakan = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $tindakan->items(),
                'pagination' => [
                    'current_page' => $tindakan->currentPage(),
                    'per_page' => $tindakan->perPage(),
                    'total' => $tindakan->total(),
                    'last_page' => $tindakan->lastPage(),
                    'has_more' => $tindakan->hasMorePages(),
                ],
                'filters' => [
                    'status_options' => [
                        'pending' => 'Menunggu',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal'
                    ],
                    'jenis_tindakan_options' => JenisTindakan::where('is_active', true)
                        ->orderBy('nama')
                        ->pluck('nama', 'id'),
                    'dokter_options' => Dokter::where('aktif', true)
                        ->orderBy('nama_lengkap')
                        ->pluck('nama_lengkap', 'id')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data tindakan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get supporting data for tindakan form
     */
    public function getTindakanFormData(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'jenis_tindakan' => JenisTindakan::where('is_active', true)
                        ->orderBy('nama')
                        ->get(['id', 'nama', 'tarif', 'jasa_non_paramedis']),
                    'pasien' => Pasien::where('input_by', Auth::id())
                        ->where('status', 'verified')
                        ->orderBy('nama')
                        ->get(['id', 'nama', 'no_rekam_medis']),
                    'dokter' => Dokter::where('aktif', true)
                        ->orderBy('nama_lengkap')
                        ->get(['id', 'nama_lengkap', 'spesialisasi']),
                    'paramedis' => Pegawai::where('jenis_pegawai', 'Paramedis')
                        ->where('aktif', true)
                        ->orderBy('nama_lengkap')
                        ->get(['id', 'nama_lengkap', 'jabatan']),
                    'non_paramedis' => Pegawai::where('jenis_pegawai', 'Non-Paramedis')
                        ->where('aktif', true)
                        ->orderBy('nama_lengkap')
                        ->get(['id', 'nama_lengkap', 'jabatan']),
                    'shift_templates' => ShiftTemplate::orderBy('nama_shift')
                        ->get(['id', 'nama_shift']),
                    'default_jaspel_percentage' => config('app.default_jaspel_percentage', 40)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data form: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new tindakan
     */
    public function storeTindakan(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'jenis_tindakan_id' => 'required|exists:jenis_tindakan,id',
                'pasien_id' => 'required|exists:pasien,id',
                'tanggal_tindakan' => 'required|date|before_or_equal:now',
                'shift_id' => 'required|exists:shift_templates,id',
                'dokter_id' => 'nullable|exists:dokter,id',
                'paramedis_id' => 'nullable|exists:pegawai,id',
                'non_paramedis_id' => 'nullable|exists:pegawai,id',
                'tarif' => 'required|numeric|min:0',
                'jasa_dokter' => 'required|numeric|min:0',
                'jasa_paramedis' => 'required|numeric|min:0',
                'jasa_non_paramedis' => 'required|numeric|min:0',
                'catatan' => 'nullable|string|max:500',
                'status' => 'required|in:pending,selesai,batal',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user can access the selected patient
            $pasien = Pasien::findOrFail($request->pasien_id);
            if ($pasien->input_by !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke pasien ini'
                ], 403);
            }

            $data = $validator->validated();
            $data['input_by'] = Auth::id();
            $data['status_validasi'] = 'pending';

            $tindakan = Tindakan::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Data tindakan berhasil disimpan',
                'data' => $tindakan->load(['jenisTindakan', 'pasien', 'dokter', 'paramedis', 'nonParamedis', 'shift'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data tindakan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show specific tindakan
     */
    public function showTindakan(Tindakan $tindakan): JsonResponse
    {
        try {
            // Check if user can access this tindakan
            if ($tindakan->input_by !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke data tindakan ini'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $tindakan->load(['jenisTindakan', 'pasien', 'dokter', 'paramedis', 'nonParamedis', 'shift'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data tindakan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update tindakan
     */
    public function updateTindakan(Request $request, Tindakan $tindakan): JsonResponse
    {
        try {
            // Check if user can update this tindakan
            if ($tindakan->input_by !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk mengupdate data tindakan ini'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'jenis_tindakan_id' => 'required|exists:jenis_tindakan,id',
                'pasien_id' => 'required|exists:pasien,id',
                'tanggal_tindakan' => 'required|date|before_or_equal:now',
                'shift_id' => 'required|exists:shift_templates,id',
                'dokter_id' => 'nullable|exists:dokter,id',
                'paramedis_id' => 'nullable|exists:pegawai,id',
                'non_paramedis_id' => 'nullable|exists:pegawai,id',
                'tarif' => 'required|numeric|min:0',
                'jasa_dokter' => 'required|numeric|min:0',
                'jasa_paramedis' => 'required|numeric|min:0',
                'jasa_non_paramedis' => 'required|numeric|min:0',
                'catatan' => 'nullable|string|max:500',
                'status' => 'required|in:pending,selesai,batal',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $tindakan->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Data tindakan berhasil diupdate',
                'data' => $tindakan->load(['jenisTindakan', 'pasien', 'dokter', 'paramedis', 'nonParamedis', 'shift'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate data tindakan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete tindakan
     */
    public function deleteTindakan(Tindakan $tindakan): JsonResponse
    {
        try {
            // Check if user can delete this tindakan
            if ($tindakan->input_by !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk menghapus data tindakan ini'
                ], 403);
            }

            $tindakan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data tindakan berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data tindakan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get paginated list of pendapatan harian with search and filters
     */
    public function pendapatanHarian(Request $request): JsonResponse
    {
        try {
            $query = PendapatanHarian::query()
                ->where('input_by', Auth::id())
                ->with(['inputBy']);

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('deskripsi', 'like', "%{$search}%")
                      ->orWhere('sumber', 'like', "%{$search}%");
                });
            }

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Date range filter
            if ($request->has('tanggal_dari') && !empty($request->tanggal_dari)) {
                $query->whereDate('tanggal', '>=', $request->tanggal_dari);
            }
            if ($request->has('tanggal_sampai') && !empty($request->tanggal_sampai)) {
                $query->whereDate('tanggal', '<=', $request->tanggal_sampai);
            }

            // Amount range filter
            if ($request->has('jumlah_min') && !empty($request->jumlah_min)) {
                $query->where('jumlah', '>=', $request->jumlah_min);
            }
            if ($request->has('jumlah_max') && !empty($request->jumlah_max)) {
                $query->where('jumlah', '<=', $request->jumlah_max);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'tanggal');
            $sortDir = $request->get('sort_dir', 'desc');
            $query->orderBy($sortBy, $sortDir);

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $pendapatan = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $pendapatan->items(),
                'pagination' => [
                    'current_page' => $pendapatan->currentPage(),
                    'per_page' => $pendapatan->perPage(),
                    'total' => $pendapatan->total(),
                    'last_page' => $pendapatan->lastPage(),
                    'has_more' => $pendapatan->hasMorePages(),
                ],
                'summary' => [
                    'total_amount' => $query->sum('jumlah'),
                    'count' => $query->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pendapatan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): JsonResponse
    {
        try {
            $userId = Auth::id();
            $today = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();

            $stats = [
                'today' => [
                    'patients' => Pasien::where('input_by', $userId)
                        ->whereDate('created_at', $today)
                        ->count(),
                    'tindakan' => Tindakan::where('input_by', $userId)
                        ->whereDate('tanggal_tindakan', $today)
                        ->count(),
                    'revenue' => PendapatanHarian::where('input_by', $userId)
                        ->whereDate('tanggal', $today)
                        ->sum('jumlah'),
                    'expenses' => PengeluaranHarian::where('input_by', $userId)
                        ->whereDate('tanggal', $today)
                        ->sum('jumlah'),
                ],
                'month' => [
                    'patients' => Pasien::where('input_by', $userId)
                        ->whereDate('created_at', '>=', $thisMonth)
                        ->count(),
                    'tindakan' => Tindakan::where('input_by', $userId)
                        ->whereDate('tanggal_tindakan', '>=', $thisMonth)
                        ->count(),
                    'revenue' => PendapatanHarian::where('input_by', $userId)
                        ->whereDate('tanggal', '>=', $thisMonth)
                        ->sum('jumlah'),
                    'expenses' => PengeluaranHarian::where('input_by', $userId)
                        ->whereDate('tanggal', '>=', $thisMonth)
                        ->sum('jumlah'),
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik dashboard: ' . $e->getMessage()
            ], 500);
        }
    }
}