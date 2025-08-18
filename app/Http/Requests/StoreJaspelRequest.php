<?php

namespace App\Http\Requests;

use App\Rules\ValidJaspelEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Store JASPEL Request Validation
 * 
 * Comprehensive validation for creating new JASPEL entries.
 * Prevents dummy data and enforces business rules.
 */
class StoreJaspelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return Auth::check(); // User must be authenticated
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        $context = [
            'user_id' => Auth::id(),
            'nominal' => $this->input('nominal'),
            'jenis_jaspel' => $this->input('jenis_jaspel'),
            'tindakan_id' => $this->input('tindakan_id'),
            'tanggal' => $this->input('tanggal'),
            'catatan_validasi' => $this->input('catatan_validasi')
        ];

        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id'
            ],
            'nominal' => [
                'required',
                'numeric',
                'min:1',
                'max:10000000',
                new ValidJaspelEntry('nominal', $context)
            ],
            'jenis_jaspel' => [
                'required',
                'string',
                'in:dokter_jaga_pagi,dokter_jaga_siang,dokter_jaga_malam,tindakan_emergency,konsultasi_khusus,paramedis,dokter_umum,dokter_spesialis',
                new ValidJaspelEntry('jenis_jaspel', $context)
            ],
            'tanggal' => [
                'required',
                'date',
                'before_or_equal:' . now()->addDays(7)->format('Y-m-d'),
                'after_or_equal:' . now()->subYear()->format('Y-m-d'),
                new ValidJaspelEntry('tanggal', $context)
            ],
            'tindakan_id' => [
                'nullable',
                'integer',
                'exists:tindakan,id',
                new ValidJaspelEntry('tindakan_id', $context)
            ],
            'shift_id' => [
                'nullable',
                'integer',
                'exists:shifts,id'
            ],
            'catatan_validasi' => [
                'nullable',
                'string',
                'max:1000'
            ],
            // Overall validation for business rules and duplicates
            'entry_validation' => [
                new ValidJaspelEntry('general', $context)
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages()
    {
        return [
            'nominal.required' => 'Nominal JASPEL wajib diisi.',
            'nominal.numeric' => 'Nominal JASPEL harus berupa angka.',
            'nominal.min' => 'Nominal JASPEL harus lebih besar dari 0.',
            'nominal.max' => 'Nominal JASPEL tidak boleh melebihi Rp 10,000,000.',
            
            'jenis_jaspel.required' => 'Jenis JASPEL wajib dipilih.',
            'jenis_jaspel.in' => 'Jenis JASPEL tidak valid.',
            
            'tanggal.required' => 'Tanggal JASPEL wajib diisi.',
            'tanggal.date' => 'Format tanggal tidak valid.',
            'tanggal.before_or_equal' => 'Tanggal JASPEL tidak boleh lebih dari 7 hari ke depan.',
            'tanggal.after_or_equal' => 'Tanggal JASPEL tidak boleh lebih dari 1 tahun yang lalu.',
            
            'tindakan_id.exists' => 'Tindakan yang dipilih tidak ditemukan.',
            'shift_id.exists' => 'Shift yang dipilih tidak ditemukan.',
            
            'catatan_validasi.max' => 'Catatan validasi tidak boleh lebih dari 1000 karakter.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes()
    {
        return [
            'nominal' => 'nominal JASPEL',
            'jenis_jaspel' => 'jenis JASPEL',
            'tanggal' => 'tanggal JASPEL',
            'tindakan_id' => 'tindakan',
            'shift_id' => 'shift',
            'catatan_validasi' => 'catatan validasi'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Additional business logic validation
            $this->validateConsistency($validator);
            $this->validateEnvironmentSpecific($validator);
        });
    }

    /**
     * Validate data consistency
     */
    private function validateConsistency($validator)
    {
        $jenisJaspel = $this->input('jenis_jaspel');
        $tindakanId = $this->input('tindakan_id');
        $nominal = $this->input('nominal');

        // Rule: Jaga shifts should not have tindakan_id
        if (in_array($jenisJaspel, ['dokter_jaga_pagi', 'dokter_jaga_siang', 'dokter_jaga_malam']) && $tindakanId) {
            $validator->errors()->add('tindakan_id', 'JASPEL jaga tidak boleh memiliki tindakan terkait.');
        }

        // Rule: Procedural JASPEL should have tindakan_id (with exceptions)
        if (in_array($jenisJaspel, ['paramedis', 'dokter_umum', 'dokter_spesialis']) && !$tindakanId) {
            if (empty($this->input('catatan_validasi')) || strlen($this->input('catatan_validasi')) < 20) {
                $validator->errors()->add('catatan_validasi', 
                    'JASPEL ' . $jenisJaspel . ' tanpa tindakan memerlukan catatan validasi minimal 20 karakter.');
            }
        }

        // Rule: Emergency procedures need higher justification
        if ($jenisJaspel === 'tindakan_emergency' && $nominal > 500000 && 
            (empty($this->input('catatan_validasi')) || strlen($this->input('catatan_validasi')) < 30)) {
            $validator->errors()->add('catatan_validasi', 
                'JASPEL emergency dengan nominal tinggi memerlukan catatan validasi minimal 30 karakter.');
        }
    }

    /**
     * Environment-specific validation
     */
    private function validateEnvironmentSpecific($validator)
    {
        // Prevent test data in production
        if (app()->environment('production')) {
            $this->validateProductionEnvironment($validator);
        }

        // Development environment warnings
        if (app()->environment(['local', 'development'])) {
            $this->validateDevelopmentEnvironment($validator);
        }
    }

    /**
     * Production environment validation
     */
    private function validateProductionEnvironment($validator)
    {
        $nominal = $this->input('nominal');
        
        // Stricter dummy data detection in production
        if ($this->isPotentialDummyData($nominal)) {
            $validator->errors()->add('nominal', 
                'Data terdeteksi sebagai dummy data. Tidak diizinkan di production.');
        }

        // Require documentation for large amounts
        if ($nominal > 2000000 && empty($this->input('catatan_validasi'))) {
            $validator->errors()->add('catatan_validasi', 
                'JASPEL dengan nominal > Rp 2,000,000 wajib memiliki catatan validasi di production.');
        }
    }

    /**
     * Development environment validation
     */
    private function validateDevelopmentEnvironment($validator)
    {
        $nominal = $this->input('nominal');
        
        // Warning for potential dummy data
        if ($this->isPotentialDummyData($nominal)) {
            // Add to session flash for warning (not blocking)
            session()->flash('warning', 
                'Data terdeteksi memiliki pola dummy. Pastikan ini adalah data valid.');
        }
    }

    /**
     * Check if data appears to be dummy/test data
     */
    private function isPotentialDummyData($nominal): bool
    {
        if (!$nominal) return false;

        // Round number patterns
        if ($nominal >= 100000 && $nominal % 10000 == 0) {
            return true;
        }

        // Common dummy patterns
        $dummyPatterns = [
            123456, 234567, 345678, 456789,
            111111, 222222, 333333, 444444, 555555,
            999999, 123123, 456456, 789789
        ];

        return in_array($nominal, $dummyPatterns);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Auto-fill user_id if not provided
        if (!$this->has('user_id')) {
            $this->merge(['user_id' => Auth::id()]);
        }

        // Normalize nominal (remove formatting)
        if ($this->has('nominal')) {
            $nominal = $this->input('nominal');
            // Remove currency formatting if present
            $nominal = preg_replace('/[^0-9.]/', '', $nominal);
            $this->merge(['nominal' => floatval($nominal)]);
        }

        // Add virtual field for overall validation
        $this->merge(['entry_validation' => true]);
    }
}