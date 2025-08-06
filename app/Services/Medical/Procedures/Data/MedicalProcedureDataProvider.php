<?php

namespace App\Services\Medical\Procedures\Data;

use Illuminate\Support\Collection;

class MedicalProcedureDataProvider
{
    /**
     * Get comprehensive raw medical procedure data
     * 
     * This method provides the complete dataset of medical procedures
     * with enhanced metadata for intelligent fee calculation
     */
    public function getRawProcedureData(): Collection
    {
        return collect([
            [
                'nama' => 'Injeksi Intramuskular (IM)',
                'tarif' => 30000,
                'kategori' => 'tindakan',
                'complexity' => 'standard',
                'requires_doctor' => false,
                'kode' => 'INJ001',
                'deskripsi' => 'Pemberian injeksi melalui otot dengan teknik aseptik untuk absorpsi obat yang optimal'
            ],
            [
                'nama' => 'Injeksi Intravena (IV)',
                'tarif' => 35000,
                'kategori' => 'tindakan',
                'complexity' => 'standard',
                'requires_doctor' => false,
                'kode' => 'INJ002',
                'deskripsi' => 'Pemberian injeksi langsung ke pembuluh darah vena untuk efek obat yang cepat'
            ],
            [
                'nama' => 'Pemasangan Infus',
                'tarif' => 75000,
                'kategori' => 'tindakan',
                'complexity' => 'standard',
                'requires_doctor' => false,
                'kode' => 'INF001',
                'deskripsi' => 'Pemasangan jalur infus intravena untuk pemberian cairan dan obat kontinyu'
            ],
            [
                'nama' => 'Lepas Infus',
                'tarif' => 25000,
                'kategori' => 'tindakan',
                'complexity' => 'simple',
                'requires_doctor' => false,
                'kode' => 'INF002',
                'deskripsi' => 'Pelepasan jalur infus dengan teknik steril dan pemantauan lokasi tusukan'
            ],
            [
                'nama' => 'Pemasangan Kateter',
                'tarif' => 75000,
                'kategori' => 'tindakan',
                'complexity' => 'standard',
                'requires_doctor' => false,
                'kode' => 'KAT001',
                'deskripsi' => 'Pemasangan kateter urine untuk drainase kandung kemih dengan teknik aseptik'
            ],
            [
                'nama' => 'Lepas Kateter',
                'tarif' => 25000,
                'kategori' => 'tindakan',
                'complexity' => 'simple',
                'requires_doctor' => false,
                'kode' => 'KAT002',
                'deskripsi' => 'Pelepasan kateter urine dengan pemantauan kondisi saluran kemih'
            ],
            [
                'nama' => 'Jahit Luka (1–4 jahitan)',
                'tarif' => 75000,
                'kategori' => 'tindakan',
                'complexity' => 'complex',
                'requires_doctor' => true,
                'kode' => 'JAH001',
                'deskripsi' => 'Penjahitan luka kecil 1-4 jahitan dengan teknik steril untuk penyembuhan optimal'
            ],
            [
                'nama' => 'Lepas Jahitan (1 jahitan)',
                'tarif' => 5500,
                'kategori' => 'tindakan',
                'complexity' => 'simple',
                'requires_doctor' => false,
                'kode' => 'JAH002',
                'deskripsi' => 'Pelepasan jahitan dengan evaluasi penyembuhan luka dan perawatan lanjutan'
            ],
            [
                'nama' => 'Pemeriksaan Buta Warna',
                'tarif' => 25000,
                'kategori' => 'pemeriksaan',
                'complexity' => 'standard',
                'requires_doctor' => false,
                'kode' => 'PER001',
                'deskripsi' => 'Tes pemeriksaan buta warna menggunakan chart Ishihara untuk evaluasi penglihatan warna'
            ],
            [
                'nama' => 'Pemeriksaan Visus Mata',
                'tarif' => 15000,
                'kategori' => 'pemeriksaan',
                'complexity' => 'simple',
                'requires_doctor' => false,
                'kode' => 'PER002',
                'deskripsi' => 'Pemeriksaan ketajaman penglihatan mata menggunakan chart Snellen'
            ],
            [
                'nama' => 'Surat Keterangan Sehat',
                'tarif' => 25000,
                'kategori' => 'lainnya',
                'complexity' => 'standard',
                'requires_doctor' => true,
                'kode' => 'SUR001',
                'deskripsi' => 'Penerbitan surat keterangan sehat berdasarkan pemeriksaan medis komprehensif'
            ],
            [
                'nama' => 'Nebulizer',
                'tarif' => 100000,
                'kategori' => 'tindakan',
                'complexity' => 'standard',
                'requires_doctor' => false,
                'kode' => 'NEB001',
                'deskripsi' => 'Terapi nebulizer untuk gangguan pernapasan dengan obat bronkodilator atau mukolitik'
            ],
            [
                'nama' => 'Perawatan Luka Kecil (<5 cm)',
                'tarif' => 25000,
                'kategori' => 'tindakan',
                'complexity' => 'standard',
                'requires_doctor' => false,
                'kode' => 'LUK001',
                'deskripsi' => 'Perawatan dan pembersihan luka kecil dengan antiseptik dan pembalutan steril'
            ],
            [
                'nama' => 'Ekstraksi Korpus Alienum (hidung/telinga/mata)',
                'tarif' => 50000,
                'kategori' => 'tindakan',
                'complexity' => 'complex',
                'requires_doctor' => true,
                'kode' => 'EKS001',
                'deskripsi' => 'Pengangkatan benda asing dari hidung, telinga, atau mata dengan teknik khusus'
            ],
            [
                'nama' => 'Ekstraksi Kuku',
                'tarif' => 130000,
                'kategori' => 'tindakan',
                'complexity' => 'complex',
                'requires_doctor' => true,
                'kode' => 'EKS002',
                'deskripsi' => 'Pencabutan kuku yang bermasalah dengan anestesi lokal dan perawatan luka'
            ],
            [
                'nama' => 'Oksigenasi (2 jam pertama)',
                'tarif' => 40000,
                'kategori' => 'tindakan',
                'complexity' => 'standard',
                'requires_doctor' => false,
                'kode' => 'OKS001',
                'deskripsi' => 'Terapi oksigen 2 jam pertama untuk membantu pernapasan dan oksigenasi jaringan'
            ],
            [
                'nama' => 'Oksigenasi per jam selanjutnya',
                'tarif' => 40000,
                'kategori' => 'tindakan',
                'complexity' => 'standard',
                'requires_doctor' => false,
                'kode' => 'OKS002',
                'deskripsi' => 'Terapi oksigen berkelanjutan per jam setelah 2 jam pertama dengan pemantauan saturasi'
            ],
            [
                'nama' => 'Insisi/Eksisi Luka Besar',
                'tarif' => 100000,
                'kategori' => 'tindakan',
                'complexity' => 'complex',
                'requires_doctor' => true,
                'kode' => 'INS001',
                'deskripsi' => 'Tindakan insisi atau eksisi pada luka besar dengan anestesi lokal dan jahitan'
            ]
        ]);
    }

