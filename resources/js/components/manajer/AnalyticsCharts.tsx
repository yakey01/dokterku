import React, { useEffect, useRef } from 'react';
import { Chart, registerables } from 'chart.js';
import 'chartjs-adapter-date-fns';

// Register Chart.js components
Chart.register(...registerables);

// ============================================
// CHART COMPONENTS FOR MANAGER DASHBOARD
// ============================================

interface ChartProps {
  data: any;
  className?: string;
}

// Revenue vs Expenses Trend Chart
export const RevenueTrendChart: React.FC<ChartProps> = ({ data, className }) => {
  const chartRef = useRef<HTMLCanvasElement>(null);
  const chartInstance = useRef<Chart | null>(null);

  useEffect(() => {
    if (!chartRef.current || !data?.financial_trend) return;

    // Destroy existing chart
    if (chartInstance.current) {
      chartInstance.current.destroy();
    }

    const ctx = chartRef.current.getContext('2d');
    if (!ctx) return;

    // Prepare data
    const labels = data.financial_trend.map((item: any) => 
      new Date(item.date).toLocaleDateString('id-ID', { month: 'short', day: 'numeric' })
    );
    
    const revenues = data.financial_trend.map((item: any) => item.revenue);
    const expenses = data.financial_trend.map((item: any) => item.expenses);
    const profits = data.financial_trend.map((item: any) => item.profit);

    chartInstance.current = new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [
          {
            label: '💰 Pendapatan',
            data: revenues,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#10b981',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
          },
          {
            label: '💸 Pengeluaran',
            data: expenses,
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#ef4444',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
          },
          {
            label: '📈 Profit',
            data: profits,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 3,
            fill: false,
            tension: 0.4,
            pointBackgroundColor: '#3b82f6',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          intersect: false,
          mode: 'index',
        },
        plugins: {
          legend: {
            position: 'top',
            labels: {
              usePointStyle: true,
              padding: 20,
              font: {
                size: 12,
                family: 'Inter',
              },
            },
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            borderColor: '#3b82f6',
            borderWidth: 1,
            cornerRadius: 8,
            displayColors: true,
            callbacks: {
              label: function(context) {
                const value = new Intl.NumberFormat('id-ID', {
                  style: 'currency',
                  currency: 'IDR',
                  minimumFractionDigits: 0,
                }).format(context.parsed.y);
                return `${context.dataset.label}: ${value}`;
              },
            },
          },
        },
        scales: {
          x: {
            grid: {
              color: 'rgba(0, 0, 0, 0.05)',
              drawBorder: false,
            },
            ticks: {
              font: {
                size: 11,
                family: 'Inter',
              },
            },
          },
          y: {
            grid: {
              color: 'rgba(0, 0, 0, 0.05)',
              drawBorder: false,
            },
            ticks: {
              font: {
                size: 11,
                family: 'Inter',
              },
              callback: function(value) {
                return new Intl.NumberFormat('id-ID', {
                  style: 'currency',
                  currency: 'IDR',
                  minimumFractionDigits: 0,
                  notation: 'compact',
                }).format(value as number);
              },
            },
          },
        },
        elements: {
          point: {
            hoverRadius: 8,
          },
        },
      },
    });

    return () => {
      if (chartInstance.current) {
        chartInstance.current.destroy();
      }
    };
  }, [data]);

  return (
    <div className={`relative ${className}`}>
      <canvas ref={chartRef} className="w-full h-full" />
    </div>
  );
};

// Patient Types Bar Chart
export const PatientTypesChart: React.FC<ChartProps> = ({ data, className }) => {
  const chartRef = useRef<HTMLCanvasElement>(null);
  const chartInstance = useRef<Chart | null>(null);

  useEffect(() => {
    if (!chartRef.current || !data?.patient_trend) return;

    if (chartInstance.current) {
      chartInstance.current.destroy();
    }

    const ctx = chartRef.current.getContext('2d');
    if (!ctx) return;

    const labels = data.patient_trend.map((item: any) => 
      new Date(item.date).toLocaleDateString('id-ID', { month: 'short', day: 'numeric' })
    );
    
    const umumData = data.patient_trend.map((item: any) => item.umum);
    const bpjsData = data.patient_trend.map((item: any) => item.bpjs);

    chartInstance.current = new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          {
            label: '👥 Pasien Umum',
            data: umumData,
            backgroundColor: 'rgba(59, 130, 246, 0.8)',
            borderColor: '#3b82f6',
            borderWidth: 1,
            borderRadius: 4,
          },
          {
            label: '🏥 Pasien BPJS',
            data: bpjsData,
            backgroundColor: 'rgba(16, 185, 129, 0.8)',
            borderColor: '#10b981',
            borderWidth: 1,
            borderRadius: 4,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            labels: {
              usePointStyle: true,
              padding: 20,
              font: {
                size: 12,
                family: 'Inter',
              },
            },
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            cornerRadius: 8,
            callbacks: {
              label: function(context) {
                return `${context.dataset.label}: ${context.parsed.y} pasien`;
              },
            },
          },
        },
        scales: {
          x: {
            grid: {
              display: false,
            },
            ticks: {
              font: {
                size: 11,
                family: 'Inter',
              },
            },
          },
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(0, 0, 0, 0.05)',
            },
            ticks: {
              font: {
                size: 11,
                family: 'Inter',
              },
              callback: function(value) {
                return `${value} pasien`;
              },
            },
          },
        },
      },
    });

    return () => {
      if (chartInstance.current) {
        chartInstance.current.destroy();
      }
    };
  }, [data]);

  return (
    <div className={`relative ${className}`}>
      <canvas ref={chartRef} className="w-full h-full" />
    </div>
  );
};

