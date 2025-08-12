<?php

namespace App\Filament\Petugas\Widgets;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Tindakan;
use Filament\Widgets\Widget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialSummaryWidget extends Widget
{
    protected static string $view = 'filament.petugas.widgets.financial-summary-widget';
    
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];
    
    public function getFinancialData(): array
    {
        try {
            $today = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();
            $thisYear = Carbon::now()->startOfYear();
            
            // Daily Financial Summary
            $todayPendapatan = Pendapatan::whereDate('tanggal', $today)
                ->where('status_validasi', 'disetujui')->sum('nominal');
            $todayPengeluaran = Pengeluaran::whereDate('tanggal', $today)->sum('nominal');
            $todayNetIncome = $todayPendapatan - $todayPengeluaran;
            
            // Monthly Financial Summary
            $monthPendapatan = Pendapatan::whereBetween('tanggal', [$thisMonth, Carbon::now()])
                ->where('status_validasi', 'disetujui')->sum('nominal');
            $monthPengeluaran = Pengeluaran::whereBetween('tanggal', [$thisMonth, Carbon::now()])->sum('nominal');
            $monthNetIncome = $monthPendapatan - $monthPengeluaran;
            
            // Yearly Financial Summary
            $yearPendapatan = Pendapatan::whereBetween('tanggal', [$thisYear, Carbon::now()])
                ->where('status_validasi', 'disetujui')->sum('nominal');
            $yearPengeluaran = Pengeluaran::whereBetween('tanggal', [$thisYear, Carbon::now()])->sum('nominal');
            $yearNetIncome = $yearPendapatan - $yearPengeluaran;
            
            // Revenue by category (current month)
            $revenueByCategory = Pendapatan::selectRaw('kategori, SUM(nominal) as total')
                ->whereBetween('tanggal', [$thisMonth, Carbon::now()])
                ->where('status_validasi', 'disetujui')
                ->groupBy('kategori')
                ->orderBy('total', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'category' => $item->kategori ?? 'Lainnya',
                        'amount' => $item->total,
                        'percentage' => 0, // Will be calculated later
                    ];
                });
            
            // Calculate percentages
            $totalRevenue = $revenueByCategory->sum('amount');
            if ($totalRevenue > 0) {
                $revenueByCategory = $revenueByCategory->map(function ($item) use ($totalRevenue) {
                    $item['percentage'] = round(($item['amount'] / $totalRevenue) * 100, 1);
                    return $item;
                });
            }
            
            // Expense breakdown (current month)
            $expenseBreakdown = Pengeluaran::selectRaw('kategori, SUM(nominal) as total')
                ->whereBetween('tanggal', [$thisMonth, Carbon::now()])
                ->groupBy('kategori')
                ->orderBy('total', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'category' => $item->kategori ?? 'Lainnya',
                        'amount' => $item->total,
                    ];
                });
            
            // Payment methods analysis
            $paymentMethods = Pendapatan::selectRaw('metode_pembayaran, COUNT(*) as count, SUM(nominal) as total')
                ->whereBetween('tanggal', [$thisMonth, Carbon::now()])
                ->where('status_validasi', 'disetujui')
                ->whereNotNull('metode_pembayaran')
                ->groupBy('metode_pembayaran')
                ->orderBy('total', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'method' => $item->metode_pembayaran,
                        'count' => $item->count,
                        'amount' => $item->total,
                    ];
                });
            
            // Recent high-value transactions
            $recentTransactions = Pendapatan::where('status_validasi', 'disetujui')
                ->orderBy('nominal', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'description' => $transaction->deskripsi ?? 'Pendapatan',
                        'amount' => $transaction->nominal,
                        'date' => $transaction->tanggal->format('d M Y'),
                        'category' => $transaction->kategori ?? 'Lainnya',
                        'payment_method' => $transaction->metode_pembayaran ?? 'Cash',
                    ];
                });
            
            return [
                'daily' => [
                    'pendapatan' => $todayPendapatan,
                    'pengeluaran' => $todayPengeluaran,
                    'net_income' => $todayNetIncome,
                ],
                'monthly' => [
                    'pendapatan' => $monthPendapatan,
                    'pengeluaran' => $monthPengeluaran,
                    'net_income' => $monthNetIncome,
                ],
                'yearly' => [
                    'pendapatan' => $yearPendapatan,
                    'pengeluaran' => $yearPengeluaran,
                    'net_income' => $yearNetIncome,
                ],
                'revenue_categories' => $revenueByCategory->toArray(),
                'expense_breakdown' => $expenseBreakdown->toArray(),
                'payment_methods' => $paymentMethods->toArray(),
                'recent_transactions' => $recentTransactions->toArray(),
                'financial_health' => $this->calculateFinancialHealth($monthNetIncome, $monthPendapatan),
            ];
            
        } catch (\Exception $e) {
            return [
                'daily' => ['pendapatan' => 0, 'pengeluaran' => 0, 'net_income' => 0],
                'monthly' => ['pendapatan' => 0, 'pengeluaran' => 0, 'net_income' => 0],
                'yearly' => ['pendapatan' => 0, 'pengeluaran' => 0, 'net_income' => 0],
                'revenue_categories' => [],
                'expense_breakdown' => [],
                'payment_methods' => [],
                'recent_transactions' => [],
                'financial_health' => ['score' => 0, 'status' => 'unknown', 'recommendation' => ''],
            ];
        }
    }
    
    public function getWeeklyTrend(): array
    {
        try {
            $days = [];
            $pendapatan = [];
            $pengeluaran = [];
            $netIncome = [];
            
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $days[] = $date->format('D, M j');
                
                $dailyPendapatan = Pendapatan::whereDate('tanggal', $date)
                    ->where('status_validasi', 'disetujui')->sum('nominal');
                $dailyPengeluaran = Pengeluaran::whereDate('tanggal', $date)->sum('nominal');
                
                $pendapatan[] = $dailyPendapatan;
                $pengeluaran[] = $dailyPengeluaran;
                $netIncome[] = $dailyPendapatan - $dailyPengeluaran;
            }
            
            return [
                'days' => $days,
                'pendapatan' => $pendapatan,
                'pengeluaran' => $pengeluaran,
                'net_income' => $netIncome,
            ];
            
        } catch (\Exception $e) {
            return [
                'days' => [],
                'pendapatan' => [],
                'pengeluaran' => [],
                'net_income' => [],
            ];
        }
    }
    
    private function calculateFinancialHealth(float $netIncome, float $totalRevenue): array
    {
        if ($totalRevenue == 0) {
            return [
                'score' => 0,
                'status' => 'No Data',
                'recommendation' => 'Mulai catat pendapatan untuk analisis keuangan'
            ];
        }
        
        $profitMargin = ($netIncome / $totalRevenue) * 100;
        
        if ($profitMargin >= 20) {
            return [
                'score' => 95,
                'status' => 'Excellent',
                'recommendation' => 'Keuangan sangat sehat, pertahankan kinerja'
            ];
        } elseif ($profitMargin >= 10) {
            return [
                'score' => 80,
                'status' => 'Good',
                'recommendation' => 'Keuangan baik, cari peluang untuk optimisasi'
            ];
        } elseif ($profitMargin >= 5) {
            return [
                'score' => 65,
                'status' => 'Fair',
                'recommendation' => 'Perlu perhatian, review pengeluaran'
            ];
        } elseif ($profitMargin >= 0) {
            return [
                'score' => 40,
                'status' => 'Poor',
                'recommendation' => 'Margin tipis, kurangi pengeluaran tidak perlu'
            ];
        } else {
            return [
                'score' => 20,
                'status' => 'Critical',
                'recommendation' => 'Kerugian! Segera review strategi keuangan'
            ];
        }
    }
}