    /**
     * Get procedure data grouped by category
     */
    public function getProceduresByCategory(): Collection
    {
        return $this->getRawProcedureData()->groupBy('kategori');
    }

    /**
     * Get procedures that require doctor supervision
     */
    public function getDoctorRequiredProcedures(): Collection
    {
        return $this->getRawProcedureData()->filter(function ($procedure) {
            return $procedure['requires_doctor'] ?? false;
        });
    }

    /**
     * Get procedures by complexity level
     */
    public function getProceduresByComplexity(string $complexity): Collection
    {
        return $this->getRawProcedureData()->filter(function ($procedure) use ($complexity) {
            return ($procedure['complexity'] ?? 'standard') === $complexity;
        });
    }

    /**
     * Get procedure statistics
     */
    public function getProcedureStatistics(): array
    {
        $procedures = $this->getRawProcedureData();
        
        return [
            'total_procedures' => $procedures->count(),
            'categories' => $procedures->pluck('kategori')->unique()->count(),
            'doctor_required' => $procedures->where('requires_doctor', true)->count(),
            'complexity_distribution' => $procedures->groupBy('complexity')->map->count(),
            'category_distribution' => $procedures->groupBy('kategori')->map->count(),
            'tariff_range' => [
                'min' => $procedures->min('tarif'),
                'max' => $procedures->max('tarif'),
                'average' => $procedures->avg('tarif')
            ]
        ];
    }

    /**
     * Validate data structure integrity
     */
    public function validateDataStructure(): array
    {
        $procedures = $this->getRawProcedureData();
        $errors = [];
        $requiredFields = ['nama', 'tarif', 'kategori', 'kode'];
        
        foreach ($procedures as $index => $procedure) {
            foreach ($requiredFields as $field) {
                if (!isset($procedure[$field]) || empty($procedure[$field])) {
                    $errors[] = "Procedure at index {$index} missing required field: {$field}";
                }
            }
            
            // Validate tariff is numeric and positive
            if (!is_numeric($procedure['tarif']) || $procedure['tarif'] <= 0) {
                $errors[] = "Procedure '{$procedure['nama']}' has invalid tariff value";
            }
            
            // Validate category is valid enum
            $validCategories = ['konsultasi', 'pemeriksaan', 'tindakan', 'obat', 'lainnya'];
            if (!in_array($procedure['kategori'], $validCategories)) {
                $errors[] = "Procedure '{$procedure['nama']}' has invalid category: {$procedure['kategori']}";
            }
            
            // Validate code format
            if (!preg_match('/^[A-Z]{3}[0-9]{3}$/', $procedure['kode'])) {
                $errors[] = "Procedure '{$procedure['nama']}' has invalid code format: {$procedure['kode']}";
            }
        }
        
        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'total_procedures' => $procedures->count()
        ];
    }
}