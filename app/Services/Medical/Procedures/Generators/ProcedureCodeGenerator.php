<?php

namespace App\Services\Medical\Procedures\Generators;

use App\Models\JenisTindakan;
use Illuminate\Support\Str;

class ProcedureCodeGenerator
{
    /**
     * Prefix mapping for different procedure types
     */
    protected array $procedurePrefixes = [
        'injeksi' => 'INJ',
        'infus' => 'INF',
        'kateter' => 'KAT',
        'jahit' => 'JAH',
        'pemeriksaan' => 'PER',
        'surat' => 'SUR',
        'nebulizer' => 'NEB',
        'perawatan' => 'LUK',
        'ekstraksi' => 'EKS',
        'oksigenasi' => 'OKS',
        'insisi' => 'INS',
        'eksisi' => 'INS',
        'konsultasi' => 'KON',
        'obat' => 'OBT',
        'lainnya' => 'LAI'
    ];

    /**
     * Category-based prefixes as fallback
     */
    protected array $categoryPrefixes = [
        'konsultasi' => 'KON',
        'pemeriksaan' => 'PER',
        'tindakan' => 'TIN',
        'obat' => 'OBT',
        'lainnya' => 'LAI'
    ];

    /**
     * Generate procedure code based on name and category
     */
    public function generateCode(string $procedureName, string $category = 'tindakan'): string
    {
        // Clean and normalize the procedure name
        $cleanName = $this->cleanProcedureName($procedureName);
        
        // Determine prefix based on procedure name keywords
        $prefix = $this->determinePrefix($cleanName, $category);
        
        // Generate sequential number
        $sequentialNumber = $this->getNextSequentialNumber($prefix);
        
        // Format the code
        $code = $prefix . str_pad($sequentialNumber, 3, '0', STR_PAD_LEFT);
        
        // Ensure uniqueness
        return $this->ensureUniqueCode($code);
    }

    /**
     * Generate multiple codes for batch processing
     */
    public function generateBatchCodes(array $procedures): array
    {
        $codes = [];
        $usedCodes = $this->getExistingCodes();
        
        foreach ($procedures as $index => $procedure) {
            $code = $this->generateCode(
                $procedure['nama'],
                $procedure['kategori'] ?? 'tindakan'
            );
            
            // Ensure no duplicates in batch
            $originalCode = $code;
            $counter = 1;
            while (in_array($code, $usedCodes) || in_array($code, $codes)) {
                $prefix = substr($originalCode, 0, 3);
                $number = (int)substr($originalCode, 3) + $counter;
                $code = $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
                $counter++;
            }
            
            $codes[] = $code;
            $usedCodes[] = $code; // Add to used codes to prevent duplicates
        }
        
        return $codes;
    }

    /**
     * Clean procedure name for processing
     */
    protected function cleanProcedureName(string $name): string
    {
        // Convert to lowercase and remove special characters
        $cleaned = strtolower($name);
        $cleaned = preg_replace('/[^a-z0-9\s]/', ' ', $cleaned);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        
        return trim($cleaned);
    }

    /**
     * Determine appropriate prefix based on procedure name
     */
    protected function determinePrefix(string $cleanName, string $category): string
    {
        // Check for specific keywords in procedure name
        foreach ($this->procedurePrefixes as $keyword => $prefix) {
            if (str_contains($cleanName, $keyword)) {
                return $prefix;
            }
        }
        
        // Check for partial matches with more flexibility
        $words = explode(' ', $cleanName);
        foreach ($words as $word) {
            foreach ($this->procedurePrefixes as $keyword => $prefix) {
                if (Str::contains($word, $keyword) || Str::contains($keyword, $word)) {
                    return $prefix;
                }
            }
        }
        
        // Fallback to category-based prefix
        return $this->categoryPrefixes[$category] ?? 'GEN';
    }

    /**
     * Get next sequential number for a given prefix
     */
    protected function getNextSequentialNumber(string $prefix): int
    {
        $existingCodes = JenisTindakan::where('kode', 'LIKE', $prefix . '%')
            ->pluck('kode')
            ->toArray();
        
        if (empty($existingCodes)) {
            return 1;
        }
        
        // Extract numbers from existing codes
        $numbers = [];
        foreach ($existingCodes as $code) {
            if (str_starts_with($code, $prefix)) {
                $numberPart = substr($code, strlen($prefix));
                if (is_numeric($numberPart)) {
                    $numbers[] = (int)$numberPart;
                }
            }
        }
        
        return empty($numbers) ? 1 : max($numbers) + 1;
    }

    /**
     * Ensure the generated code is unique
     */
    protected function ensureUniqueCode(string $code): string
    {
        $originalCode = $code;
        $counter = 1;
        
        while ($this->codeExists($code)) {
            $prefix = substr($originalCode, 0, 3);
            $number = (int)substr($originalCode, 3) + $counter;
            $code = $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
            $counter++;
            
            // Prevent infinite loop
            if ($counter > 999) {
                throw new \RuntimeException("Unable to generate unique code for prefix: {$prefix}");
            }
        }
        
        return $code;
    }

    /**
     * Check if code already exists
     */
    protected function codeExists(string $code): bool
    {
        return JenisTindakan::where('kode', $code)->exists();
    }

    /**
     * Get all existing codes from database
     */
    protected function getExistingCodes(): array
    {
        return JenisTindakan::pluck('kode')->toArray();
    }

    /**
     * Validate code format
     */
    public function validateCodeFormat(string $code): bool
    {
        return preg_match('/^[A-Z]{3}[0-9]{3}$/', $code) === 1;
    }

