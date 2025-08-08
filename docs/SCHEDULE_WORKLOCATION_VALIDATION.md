# Jadwal Jaga dan Work Location Validation - Presensi Dokter

## 🎯 Overview

Fitur baru telah ditambahkan ke sistem presensi dokter untuk memvalidasi jadwal jaga dan work location sebelum melakukan check-in/out.

## ✨ Fitur Baru

### 1. **Validasi Jadwal Jaga**
- ✅ Mengecek apakah dokter memiliki jadwal jaga hari ini
- ✅ Memvalidasi apakah waktu saat ini berada dalam jam jaga
- ✅ Menampilkan informasi jadwal jaga (waktu mulai, selesai, lokasi)

### 2. **Validasi Work Location**
- ✅ Mengecek apakah work location sudah ditugaskan
- ✅ Memvalidasi jarak dari lokasi kerja (radius maksimal)
- ✅ Menampilkan informasi work location (nama, alamat)

### 3. **UI Status Dashboard**
- 🟢 **Siap Jaga**: Dokter memiliki jadwal dan dapat melakukan presensi
- 🔴 **Tidak Jaga**: Dokter tidak memiliki jadwal atau di luar jam jaga
- ⚠️ **Peringatan**: Menampilkan pesan validasi jika ada masalah

## 🔧 Implementasi

### State Management
```typescript
const [scheduleData, setScheduleData] = useState({
  todaySchedule: null,
  currentShift: null,
  workLocation: null,
  isOnDuty: false,
  canCheckIn: false,
  canCheckOut: false,
  validationMessage: ''
});
```

### API Endpoints
- `GET /api/v2/dashboards/dokter/jadwal-jaga` - Mengambil jadwal jaga hari ini
- `GET /api/v2/dashboards/dokter/work-location/status` - Mengambil status work location
- `POST /api/v2/dashboards/dokter/checkin` - Check-in dengan validasi
- `POST /api/v2/dashboards/dokter/checkout` - Check-out dengan validasi

### Validasi Logic
```typescript
// Check if doctor is on duty today
const isOnDutyToday = scheduleData.todaySchedule && scheduleData.todaySchedule.length > 0;

// Check if current time is within shift hours
const isWithinShiftHours = scheduleData.currentShift && 
  currentHour >= scheduleData.currentShift.start_hour && 
  currentHour < scheduleData.currentShift.end_hour;

// Check if work location is assigned
const hasWorkLocation = scheduleData.workLocation && scheduleData.workLocation.is_assigned;

// Determine if can check in/out
const canCheckIn = isOnDutyToday && isWithinShiftHours && hasWorkLocation && !isCheckedIn;
const canCheckOut = isCheckedIn;
```

## 🎨 UI Components

### Status Dashboard
- **Jadwal Jaga Card**: Menampilkan waktu jaga dan lokasi
- **Work Location Card**: Menampilkan nama dan alamat work location
- **Validation Message**: Pesan error jika ada masalah validasi

### Button States
- **Check-in Button**: Disabled jika tidak memenuhi syarat validasi
- **Check-out Button**: Disabled jika belum check-in atau tidak dapat check-out

## 📱 User Experience

### Flow Presensi
1. **Load Data**: Sistem memuat jadwal jaga dan work location
2. **Validate Status**: Mengecek apakah dokter dapat melakukan presensi
3. **Display Status**: Menampilkan status di dashboard
4. **Enable/Disable Buttons**: Tombol check-in/out sesuai validasi
5. **GPS Validation**: Validasi lokasi GPS saat check-in/out
6. **Success/Error**: Menampilkan pesan sukses atau error

### Pesan Validasi
- ❌ "Anda tidak memiliki jadwal jaga hari ini"
- ❌ "Saat ini bukan jam jaga Anda"
- ❌ "Work location belum ditugaskan"
- ❌ "Anda terlalu jauh dari lokasi kerja"

## 🔒 Security & Validation

### GPS Distance Validation
```typescript
const distance = calculateDistance(latitude, longitude, hospitalLocation.lat, hospitalLocation.lng);
if (distance > hospitalLocation.radius) {
  alert(`❌ Anda terlalu jauh dari lokasi kerja. Jarak: ${Math.round(distance)}m (maksimal ${hospitalLocation.radius}m)`);
  return;
}
```

### Schedule Validation
- Memastikan dokter memiliki jadwal jaga hari ini
- Memvalidasi waktu check-in sesuai jam jaga
- Mencegah check-in di luar jam kerja

### Work Location Validation
- Memastikan work location sudah ditugaskan
- Validasi jarak dari lokasi kerja
- Mencegah presensi dari lokasi yang tidak sah

## 🚀 Deployment

### Backend Requirements
- API endpoint untuk jadwal jaga
- API endpoint untuk work location status
- Validasi di sisi server untuk check-in/out

### Frontend Requirements
- React component dengan TypeScript
- State management untuk schedule data
- UI components untuk status display

## 📊 Monitoring

### Logs
- GPS detection logs
- Schedule validation logs
- Work location validation logs
- Check-in/out success/error logs

### Metrics
- Success rate presensi
- GPS accuracy metrics
- Schedule compliance rate
- Work location validation rate

## 🔄 Future Enhancements

### Planned Features
- [ ] Real-time schedule updates
- [ ] Multiple work location support
- [ ] Schedule conflict detection
- [ ] Automatic work location assignment
- [ ] Offline schedule caching
- [ ] Schedule notification system

### Technical Improvements
- [ ] Optimize API calls
- [ ] Add retry mechanism
- [ ] Improve error handling
- [ ] Add unit tests
- [ ] Performance optimization

## 📝 Notes

- Sistem ini memastikan dokter hanya dapat melakukan presensi sesuai jadwal dan lokasi yang ditugaskan
- Validasi GPS memastikan dokter berada di lokasi kerja yang benar
- UI memberikan feedback yang jelas tentang status validasi
- Sistem dapat menangani berbagai skenario error dengan graceful degradation
