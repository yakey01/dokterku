import React from 'react';
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip, Legend, BarChart, Bar, XAxis, YAxis, CartesianGrid } from 'recharts';

interface ChartData {
    name: string;
    value: number;
    color: string;
}

interface PetugasDashboardChartsProps {
    patientData?: ChartData[];
    procedureData?: ChartData[];
}

const PetugasDashboardCharts: React.FC<PetugasDashboardChartsProps> = ({
    patientData = [
        { name: 'Umum', value: 45, color: '#3B82F6' },
        { name: 'BPJS', value: 35, color: '#10B981' },
        { name: 'Asuransi', value: 20, color: '#F59E0B' }
    ],
    procedureData = [
        { name: 'Pemeriksaan', value: 35, color: '#8B5CF6' },
        { name: 'Konsultasi', value: 25, color: '#EC4899' },
        { name: 'Tindakan', value: 20, color: '#06B6D4' },
        { name: 'Laboratorium', value: 15, color: '#14B8A6' },
        { name: 'Radiologi', value: 5, color: '#F97316' }
    ]
}) => {
    // Custom label renderer for pie chart
    const renderCustomLabel = (entry: any) => {
        const total = entry.payload.reduce((sum: number, item: any) => sum + item.value, 0);
        const percent = ((entry.value / total) * 100).toFixed(0);
        return `${percent}%`;
    };

    // Custom tooltip for better UX
    const CustomTooltip = ({ active, payload }: any) => {
        if (active && payload && payload.length) {
            const data = payload[0];
            const total = data.payload.value;
            
            return (
                <div className="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
                    <p className="font-semibold text-gray-900 dark:text-white">
                        {data.name}
                    </p>
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                        Jumlah: {data.value}
                    </p>
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                        Persentase: {((data.value / total) * 100).toFixed(1)}%
                    </p>
                </div>
            );
        }
        return null;
    };

    // Custom legend component
    const CustomLegend = ({ data }: { data: ChartData[] }) => {
        const total = data.reduce((sum, item) => sum + item.value, 0);
        
        return (
            <div className="mt-6 grid grid-cols-1 gap-3">
                {data.map((entry, index) => {
                    const percentage = ((entry.value / total) * 100).toFixed(0);
                    return (
                        <div 
                            key={`legend-${index}`}
                            className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors cursor-pointer"
                        >
                            <div className="flex items-center space-x-3">
                                <div 
                                    className="w-3 h-3 rounded-full" 
                                    style={{ backgroundColor: entry.color }}
                                />
                                <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {entry.name}
                                </span>
                            </div>
                            <div className="flex items-center space-x-2">
                                <span className="text-sm text-gray-500 dark:text-gray-400">
                                    {entry.value}
                                </span>
                                <span className="text-sm font-bold text-gray-900 dark:text-white">
                                    {percentage}%
                                </span>
                            </div>
                        </div>
                    );
                })}
            </div>
        );
    };

    return (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Kategori Pasien Chart */}
            <div className="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <h4 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <div className="w-3 h-3 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full mr-3" />
                    Kategori Pasien
                </h4>
                
                <div className="relative">
                    <ResponsiveContainer width="100%" height={280}>
                        <PieChart>
                            <Pie
                                data={patientData}
                                cx="50%"
                                cy="50%"
                                labelLine={false}
                                label={renderCustomLabel}
                                outerRadius={100}
                                innerRadius={60}
                                fill="#8884d8"
                                dataKey="value"
                                animationBegin={0}
                                animationDuration={800}
                                animationEasing="ease-out"
                            >
                                {patientData.map((entry, index) => (
                                    <Cell key={`cell-${index}`} fill={entry.color} />
                                ))}
                            </Pie>
                            <Tooltip content={<CustomTooltip />} />
                        </PieChart>
                    </ResponsiveContainer>
                </div>
                
                <CustomLegend data={patientData} />
            </div>

            {/* Jenis Tindakan Chart */}
            <div className="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <h4 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <div className="w-3 h-3 bg-gradient-to-r from-green-500 to-teal-600 rounded-full mr-3" />
                    Jenis Tindakan
                </h4>
                
                <div className="relative">
                    <ResponsiveContainer width="100%" height={280}>
                        <BarChart 
                            data={procedureData}
                            margin={{ top: 20, right: 30, left: 20, bottom: 5 }}
                        >
                            <CartesianGrid strokeDasharray="3 3" stroke="#E5E7EB" />
                            <XAxis 
                                dataKey="name" 
                                tick={{ fontSize: 12, fill: '#6B7280' }}
                                angle={-45}
                                textAnchor="end"
                                height={80}
                            />
                            <YAxis 
                                tick={{ fontSize: 12, fill: '#6B7280' }}
                            />
                            <Tooltip content={<CustomTooltip />} />
                            <Bar 
                                dataKey="value" 
                                fill="#8884d8"
                                animationBegin={0}
                                animationDuration={800}
                                animationEasing="ease-out"
                                radius={[8, 8, 0, 0]}
                            >
                                {procedureData.map((entry, index) => (
                                    <Cell key={`cell-${index}`} fill={entry.color} />
                                ))}
                            </Bar>
                        </BarChart>
                    </ResponsiveContainer>
                </div>
                
                <CustomLegend data={procedureData} />
            </div>
        </div>
    );
};

export default PetugasDashboardCharts;