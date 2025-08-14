/**
 * Test script to debug attendance calculation
 * Run in browser console to simulate the issue
 */

// Simulate the filtered data that's currently showing 9 records
const testData = [
  {
    date: '2025-01-13',
    status: 'Hadir',
    time_in: '08:00',
    time_out: '17:00',
    actual_hours: 8,
    worked_hours: 8,
    scheduled_hours: 8
  },
  {
    date: '2025-01-14',
    status: 'Tepat Waktu',
    time_in: '08:15',
    time_out: '17:30',
    actual_hours: 9,
    worked_hours: 9,
    scheduled_hours: 8
  },
  {
    date: '2025-01-15',
    status: 'Terlambat',
    time_in: '08:45',
    time_out: '17:00',
    actual_hours: 8.25,
    worked_hours: 8.25,
    scheduled_hours: 8
  }
];

// Test the AttendanceCalculator logic
console.log('🧪 Testing AttendanceCalculator with sample data...');

// Simulate the status checking logic
testData.forEach((record, i) => {
  const isPresent = record.status === 'Hadir' || record.status === 'Tepat Waktu' || record.status === 'Terlambat';
  console.log(`Record ${i + 1} status check:`, {
    status: `"${record.status}"`,
    isHadir: record.status === 'Hadir',
    isTepatWaktu: record.status === 'Tepat Waktu',
    isTerlambat: record.status === 'Terlambat',
    isPresent,
    attendedHours: record.actual_hours,
    shouldCount: isPresent && record.actual_hours > 0
  });
});

// Calculate totals like AttendanceCalculator does
let totalScheduled = 0;
let totalAttended = 0;
let presentDays = 0;

testData.forEach(record => {
  const isPresent = record.status === 'Hadir' || record.status === 'Tepat Waktu' || record.status === 'Terlambat';
  
  if (isPresent) {
    presentDays++;
  }
  
  if (record.scheduled_hours) {
    totalScheduled += record.scheduled_hours;
  }
  
  if (isPresent && record.actual_hours > 0) {
    totalAttended += record.actual_hours;
  }
});

const percentage = totalScheduled > 0 ? Math.round((totalAttended / totalScheduled) * 100) : 0;

console.log('🧪 Test calculation results:', {
  presentDays,
  totalScheduled,
  totalAttended,
  percentage: percentage + '%'
});

// This should show 3 present days, 24 scheduled hours, 25.25 attended hours, 105%