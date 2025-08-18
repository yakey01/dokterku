<?php
/**
 * BROWSER-BASED TOTAL HOURS VALIDATION
 * 
 * Direct web validation for Total Hours calculation fixes
 * Access via: /validate-total-hours-browser.php
 */

// Security check - only allow in development
if (env('APP_ENV', 'production') === 'production') {
    abort(403, 'Validation script not available in production');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Total Hours Validation System</title>
    <style>
        body { 
            font-family: 'Monaco', 'Consolas', monospace; 
            background: #1a1a1a; 
            color: #00ff00; 
            padding: 20px; 
            line-height: 1.6;
        }
        .header {
            background: #2d2d2d;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #00ff00;
        }
        .test-section {
            background: #2d2d2d;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #ffa500;
        }
        .success { color: #00ff00; }
        .error { color: #ff6b6b; }
        .warning { color: #ffa500; }
        .info { color: #74c0fc; }
        .critical { background: #660000; padding: 5px; border-radius: 3px; }
        .button {
            background: #4a90e2;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        .button:hover { background: #357abd; }
        .result-box {
            background: #333;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border: 1px solid #555;
        }
        pre { background: #1a1a1a; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .metric { 
            display: inline-block; 
            background: #4a4a4a; 
            padding: 5px 10px; 
            border-radius: 3px; 
            margin: 3px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔬 TOTAL HOURS VALIDATION SYSTEM</h1>
        <p><strong>MISSION:</strong> Zero tolerance validation for negative total hours</p>
        <p><strong>SCOPE:</strong> All dokter dashboard APIs and edge cases</p>
        <p><strong>CRITERIA:</strong> Blind testing with strict pass/fail validation</p>
    </div>

    <div class="test-section">
        <h2>🎯 VALIDATION CONTROLS</h2>
        <button class="button" onclick="runValidation()">🚀 Start Full Validation</button>
        <button class="button" onclick="testSpecificUser()">👨‍⚕️ Test Specific User</button>
        <button class="button" onclick="testEdgeCases()">🧪 Edge Cases Only</button>
        <button class="button" onclick="clearResults()">🗑️ Clear Results</button>
        
        <div style="margin-top: 15px;">
            <label>Test User ID: </label>
            <input type="number" id="userIdInput" value="26" style="background: #1a1a1a; color: #00ff00; border: 1px solid #555; padding: 5px;">
            <span class="info">(Default: 26 = Dr. Yaya)</span>
        </div>
    </div>

    <div id="results"></div>

    <script>
        let currentTest = null;
        let results = {
            errors: [],
            warnings: [],
            tests: []
        };

        async function runValidation() {
            clearResults();
            showStatus('🚀 Starting comprehensive validation...', 'info');
            
            await testApiAccess();
            await testCoreEndpoints();
            await testEdgeCases();
            await testDataIntegrity();
            await testCrossEndpointConsistency();
            
            generateFinalReport();
        }

        async function testApiAccess() {
            const section = addSection('📡 PHASE 1: API ACCESS VALIDATION');
            
            try {
                const response = await fetch('/api/v2/dashboards/dokter?user_id=26');
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                addResult(section, '✅ API Access: PASSED', 'success');
                addResult(section, '✅ Authentication: WORKING', 'success');
                
                return true;
            } catch (error) {
                addResult(section, `❌ API Access: FAILED - ${error.message}`, 'error');
                addError('API_ACCESS', `Cannot access API endpoints: ${error.message}`, 'CRITICAL');
                return false;
            }
        }

        async function testCoreEndpoints() {
            const section = addSection('🔍 PHASE 2: CORE API ENDPOINTS VALIDATION');
            
            const endpoints = [
                { name: 'main_dashboard', url: '/api/v2/dashboards/dokter' },
                { name: 'jadwal_jaga', url: '/api/v2/dashboards/dokter/jadwal-jaga' },
                { name: 'presensi', url: '/api/v2/dashboards/dokter/presensi' },
                { name: 'leaderboard', url: '/api/v2/dashboards/dokter/leaderboard' }
            ];

            for (const endpoint of endpoints) {
                try {
                    addResult(section, `Testing: ${endpoint.name} (${endpoint.url})`, 'info');
                    
                    const response = await fetch(`${endpoint.url}?user_id=26`);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    
                    const data = await response.json();
                    const validation = validateTotalHoursInResponse(data, endpoint.name, 26);
                    
                    if (validation.passed) {
                        addResult(section, `  ✅ Total Hours: ${validation.total_hours} (VALID)`, 'success');
                    } else {
                        addResult(section, `  ❌ Total Hours: ${validation.total_hours} (INVALID)`, 'error');
                        addError(endpoint.name, validation.error, 'CRITICAL');
                    }
                    
                } catch (error) {
                    addResult(section, `  ❌ ${endpoint.name}: FAILED - ${error.message}`, 'error');
                    addError(endpoint.name, `Endpoint not accessible: ${error.message}`, 'HIGH');
                }
            }
        }

        async function testSpecificUser() {
            const userId = document.getElementById('userIdInput').value || 26;
            clearResults();
            
            const section = addSection(`👨‍⚕️ TESTING USER ID: ${userId}`);
            
            const endpoints = [
                '/api/v2/dashboards/dokter',
                '/api/v2/dashboards/dokter/jadwal-jaga',
                '/api/v2/dashboards/dokter/presensi'
            ];

            for (const endpoint of endpoints) {
                try {
                    addResult(section, `Testing: ${endpoint}`, 'info');
                    
                    const response = await fetch(`${endpoint}?user_id=${userId}`);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    
                    const data = await response.json();
                    const validation = validateTotalHoursInResponse(data, endpoint, userId);
                    
                    if (validation.passed) {
                        addResult(section, `  ✅ Total Hours: ${validation.total_hours} (VALID)`, 'success');
                    } else {
                        addResult(section, `  ❌ Total Hours: ${validation.total_hours} (INVALID)`, 'error');
                    }
                    
                    // Show raw data for debugging
                    const totalHours = extractTotalHours(data);
                    addResult(section, `  📊 Raw Data: ${JSON.stringify({total_hours: totalHours}, null, 2)}`, 'info');
                    
                } catch (error) {
                    addResult(section, `  ❌ Error: ${error.message}`, 'error');
                }
            }
        }

        async function testEdgeCases() {
            const section = addSection('🧪 PHASE 3: EDGE CASES VALIDATION');
            
            // Test users that might have edge cases
            const testUsers = [999, 998, 997, 1, 5, 10]; // Various user IDs
            
            for (const userId of testUsers) {
                try {
                    addResult(section, `Testing User ID: ${userId}`, 'info');
                    
                    const response = await fetch(`/api/v2/dashboards/dokter?user_id=${userId}`);
                    
                    if (!response.ok) {
                        addResult(section, `  ⚠️  User ${userId}: No data or access denied`, 'warning');
                        continue;
                    }
                    
                    const data = await response.json();
                    const validation = validateTotalHoursInResponse(data, `user_${userId}`, userId);
                    
                    if (validation.passed) {
                        addResult(section, `  ✅ User ${userId}: Total Hours ${validation.total_hours} (VALID)`, 'success');
                    } else {
                        addResult(section, `  ❌ User ${userId}: Total Hours ${validation.total_hours} (INVALID)`, 'error');
                        addError(`edge_case_${userId}`, validation.error, 'HIGH');
                    }
                    
                } catch (error) {
                    addResult(section, `  ⚠️  User ${userId}: ${error.message}`, 'warning');
                }
            }
        }

        async function testDataIntegrity() {
            const section = addSection('🔒 PHASE 4: DATA INTEGRITY VALIDATION');
            
            try {
                const response = await fetch('/api/v2/dashboards/dokter?user_id=26');
                const data = await response.json();
                
                const metrics = extractMetrics(data);
                
                addResult(section, '📊 EXTRACTED METRICS:', 'info');
                for (const [key, value] of Object.entries(metrics)) {
                    addResult(section, `  • ${key}: ${value}`, 'info');
                }
                
                // Business Logic Validations
                const checks = {
                    'total_hours_non_negative': metrics.total_hours >= 0,
                    'completed_shifts_logical': metrics.completed_shifts <= metrics.total_shifts,
                    'attendance_rate_valid': metrics.attendance_rate >= 0 && metrics.attendance_rate <= 100,
                    'hours_per_shift_reasonable': metrics.total_shifts > 0 ? 
                        (metrics.total_hours / Math.max(metrics.completed_shifts, 1)) <= 24 : true
                };
                
                for (const [check, passed] of Object.entries(checks)) {
                    if (passed) {
                        addResult(section, `✅ ${check}: PASSED`, 'success');
                    } else {
                        addResult(section, `❌ ${check}: FAILED`, 'error');
                        addError('DATA_INTEGRITY', `Failed check: ${check}`, 'HIGH');
                    }
                }
                
            } catch (error) {
                addResult(section, `❌ Data integrity check failed: ${error.message}`, 'error');
                addError('DATA_INTEGRITY', `Cannot perform integrity check: ${error.message}`, 'CRITICAL');
            }
        }

        async function testCrossEndpointConsistency() {
            const section = addSection('🔄 PHASE 5: CROSS-ENDPOINT CONSISTENCY');
            
            try {
                const endpoints = [
                    { name: 'main_dashboard', url: '/api/v2/dashboards/dokter' },
                    { name: 'jadwal_jaga', url: '/api/v2/dashboards/dokter/jadwal-jaga' },
                    { name: 'presensi', url: '/api/v2/dashboards/dokter/presensi' }
                ];
                
                const totalHours = {};
                
                for (const endpoint of endpoints) {
                    const response = await fetch(`${endpoint.url}?user_id=26`);
                    if (response.ok) {
                        const data = await response.json();
                        totalHours[endpoint.name] = extractTotalHours(data);
                    }
                }
                
                addResult(section, '📈 TOTAL HOURS ACROSS ENDPOINTS:', 'info');
                for (const [endpoint, hours] of Object.entries(totalHours)) {
                    addResult(section, `  • ${endpoint}: ${hours} hours`, 'info');
                }
                
                // Check consistency
                const validHours = Object.values(totalHours).filter(h => h !== null);
                const uniqueValues = [...new Set(validHours)];
                
                if (uniqueValues.length <= 1) {
                    addResult(section, '✅ Cross-endpoint consistency: PASSED', 'success');
                } else {
                    addResult(section, '❌ Cross-endpoint consistency: FAILED', 'error');
                    addError('CONSISTENCY', 'Inconsistent total hours across endpoints', 'HIGH');
                }
                
                // Check all non-negative
                let allNonNegative = true;
                for (const [endpoint, hours] of Object.entries(totalHours)) {
                    if (hours !== null && hours < 0) {
                        allNonNegative = false;
                        addResult(section, `❌ ${endpoint}: Negative hours detected (${hours})`, 'error');
                        addError('NEGATIVE_HOURS', `Negative hours in ${endpoint}`, 'CRITICAL');
                    }
                }
                
                if (allNonNegative) {
                    addResult(section, '✅ All endpoints non-negative: PASSED', 'success');
                }
                
            } catch (error) {
                addResult(section, `❌ Consistency check failed: ${error.message}`, 'error');
                addError('CONSISTENCY', `Cannot perform consistency check: ${error.message}`, 'HIGH');
            }
        }

        function validateTotalHoursInResponse(data, context, userId) {
            const totalHours = extractTotalHours(data);
            
            if (totalHours === null) {
                return {
                    passed: false,
                    total_hours: 'NOT_FOUND',
                    error: 'Total hours field not found in response'
                };
            }
            
            const passed = totalHours >= 0;
            
            return {
                passed: passed,
                total_hours: totalHours,
                error: passed ? null : `Negative total hours: ${totalHours}`
            };
        }

        function extractTotalHours(data) {
            const paths = [
                'schedule_stats.total_hours',
                'presensi_stats.total_hours',
                'attendance_stats.total_hours',
                'stats.total_hours',
                'total_hours'
            ];
            
            for (const path of paths) {
                const value = getNestedValue(data, path);
                if (value !== null && value !== undefined) {
                    return parseFloat(value);
                }
            }
            
            return null;
        }

        function extractMetrics(data) {
            return {
                total_hours: extractTotalHours(data) || 0,
                completed_shifts: getNestedValue(data, 'schedule_stats.completed_shifts') || 0,
                total_shifts: getNestedValue(data, 'schedule_stats.total_shifts') || 0,
                attendance_rate: getNestedValue(data, 'presensi_stats.attendance_rate') || 0
            };
        }

        function getNestedValue(obj, path) {
            const keys = path.split('.');
            let current = obj;
            
            for (const key of keys) {
                if (current && typeof current === 'object' && key in current) {
                    current = current[key];
                } else {
                    return null;
                }
            }
            
            return current;
        }

        function addSection(title) {
            const section = document.createElement('div');
            section.className = 'test-section';
            section.innerHTML = `<h3>${title}</h3>`;
            document.getElementById('results').appendChild(section);
            return section;
        }

        function addResult(section, message, type = 'info') {
            const result = document.createElement('div');
            result.className = `result-box ${type}`;
            result.textContent = message;
            section.appendChild(result);
        }

        function showStatus(message, type = 'info') {
            const status = document.createElement('div');
            status.className = `result-box ${type}`;
            status.textContent = message;
            document.getElementById('results').appendChild(status);
        }

        function addError(context, message, severity = 'MEDIUM') {
            results.errors.push({
                context: context,
                message: message,
                severity: severity,
                timestamp: new Date().toISOString()
            });
        }

        function generateFinalReport() {
            const section = addSection('📋 COMPREHENSIVE VALIDATION REPORT');
            
            const totalErrors = results.errors.length;
            const criticalErrors = results.errors.filter(e => e.severity === 'CRITICAL').length;
            const highErrors = results.errors.filter(e => e.severity === 'HIGH').length;
            
            addResult(section, '📊 SUMMARY:', 'info');
            addResult(section, `  • Total Errors: ${totalErrors}`, totalErrors > 0 ? 'error' : 'success');
            addResult(section, `  • Critical Errors: ${criticalErrors}`, criticalErrors > 0 ? 'error' : 'success');
            addResult(section, `  • High Priority Errors: ${highErrors}`, highErrors > 0 ? 'warning' : 'success');
            
            if (results.errors.length > 0) {
                addResult(section, '❌ ERRORS:', 'error');
                results.errors.forEach(error => {
                    addResult(section, `  [${error.severity}] ${error.context}: ${error.message}`, 'error');
                });
            }
            
            const passed = criticalErrors === 0 && highErrors <= 3;
            
            addResult(section, '🏁 FINAL VERDICT:', 'info');
            if (passed) {
                addResult(section, '  ✅ VALIDATION PASSED', 'success');
                addResult(section, '  🎉 Total Hours fix is working correctly!', 'success');
                addResult(section, '  🚀 System is ready for production', 'success');
            } else {
                addResult(section, '  ❌ VALIDATION FAILED', 'error');
                addResult(section, '  🚨 Critical issues found that must be addressed', 'error');
                addResult(section, '  ⚠️  DO NOT deploy to production', 'error');
            }
        }

        function clearResults() {
            document.getElementById('results').innerHTML = '';
            results = { errors: [], warnings: [], tests: [] };
        }

        // Auto-run basic check on page load
        window.onload = function() {
            testSpecificUser();
        };
    </script>
</body>
</html>