// Expense Breakdown Donut Chart
export const ExpenseBreakdownChart: React.FC<ChartProps> = ({ data, className }) => {
  const chartRef = useRef<HTMLCanvasElement>(null);
  const chartInstance = useRef<Chart | null>(null);

  useEffect(() => {
    if (!chartRef.current || !data?.expense_breakdown) return;

    if (chartInstance.current) {
      chartInstance.current.destroy();
    }

    const ctx = chartRef.current.getContext('2d');
    if (!ctx) return;

    const labels = data.expense_breakdown.map((item: any) => item.category);
    const amounts = data.expense_breakdown.map((item: any) => item.amount);
    
    // Generate colors for categories
    const colors = [
      '#3b82f6', '#10b981', '#f59e0b', '#ef4444', 
      '#8b5cf6', '#06b6d4', '#84cc16', '#f97316'
    ];

    chartInstance.current = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [
          {
            data: amounts,
            backgroundColor: colors.slice(0, labels.length).map(color => color + 'CC'),
            borderColor: colors.slice(0, labels.length),
            borderWidth: 2,
            hoverOffset: 10,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              usePointStyle: true,
              padding: 15,
              font: {
                size: 11,
                family: 'Inter',
              },
              generateLabels: function(chart) {
                const data = chart.data;
                if (data.labels && data.datasets.length) {
                  return data.labels.map((label, index) => {
                    const value = data.datasets[0].data[index] as number;
                    const total = (data.datasets[0].data as number[]).reduce((a, b) => a + b, 0);
                    const percentage = ((value / total) * 100).toFixed(1);
                    
                    return {
                      text: `${label} (${percentage}%)`,
                      fillStyle: data.datasets[0].backgroundColor[index],
                      strokeStyle: data.datasets[0].borderColor[index],
                      lineWidth: 2,
                      hidden: false,
                      index,
                    };
                  });
                }
                return [];
              },
            },
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            cornerRadius: 8,
            callbacks: {
              label: function(context) {
                const value = new Intl.NumberFormat('id-ID', {
                  style: 'currency',
                  currency: 'IDR',
                  minimumFractionDigits: 0,
                }).format(context.parsed);
                
                const total = (context.dataset.data as number[]).reduce((a, b) => a + b, 0);
                const percentage = ((context.parsed / total) * 100).toFixed(1);
                
                return `${context.label}: ${value} (${percentage}%)`;
              },
            },
          },
        },
        animation: {
          animateRotate: true,
          animateScale: true,
        },
      },
    });

    return () => {
      if (chartInstance.current) {
        chartInstance.current.destroy();
      }
    };
  }, [data]);

  return (
    <div className={`relative ${className}`}>
      <canvas ref={chartRef} className="w-full h-full" />
      
      {/* Center Label */}
      <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
        <div className="text-center">
          <p className="text-2xl font-bold text-neutral-900 dark:text-white">
            {data?.expense_breakdown?.length || 0}
          </p>
          <p className="text-sm text-neutral-500 dark:text-neutral-400">
            Kategori
          </p>
        </div>
      </div>
    </div>
  );
};

