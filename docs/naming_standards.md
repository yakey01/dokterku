# Naming Standards for Dokterku Application

## Overview
This document establishes consistent naming conventions for the Dokterku Laravel application to improve code maintainability and developer experience.

## Database Tables
Current tables use singular Indonesian names. This pattern will be maintained for consistency:
- `pasien` (patient)
- `pendapatan` (income)
- `pengeluaran` (expense)
- `tindakan` (medical procedure)
- `dokter` (doctor)
- `pegawai` (employee)

## Model Names
Models should use singular PascalCase Indonesian names matching table names:
- `Pasien`
- `Pendapatan`
- `Pengeluaran`
- `Tindakan`
- `Dokter`
- `Pegawai`

## Filament Resource Names
Resources should follow the pattern `{ModelName}Resource`:
- `PasienResource`
- `PendapatanResource`
- `PengeluaranResource`
- `TindakanResource`
- `DokterResource`
- `PegawaiResource`

## Route Names
Routes should use kebab-case Indonesian names:
- `pasien` (for /pasien routes)
- `pendapatan` (for /pendapatan routes)
- `pengeluaran` (for /pengeluaran routes)
- `tindakan` (for /tindakan routes)

## URL Patterns
URLs should use kebab-case Indonesian names:
- `/pasien`
- `/pendapatan`
- `/pengeluaran`
- `/tindakan`
- `/dokter`
- `/pegawai`

## Resource Labels
Resource labels should use proper Indonesian capitalization:
- "Pasien" (Patient)
- "Pendapatan" (Income)
- "Pengeluaran" (Expense)
- "Tindakan" (Medical Procedure)
- "Dokter" (Doctor)
- "Pegawai" (Employee)

## Navigation Labels
Navigation items should use Indonesian terms:
- "Manajemen Pasien" (Patient Management)
- "Keuangan" (Finance)
  - "Pendapatan" (Income)
  - "Pengeluaran" (Expense)
- "Tindakan Medis" (Medical Procedures)
- "Manajemen Dokter" (Doctor Management)
- "Manajemen Pegawai" (Employee Management)

## API Endpoint Naming
API endpoints should use kebab-case:
- `/api/pasien`
- `/api/pendapatan`
- `/api/pengeluaran`
- `/api/tindakan`
- `/api/dokter`
- `/api/pegawai`

## Form Field Names
Form fields should use snake_case Indonesian names:
- `nama_pasien`
- `tanggal_tindakan`
- `nominal_pendapatan`
- `keterangan_pengeluaran`

## Validation Rules
Validation rules should reference field names clearly:
- `nama_pasien.required`
- `tanggal_tindakan.required`
- `nominal_pendapatan.required|numeric`

## File Naming
- Controllers: `{ModelName}Controller`
- Requests: `{ModelName}Request`
- Policies: `{ModelName}Policy`
- Factories: `{ModelName}Factory`
- Seeders: `{ModelName}Seeder`

## Priority Implementation
1. **HIGH**: Resource labels and navigation consistency
2. **MEDIUM**: Form field naming standardization
3. **LOW**: URL pattern optimization (requires route changes)

## Standards Compliance
All new code should follow these conventions. Existing code should be gradually updated during maintenance cycles.