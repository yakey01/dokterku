<?php

namespace App\Filament\Bendahara\Resources\LaporanKeuanganReportResource\Actions;

use App\Services\JaspelReportService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ExportJaspelAction
{
    protected JaspelReportService $jaspelService;

    public function __construct(JaspelReportService $jaspelService)
    {
        $this->jaspelService = $jaspelService;
    }

    /**
     * Export to Excel format
     */
    public function exportToExcel(string $role = 'semua', array $filters = []): string
    {
        $data = $this->jaspelService->getValidatedJaspelByRole($role, $filters);
        $summary = $this->jaspelService->getRoleSummaryStats($filters);
        
        // Generate CSV content (simplified Excel export)
        $csv = $this->generateCsvContent($data, $summary, $role, $filters);
        
        // Create temporary file
        $filename = 'laporan_jaspel_' . $role . '_' . Carbon::now()->format('Ymd_His') . '.csv';
        $filepath = storage_path('app/temp/' . $filename);
        
        // Ensure directory exists
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        file_put_contents($filepath, $csv);
        
        return $filepath;
    }

    /**
     * Export to PDF format
     */
    public function exportToPdf(string $role = 'semua', array $filters = []): string
    {
        $data = $this->jaspelService->getValidatedJaspelByRole($role, $filters);
        $summary = $this->jaspelService->getRoleSummaryStats($filters);
        
        // Generate HTML content for PDF
        $html = $this->generatePdfHtml($data, $summary, $role, $filters);
        
        // Create temporary HTML file (in a real implementation, use DOMPDF or similar)
        $filename = 'laporan_jaspel_' . $role . '_' . Carbon::now()->format('Ymd_His') . '.html';
        $filepath = storage_path('app/temp/' . $filename);
        
        // Ensure directory exists
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        file_put_contents($filepath, $html);
        
        return $filepath;
    }

    /**
     * Generate CSV content
     */
    protected function generateCsvContent(Collection $data, array $summary, string $role, array $filters): string
    {
        $csv = [];
        
        // Header
        $csv[] = "LAPORAN JASPEL TERVALIDASI";
        $csv[] = "Role: " . ucfirst($role);
        $csv[] = "Periode: " . ($filters['date_from'] ?? 'Semua waktu') . " - " . ($filters['date_to'] ?? 'Semua waktu');
        $csv[] = "Digenerate: " . Carbon::now()->format('d M Y H:i:s');
        $csv[] = "";

        // Summary
        $csv[] = "RINGKASAN:";
        foreach ($summary as $stat) {
            $csv[] = $stat['display_name'] . ": " . $stat['user_count'] . " orang, Total: Rp " . number_format($stat['total_jaspel'], 0, ',', '.');
        }
        $csv[] = "";

        // Data header
        $csv[] = "Nama,Role,Total Tindakan,Total Jaspel,Validasi Terakhir,Email";

        // Data rows
        foreach ($data as $row) {
            $csv[] = implode(',', [
                '"' . $row->name . '"',
                $this->formatRole($row->role_name),
                $row->total_tindakan,
                $row->total_jaspel,
                $row->last_validation ? Carbon::parse($row->last_validation)->format('d M Y H:i') : 'Tidak ada',
                '"' . $row->email . '"'
            ]);
        }

        return implode("\n", $csv);
    }

    /**
     * Generate PDF HTML content
     */
    protected function generatePdfHtml(Collection $data, array $summary, string $role, array $filters): string
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Jaspel Tervalidasi</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #1f2937; }
        .header p { margin: 5px 0; color: #6b7280; }
        .summary { background: #f9fafb; padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .summary h3 { margin-top: 0; color: #374151; }
        .summary-item { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background-color: #f3f4f6; font-weight: bold; color: #374151; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { padding: 2px 8px; border-radius: 4px; font-size: 10px; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-gray { background: #f3f4f6; color: #374151; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 LAPORAN JASPEL TERVALIDASI</h1>
        <p>Role: <strong>' . strtoupper($role) . '</strong></p>
        <p>Periode: ' . ($filters['date_from'] ?? 'Semua waktu') . ' - ' . ($filters['date_to'] ?? 'Semua waktu') . '</p>
        <p>Digenerate: ' . Carbon::now()->format('d M Y H:i:s') . '</p>
    </div>';

        if (!empty($summary)) {
            $html .= '<div class="summary">
                <h3>📊 Ringkasan per Role</h3>';
            
            foreach ($summary as $stat) {
                $html .= '<div class="summary-item">
                    <strong>' . $stat['display_name'] . ':</strong> ' . 
                    $stat['user_count'] . ' orang, Total: Rp ' . number_format($stat['total_jaspel'], 0, ',', '.') . 
                    ' (' . number_format($stat['total_tindakan']) . ' tindakan)
                </div>';
            }
            
            $html .= '</div>';
        }

        $html .= '<table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Role</th>
                    <th class="text-center">Total Tindakan</th>
                    <th class="text-right">Total Jaspel</th>
                    <th>Validasi Terakhir</th>
                </tr>
            </thead>
            <tbody>';

        $no = 1;
        foreach ($data as $row) {
            $badgeClass = $this->getRoleBadgeClass($row->role_name);
            $html .= '<tr>
                <td class="text-center">' . $no++ . '</td>
                <td>' . htmlspecialchars($row->name) . '</td>
                <td><span class="badge ' . $badgeClass . '">' . $this->formatRole($row->role_name) . '</span></td>
                <td class="text-center">' . number_format($row->total_tindakan) . '</td>
                <td class="text-right">Rp ' . number_format($row->total_jaspel, 0, ',', '.') . '</td>
                <td>' . ($row->last_validation ? Carbon::parse($row->last_validation)->format('d M Y H:i') : 'Tidak ada') . '</td>
            </tr>';
        }

        $html .= '</tbody>
        </table>
        
        <div style="margin-top: 30px; font-size: 10px; color: #6b7280;">
            <p>Total Records: ' . $data->count() . ' | Generated by Bendahara Dashboard</p>
        </div>
    </body>
</html>';

        return $html;
    }

    /**
     * Format role name for display
     */
    protected function formatRole(string $role): string
    {
        return match ($role) {
            'dokter' => 'Dokter',
            'dokter_gigi' => 'Dokter Gigi',
            'paramedis' => 'Paramedis',
            'non_paramedis' => 'Non-Paramedis',
            'petugas' => 'Petugas',
            default => ucfirst($role),
        };
    }

    /**
     * Get badge CSS class for role
     */
    protected function getRoleBadgeClass(string $role): string
    {
        return match ($role) {
            'dokter', 'dokter_gigi' => 'badge-success',
            'paramedis' => 'badge-info',
            'non_paramedis' => 'badge-warning',
            'petugas' => 'badge-gray',
            default => 'badge-gray',
        };
    }

    /**
     * Download file and clean up
     */
    public function downloadAndCleanup(string $filepath, string $downloadName): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $response = response()->download($filepath, $downloadName)->deleteFileAfterSend();
        
        return $response;
    }
}