    /**
     * Parse code to extract components
     */
    public function parseCode(string $code): array
    {
        if (!$this->validateCodeFormat($code)) {
            throw new \InvalidArgumentException("Invalid code format: {$code}");
        }
        
        $prefix = substr($code, 0, 3);
        $number = (int)substr($code, 3);
        
        // Determine category from prefix
        $category = array_search($prefix, $this->categoryPrefixes) ?: 'unknown';
        
        // Determine procedure type from prefix
        $procedureType = array_search($prefix, $this->procedurePrefixes) ?: 'unknown';
        
        return [
            'code' => $code,
            'prefix' => $prefix,
            'number' => $number,
            'sequential_number' => sprintf('%03d', $number),
            'category' => $category,
            'procedure_type' => $procedureType
        ];
    }

    /**
     * Generate code statistics and analysis
     */
    public function getCodeStatistics(): array
    {
        $existingCodes = $this->getExistingCodes();
        
        $prefixCount = [];
        $categoryCount = [];
        
        foreach ($existingCodes as $code) {
            if ($this->validateCodeFormat($code)) {
                $parsed = $this->parseCode($code);
                $prefixCount[$parsed['prefix']] = ($prefixCount[$parsed['prefix']] ?? 0) + 1;
                $categoryCount[$parsed['category']] = ($categoryCount[$parsed['category']] ?? 0) + 1;
            }
        }
        
        return [
            'total_codes' => count($existingCodes),
            'valid_format_codes' => count(array_filter($existingCodes, [$this, 'validateCodeFormat'])),
            'prefix_distribution' => $prefixCount,
            'category_distribution' => $categoryCount,
            'available_prefixes' => array_values($this->procedurePrefixes),
            'next_available_numbers' => $this->getNextAvailableNumbers()
        ];
    }

    /**
     * Get next available numbers for each prefix
     */
    protected function getNextAvailableNumbers(): array
    {
        $nextNumbers = [];
        
        foreach ($this->procedurePrefixes as $type => $prefix) {
            $nextNumbers[$prefix] = $this->getNextSequentialNumber($prefix);
        }
        
        return $nextNumbers;
    }

    /**
     * Suggest code for a procedure name
     */
    public function suggestCode(string $procedureName, string $category = 'tindakan'): array
    {
        $cleanName = $this->cleanProcedureName($procedureName);
        $suggestedPrefix = $this->determinePrefix($cleanName, $category);
        $nextNumber = $this->getNextSequentialNumber($suggestedPrefix);
        $suggestedCode = $suggestedPrefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        
        // Get alternative prefixes
        $alternatives = [];
        foreach ($this->categoryPrefixes as $cat => $prefix) {
            if ($prefix !== $suggestedPrefix) {
                $altNumber = $this->getNextSequentialNumber($prefix);
                $alternatives[] = [
                    'prefix' => $prefix,
                    'code' => $prefix . str_pad($altNumber, 3, '0', STR_PAD_LEFT),
                    'category' => $cat,
                    'next_number' => $altNumber
                ];
            }
        }
        
        return [
            'suggested_code' => $suggestedCode,
            'prefix' => $suggestedPrefix,
            'next_number' => $nextNumber,
            'confidence' => $this->calculateSuggestionConfidence($cleanName, $suggestedPrefix),
            'alternatives' => $alternatives,
            'reasoning' => $this->explainCodeSuggestion($cleanName, $suggestedPrefix, $category)
        ];
    }

    /**
     * Calculate confidence level for code suggestion
     */
    protected function calculateSuggestionConfidence(string $cleanName, string $suggestedPrefix): float
    {
        $confidence = 0.5; // Base confidence
        
        // Increase confidence for direct keyword matches
        foreach ($this->procedurePrefixes as $keyword => $prefix) {
            if ($prefix === $suggestedPrefix && str_contains($cleanName, $keyword)) {
                $confidence += 0.3;
                break;
            }
        }
        
        // Increase confidence for partial matches
        $words = explode(' ', $cleanName);
        foreach ($words as $word) {
            foreach ($this->procedurePrefixes as $keyword => $prefix) {
                if ($prefix === $suggestedPrefix && (Str::contains($word, $keyword) || Str::contains($keyword, $word))) {
                    $confidence += 0.2;
                    break 2;
                }
            }
        }
        
        return min(1.0, $confidence);
    }

    /**
     * Explain the reasoning behind code suggestion
     */
    protected function explainCodeSuggestion(string $cleanName, string $suggestedPrefix, string $category): string
    {
        // Check for direct keyword match
        foreach ($this->procedurePrefixes as $keyword => $prefix) {
            if ($prefix === $suggestedPrefix && str_contains($cleanName, $keyword)) {
                return "Direct keyword match: '{$keyword}' found in procedure name";
            }
        }
        
        // Check for partial match
        $words = explode(' ', $cleanName);
        foreach ($words as $word) {
            foreach ($this->procedurePrefixes as $keyword => $prefix) {
                if ($prefix === $suggestedPrefix && (Str::contains($word, $keyword) || Str::contains($keyword, $word))) {
                    return "Partial keyword match: '{$word}' relates to '{$keyword}'";
                }
            }
        }
        
        // Fallback to category
        if (isset($this->categoryPrefixes[$category]) && $this->categoryPrefixes[$category] === $suggestedPrefix) {
            return "Category-based prefix: '{$category}' category mapped to '{$suggestedPrefix}'";
        }
        
        return "Default suggestion based on available patterns";
    }
}