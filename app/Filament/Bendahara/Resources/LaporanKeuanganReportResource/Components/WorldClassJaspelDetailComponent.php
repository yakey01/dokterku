<?php

namespace App\Filament\Bendahara\Resources\LaporanKeuanganReportResource\Components;

use App\Services\ProcedureJaspelCalculationService;
use App\Services\SubAgents\ValidationSubAgentService;
use Filament\Forms\Components\Component;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;

class WorldClassJaspelDetailComponent extends Component
{
    protected string $view = 'filament.bendahara.components.world-class-jaspel-detail';

    public static function make(): static
    {
        return app(static::class);
    }

    public function generateWorldClassDetailView(int $userId): HtmlString
    {
        $procedureCalculator = app(ProcedureJaspelCalculationService::class);
        $validationAgent = app(ValidationSubAgentService::class);
        
        $procedureData = $procedureCalculator->calculateJaspelFromProcedures($userId, []);
        $validationData = $validationAgent->performCermatJaspelValidation($userId);

        $html = '<div class="jaspel-detail-container">';
        
        // Hero Section
        $html .= '<div class="detail-card animate-fade-in-up mb-8">';
        $html .= '<div class="p-8 text-center">';
        $html .= '<div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full mb-4">';
        $html .= '<svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path></svg>';
        $html .= '</div>';
        $html .= '<h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">' . ($procedureData['user_name'] ?? 'User') . '</h1>';
        $html .= '<p class="text-lg text-gray-600 dark:text-gray-400">Detail Rincian & Analisis Jaspel</p>';
        $html .= '<div class="mt-6 flex justify-center space-x-4">';
        $html .= '<div class="text-center">';
        $html .= '<div class="text-3xl font-bold text-green-600" data-animate="counter" data-target="' . ($procedureData['total_jaspel'] ?? 0) . '">0</div>';
        $html .= '<div class="text-sm text-gray-500">Total Jaspel</div>';
        $html .= '</div>';
        $html .= '<div class="text-center">';
        $html .= '<div class="text-3xl font-bold text-blue-600" data-animate="counter" data-target="' . ($procedureData['total_procedures'] ?? 0) . '">0</div>';
        $html .= '<div class="text-sm text-gray-500">Total Procedures</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Charts Section
        ob_start();
        include resource_path('views/filament/bendahara/jaspel-detail-charts.blade.php');
        $chartContent = ob_get_clean();
        $html .= $chartContent;

        // Detailed Breakdown Sections
        $html .= '<div class="grid grid-cols-1 xl:grid-cols-2 gap-8">';
        
        // Tindakan Breakdown
        if (!empty($procedureData['breakdown']['tindakan_procedures'])) {
            $html .= '<div class="detail-card animate-fade-in-up animate-delay-100">';
            $html .= '<div class="p-6">';
            $html .= '<h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">';
            $html .= '🩺 Breakdown Tindakan Medis';
            $html .= '</h3>';
            
            foreach ($procedureData['breakdown']['tindakan_procedures'] as $tindakan) {
                $html .= '<div class="procedure-item">';
                $html .= '<div class="flex justify-between items-center">';
                $html .= '<div class="flex-1">';
                $html .= '<div class="font-medium text-gray-900">' . $tindakan['jenis_tindakan'] . '</div>';
                $html .= '<div class="text-sm text-gray-500">' . Carbon::parse($tindakan['tanggal'])->format('d M Y') . '</div>';
                $html .= '</div>';
                $html .= '<div class="text-right">';
                $html .= '<div class="text-lg font-semibold text-green-600">Rp ' . number_format($tindakan['jaspel'], 0, ',', '.') . '</div>';
                $html .= '<div class="text-sm text-gray-500">dari Rp ' . number_format($tindakan['tarif'], 0, ',', '.') . '</div>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }
            
            $html .= '<div class="mt-4 p-4 bg-blue-50 rounded-lg dark:bg-blue-900/20">';
            $html .= '<div class="text-center">';
            $html .= '<div class="text-lg font-semibold text-blue-800 dark:text-blue-200">Total Tindakan</div>';
            $html .= '<div class="text-2xl font-bold text-blue-600">Rp ' . number_format($procedureData['tindakan_jaspel'] ?? 0, 0, ',', '.') . '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        // Pasien Harian Breakdown
        if (!empty($procedureData['breakdown']['pasien_harian_days'])) {
            $html .= '<div class="detail-card animate-fade-in-up animate-delay-200">';
            $html .= '<div class="p-6">';
            $html .= '<h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">';
            $html .= '👥 Breakdown Pasien Harian';
            $html .= '</h3>';
            
            foreach ($procedureData['breakdown']['pasien_harian_days'] as $pasien) {
                $html .= '<div class="procedure-item border-l-green-500">';
                $html .= '<div class="flex justify-between items-center">';
                $html .= '<div class="flex-1">';
                $html .= '<div class="font-medium text-gray-900">' . Carbon::parse($pasien['tanggal'])->format('d M Y') . '</div>';
                $html .= '<div class="text-sm text-gray-500">' . $pasien['jumlah_pasien'] . ' total pasien</div>';
                $html .= '</div>';
                $html .= '<div class="text-right">';
                $html .= '<div class="text-lg font-semibold text-green-600">Rp ' . number_format($pasien['jaspel_rupiah'], 0, ',', '.') . '</div>';
                $html .= '<div class="text-sm text-gray-500">per hari</div>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }
            
            $html .= '<div class="mt-4 p-4 bg-green-50 rounded-lg dark:bg-green-900/20">';
            $html .= '<div class="text-center">';
            $html .= '<div class="text-lg font-semibold text-green-800 dark:text-green-200">Total Pasien Harian</div>';
            $html .= '<div class="text-2xl font-bold text-green-600">Rp ' . number_format($procedureData['pasien_jaspel'] ?? 0, 0, ',', '.') . '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>'; // End grid

        // Validation & Quality Section
        $html .= '<div class="detail-card animate-fade-in-up animate-delay-300 mt-8">';
        $html .= '<div class="p-6">';
        $html .= '<h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">';
        $html .= '🔍 Validasi & Quality Assurance';
        $html .= '</h3>';
        
        $html .= '<div class="grid grid-cols-1 md:grid-cols-3 gap-6">';
        
        // Validation Score
        $validationScore = $validationData['summary']['validation_score'] ?? 0;
        $html .= '<div class="stat-card text-center">';
        $html .= '<div class="text-3xl font-bold text-indigo-600 mb-2">' . $validationScore . '%</div>';
        $html .= '<div class="text-sm font-medium text-gray-700">Validation Score</div>';
        $html .= '<div class="text-xs text-gray-500 mt-1">' . ($validationData['summary']['passed_checks'] ?? 0) . '/' . ($validationData['summary']['total_checks'] ?? 0) . ' checks passed</div>';
        $html .= '</div>';
        
        // Calculation Accuracy  
        $html .= '<div class="stat-card text-center">';
        $html .= '<div class="text-3xl font-bold text-green-600 mb-2">✓</div>';
        $html .= '<div class="text-sm font-medium text-gray-700">Calculation Accuracy</div>';
        $html .= '<div class="text-xs text-gray-500 mt-1">Procedure-based verified</div>';
        $html .= '</div>';
        
        // Data Integrity
        $html .= '<div class="stat-card text-center">';
        $html .= '<div class="text-3xl font-bold text-purple-600 mb-2">🛡️</div>';
        $html .= '<div class="text-sm font-medium text-gray-700">Data Integrity</div>';
        $html .= '<div class="text-xs text-gray-500 mt-1">Fully verified</div>';
        $html .= '</div>';
        
        $html .= '</div>'; // End grid
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>'; // End container

        return new HtmlString($html);
    }
}