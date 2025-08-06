<!DOCTYPE html>
<html>
<head>
    <title>Debug Work Location Issue</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h1>Debug Work Location Issue</h1>
    
    <div id="results"></div>
    
    <script>
        const results = document.getElementById('results');
        
        // Function to add result
        function addResult(title, data) {
            const div = document.createElement('div');
            div.innerHTML = `
                <h3>${title}</h3>
                <pre>${JSON.stringify(data, null, 2)}</pre>
                <hr>
            `;
            results.appendChild(div);
        }
        
        // Function to test API
        async function testAPI() {
            try {
                // Test 1: Dashboard API
                const dashboardResponse = await fetch('/api/v2/dashboards/paramedis/', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                
                if (dashboardResponse.ok) {
                    const dashboardData = await dashboardResponse.json();
                    addResult('Dashboard API Response', dashboardData);
                } else {
                    addResult('Dashboard API Error', {
                        status: dashboardResponse.status,
                        statusText: dashboardResponse.statusText
                    });
                }
                
                // Test 2: Attendance Status API
                const attendanceResponse = await fetch('/api/v2/dashboards/paramedis/attendance/status', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                
                if (attendanceResponse.ok) {
                    const attendanceData = await attendanceResponse.json();
                    addResult('Attendance Status API Response', attendanceData);
                } else {
                    addResult('Attendance Status API Error', {
                        status: attendanceResponse.status,
                        statusText: attendanceResponse.statusText
                    });
                }
                
                // Test 3: Refresh Work Location API
                const refreshResponse = await fetch('/api/v2/dashboards/paramedis/refresh-work-location', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (refreshResponse.ok) {
                    const refreshData = await refreshResponse.json();
                    addResult('Refresh Work Location API Response', refreshData);
                } else {
                    addResult('Refresh Work Location API Error', {
                        status: refreshResponse.status,
                        statusText: refreshResponse.statusText
                    });
                }
                
            } catch (error) {
                addResult('JavaScript Error', {
                    message: error.message,
                    stack: error.stack
                });
            }
        }
        
        // Check localStorage and sessionStorage
        addResult('LocalStorage Data', {
            keys: Object.keys(localStorage),
            data: Object.fromEntries(Object.entries(localStorage))
        });
        
        addResult('SessionStorage Data', {
            keys: Object.keys(sessionStorage),
            data: Object.fromEntries(Object.entries(sessionStorage))
        });
        
        // Run API tests
        testAPI();
    </script>
</body>
</html>