// Staff Performance Radar Chart
export const StaffPerformanceRadar: React.FC<ChartProps> = ({ data, className }) => {
  const chartRef = useRef<HTMLCanvasElement>(null);
  const chartInstance = useRef<Chart | null>(null);

  useEffect(() => {
    if (!chartRef.current || !data?.departments) return;

    if (chartInstance.current) {
      chartInstance.current.destroy();
    }

    const ctx = chartRef.current.getContext('2d');
    if (!ctx) return;

    const labels = ['Produktivitas', 'Kualitas', 'Efisiensi', 'Kepuasan', 'Inovasi'];
    const scores = [85, 78, 92, 88, 75]; // Mock scores - replace with real data

    chartInstance.current = new Chart(ctx, {
      type: 'radar',
      data: {
        labels,
        datasets: [
          {
            label: '📊 Performa Department',
            data: scores,
            backgroundColor: 'rgba(59, 130, 246, 0.2)',
            borderColor: '#3b82f6',
            borderWidth: 2,
            pointBackgroundColor: '#3b82f6',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          r: {
            beginAtZero: true,
            max: 100,
            grid: {
              color: 'rgba(0, 0, 0, 0.1)',
            },
            angleLines: {
              color: 'rgba(0, 0, 0, 0.1)',
            },
            pointLabels: {
              font: {
                size: 12,
                family: 'Inter',
              },
            },
            ticks: {
              display: false,
            },
          },
        },
        plugins: {
          legend: {
            display: false,
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            cornerRadius: 8,
            callbacks: {
              label: function(context) {
                return `${context.label}: ${context.parsed.r}%`;
              },
            },
          },
        },
      },
    });

    return () => {
      if (chartInstance.current) {
        chartInstance.current.destroy();
      }
    };
  }, [data]);

  return (
    <div className={`relative ${className}`}>
      <canvas ref={chartRef} className="w-full h-full" />
    </div>
  );
};

// Chart Container Wrapper with Loading State
export const ChartContainer: React.FC<{
  title: string;
  children: React.ReactNode;
  isLoading?: boolean;
  onRefresh?: () => void;
  timeRange?: string;
  onTimeRangeChange?: (range: string) => void;
}> = ({ 
  title, 
  children, 
  isLoading = false, 
  onRefresh,
  timeRange = '7',
  onTimeRangeChange 
}) => {
  return (
    <div className="bg-white dark:bg-neutral-800 rounded-xl p-6 border border-neutral-200 dark:border-neutral-700 shadow-sm hover:shadow-md transition-all duration-300">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-lg font-semibold text-neutral-900 dark:text-white">
          {title}
        </h2>
        
        <div className="flex items-center space-x-3">
          {onTimeRangeChange && (
            <select
              value={timeRange}
              onChange={(e) => onTimeRangeChange(e.target.value)}
              className="px-3 py-2 bg-neutral-100 dark:bg-neutral-700 border border-neutral-200 dark:border-neutral-600 rounded-lg text-sm text-neutral-700 dark:text-neutral-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="7">7 Hari</option>
              <option value="14">14 Hari</option>
              <option value="30">30 Hari</option>
              <option value="90">3 Bulan</option>
            </select>
          )}
          
          {onRefresh && (
            <button
              onClick={onRefresh}
              disabled={isLoading}
              className="flex items-center space-x-2 px-3 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-all duration-200 disabled:opacity-50"
            >
              <svg 
                className={`w-4 h-4 ${isLoading ? 'animate-spin' : ''}`} 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </button>
          )}
        </div>
      </div>
      
      <div className="relative">
        {isLoading && (
          <div className="absolute inset-0 bg-white/80 dark:bg-neutral-800/80 flex items-center justify-center z-10 rounded-lg">
            <div className="flex items-center space-x-2 text-neutral-500 dark:text-neutral-400">
              <svg className="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <span className="text-sm font-medium">Loading chart...</span>
            </div>
          </div>
        )}
        
        <div className="h-64">
          {children}
        </div>
      </div>
    </div>
  );
};

// Export the integrated analytics column
export const AnalyticsColumn: React.FC<{
  analyticsData: any;
  isLoading?: boolean;
  onRefresh?: () => void;
  onTimeRangeChange?: (range: string) => void;
}> = ({ analyticsData, isLoading = false, onRefresh, onTimeRangeChange }) => {
  return (
    <div className="space-y-6">
      {/* Revenue Trend Chart */}
      <ChartContainer
        title="📈 Tren Pendapatan vs Pengeluaran"
        isLoading={isLoading}
        onRefresh={onRefresh}
        onTimeRangeChange={onTimeRangeChange}
      >
        <RevenueTrendChart data={analyticsData} />
      </ChartContainer>

      {/* Patient Types Chart */}
      <ChartContainer
        title="📊 Grafik Batang Pasien Umum/BPJS"
        isLoading={isLoading}
      >
        <PatientTypesChart data={analyticsData} />
      </ChartContainer>

      {/* Expense Breakdown */}
      <ChartContainer
        title="🍩 Komposisi Pengeluaran"
        isLoading={isLoading}
      >
        <ExpenseBreakdownChart data={analyticsData} />
      </ChartContainer>

      {/* Staff Performance Radar (Optional) */}
      <ChartContainer
        title="🎯 Radar Kinerja Karyawan"
        isLoading={isLoading}
      >
        <StaffPerformanceRadar data={analyticsData} />
      </ChartContainer>
    </div>
  );
};