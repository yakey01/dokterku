<?php
/**
 * Generate test page to check frontend state for dr. Rindang
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use App\Models\User;
use App\Models\Attendance;
use App\Models\JadwalJaga;
use Carbon\Carbon;

// Find dr. Rindang
$rindang = User::where('email', 'dd@rrr.com')->first();
if (!$rindang) {
    die("User not found\n");
}

$now = Carbon::now('Asia/Jakarta');
$today = Carbon::today('Asia/Jakarta');

// Get schedule
$schedules = JadwalJaga::where('pegawai_id', $rindang->id)
    ->whereDate('tanggal_jaga', $today)
    ->with('shiftTemplate')
    ->get();

// Get attendance
$attendance = Attendance::where('user_id', $rindang->id)
    ->whereDate('date', $today)
    ->orderByDesc('time_in')
    ->get();

// Find open attendance
$openAttendance = $attendance->filter(function($a) {
    return $a->time_in && !$a->time_out;
})->first();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Frontend State Test - Dr. Rindang</title>
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <style>
        body { 
            font-family: system-ui; 
            padding: 20px; 
            background: #f5f5f5;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white;
            padding: 20px;
            border-radius: 8px;
        }
        .section { 
            margin: 20px 0; 
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
        }
        .debug { 
            background: #f0f0f0; 
            padding: 10px; 
            font-family: monospace;
            white-space: pre-wrap;
            border-radius: 4px;
        }
        .status { 
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status.ok { background: #4caf50; color: white; }
        .status.error { background: #f44336; color: white; }
        .status.warning { background: #ff9800; color: white; }
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .btn-checkin { background: #4caf50; color: white; }
        .btn-checkout { background: #2196f3; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Frontend State Test - Dr. Rindang</h1>
        
        <div class="section">
            <h2>📊 Backend Data</h2>
            <div class="debug">
User: <?php echo $rindang->name; ?> (ID: <?php echo $rindang->id; ?>)
Email: <?php echo $rindang->email; ?>
Current Time: <?php echo $now->format('Y-m-d H:i:s'); ?>

Schedule Count: <?php echo $schedules->count(); ?>
<?php foreach ($schedules as $s): ?>
  - ID: <?php echo $s->id; ?>, Shift: <?php echo $s->shiftTemplate->nama_shift ?? 'N/A'; ?>
    Time: <?php echo $s->shiftTemplate->jam_masuk ?? ''; ?> - <?php echo $s->shiftTemplate->jam_pulang ?? ''; ?>
<?php endforeach; ?>

Attendance Count: <?php echo $attendance->count(); ?>
<?php foreach ($attendance as $a): ?>
  - In: <?php echo $a->time_in; ?>, Out: <?php echo $a->time_out ?: 'NOT YET'; ?>
<?php endforeach; ?>

Open Attendance: <?php echo $openAttendance ? 'YES (checked in at ' . $openAttendance->time_in . ')' : 'NO'; ?>
            </div>
        </div>

        <div class="section">
            <h2>🔍 Expected Frontend Behavior</h2>
            <?php if ($openAttendance): ?>
                <p><span class="status ok">CHECKED IN</span></p>
                <ul>
                    <li>✅ isCheckedIn should be <strong>TRUE</strong></li>
                    <li>✅ canCheckOut should be <strong>TRUE</strong></li>
                    <li>✅ isOnDuty should be <strong>TRUE</strong></li>
                    <li>✅ Should NOT show "Anda tidak memiliki jadwal jaga hari ini"</li>
                    <li>✅ Check-out button should be <strong>ENABLED</strong></li>
                </ul>
            <?php elseif ($schedules->count() > 0): ?>
                <p><span class="status warning">HAS SCHEDULE</span></p>
                <ul>
                    <li>✅ Should show schedule information</li>
                    <li>✅ Should NOT show "Anda tidak memiliki jadwal jaga hari ini"</li>
                </ul>
            <?php else: ?>
                <p><span class="status error">NO SCHEDULE</span></p>
                <ul>
                    <li>❌ Should show "Anda tidak memiliki jadwal jaga hari ini"</li>
                </ul>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>🎯 Test Component State</h2>
            <div id="test-root"></div>
        </div>

        <div class="section">
            <h2>📝 Console Output</h2>
            <div id="console-output" class="debug"></div>
        </div>
    </div>

    <script>
        // Store console logs
        const logs = [];
        const originalLog = console.log;
        console.log = function() {
            logs.push(Array.from(arguments).map(a => 
                typeof a === 'object' ? JSON.stringify(a, null, 2) : a
            ).join(' '));
            originalLog.apply(console, arguments);
            updateConsoleOutput();
        };

        function updateConsoleOutput() {
            const output = document.getElementById('console-output');
            output.textContent = logs.join('\n');
        }

        // Test state management
        const testState = {
            scheduleData: {
                todaySchedule: <?php echo json_encode($schedules->map(function($s) {
                    return [
                        'id' => $s->id,
                        'shift_template' => $s->shiftTemplate ? [
                            'nama_shift' => $s->shiftTemplate->nama_shift,
                            'jam_masuk' => $s->shiftTemplate->jam_masuk,
                            'jam_pulang' => $s->shiftTemplate->jam_pulang,
                        ] : null
                    ];
                })); ?>,
                currentShift: null,
                workLocation: { id: 1, name: 'Klinik Dokterku' },
                isOnDuty: false,
                canCheckIn: false,
                canCheckOut: false,
                validationMessage: ''
            },
            isCheckedIn: <?php echo $openAttendance ? 'true' : 'false'; ?>,
            attendanceData: {
                checkInTime: <?php echo $openAttendance ? '"' . $openAttendance->time_in . '"' : 'null'; ?>,
                checkOutTime: null
            }
        };

        // Simulate validation logic
        function validateCurrentStatus() {
            const now = new Date();
            const isOnDutyToday = testState.scheduleData.todaySchedule.length > 0;
            const isWithinCheckinWindow = false; // Simplified - would need actual calculation
            const hasWorkLocation = testState.scheduleData.workLocation && testState.scheduleData.workLocation.id;
            
            // THE KEY FIX: isOnDuty should be true if checked in
            const isOnDuty = isOnDutyToday && (isWithinCheckinWindow || testState.isCheckedIn);
            const canCheckIn = isOnDutyToday && isWithinCheckinWindow && !testState.isCheckedIn;
            const canCheckOut = testState.isCheckedIn;
            
            // Validation message logic
            let validationMessage = '';
            if (testState.isCheckedIn) {
                // If checked in, don't show "no schedule" message
                validationMessage = '';
            } else if (!isOnDutyToday) {
                validationMessage = 'Anda tidak memiliki jadwal jaga hari ini';
            } else if (!isWithinCheckinWindow) {
                validationMessage = 'Saat ini bukan jam jaga Anda';
            } else if (!hasWorkLocation) {
                validationMessage = 'Work location belum ditugaskan';
            }
            
            // Update state
            testState.scheduleData.isOnDuty = isOnDuty;
            testState.scheduleData.canCheckIn = canCheckIn;
            testState.scheduleData.canCheckOut = canCheckOut;
            testState.scheduleData.validationMessage = validationMessage;
            
            console.log('=== VALIDATION RESULTS ===');
            console.log('isOnDutyToday:', isOnDutyToday);
            console.log('isWithinCheckinWindow:', isWithinCheckinWindow);
            console.log('isCheckedIn:', testState.isCheckedIn);
            console.log('isOnDuty:', isOnDuty);
            console.log('canCheckIn:', canCheckIn);
            console.log('canCheckOut:', canCheckOut);
            console.log('validationMessage:', validationMessage);
            
            renderState();
        }

        function renderState() {
            const root = document.getElementById('test-root');
            const { scheduleData, isCheckedIn } = testState;
            
            root.innerHTML = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <h3>State Values</h3>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr><td style="padding: 5px; border: 1px solid #ddd;">isCheckedIn</td><td style="padding: 5px; border: 1px solid #ddd;"><strong>${isCheckedIn}</strong></td></tr>
                            <tr><td style="padding: 5px; border: 1px solid #ddd;">isOnDuty</td><td style="padding: 5px; border: 1px solid #ddd;"><strong>${scheduleData.isOnDuty}</strong></td></tr>
                            <tr><td style="padding: 5px; border: 1px solid #ddd;">canCheckIn</td><td style="padding: 5px; border: 1px solid #ddd;"><strong>${scheduleData.canCheckIn}</strong></td></tr>
                            <tr><td style="padding: 5px; border: 1px solid #ddd;">canCheckOut</td><td style="padding: 5px; border: 1px solid #ddd;"><strong>${scheduleData.canCheckOut}</strong></td></tr>
                        </table>
                    </div>
                    <div>
                        <h3>UI Elements</h3>
                        ${scheduleData.validationMessage ? 
                            `<div style="padding: 10px; background: #ffebee; color: #c62828; border-radius: 4px; margin-bottom: 10px;">
                                ⚠️ ${scheduleData.validationMessage}
                            </div>` : ''}
                        <button class="btn-checkin" ${!scheduleData.canCheckIn ? 'disabled' : ''}>
                            Check In
                        </button>
                        <button class="btn-checkout" ${!isCheckedIn || !scheduleData.canCheckOut ? 'disabled' : ''}>
                            Check Out
                        </button>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <h3>Issues Detected</h3>
                    ${testState.isCheckedIn && !scheduleData.isOnDuty ? 
                        '<p style="color: red;">❌ ISSUE: isCheckedIn is TRUE but isOnDuty is FALSE</p>' : ''}
                    ${testState.isCheckedIn && !scheduleData.canCheckOut ? 
                        '<p style="color: red;">❌ ISSUE: isCheckedIn is TRUE but canCheckOut is FALSE</p>' : ''}
                    ${testState.isCheckedIn && scheduleData.validationMessage === 'Anda tidak memiliki jadwal jaga hari ini' ? 
                        '<p style="color: red;">❌ ISSUE: Showing "no schedule" message when checked in</p>' : ''}
                    ${!testState.isCheckedIn && scheduleData.todaySchedule.length > 0 && scheduleData.validationMessage === 'Anda tidak memiliki jadwal jaga hari ini' ? 
                        '<p style="color: red;">❌ ISSUE: Showing "no schedule" message when schedule exists</p>' : ''}
                </div>
            `;
        }

        // Run validation
        validateCurrentStatus();
    </script>
</body>
</html>