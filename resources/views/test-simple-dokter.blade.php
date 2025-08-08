<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Simple Dokter Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .result { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        pre { background-color: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Simple Dokter API Test</h1>
    
    <div id="status" class="info">
        Testing API connection...
    </div>
    
    <div id="result"></div>
    
    <button onclick="testAPI()">Test API Again</button>
    
    <script>
        async function testAPI() {
            const statusDiv = document.getElementById('status');
            const resultDiv = document.getElementById('result');
            
            statusDiv.className = 'info';
            statusDiv.textContent = 'Testing API connection...';
            resultDiv.innerHTML = '';
            
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                console.log('CSRF Token:', csrfToken);
                console.log('Making API request...');
                
                const response = await fetch('/api/v2/dashboards/dokter/jadwal-jaga', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                
                const data = await response.json();
                console.log('Response data:', data);
                
                if (response.ok && data.success) {
                    statusDiv.className = 'success';
                    statusDiv.textContent = '✅ API call successful!';
                    
                    const calendarEvents = data.data?.calendar_events || [];
                    const weeklySchedule = data.data?.weekly_schedule || [];
                    const today = data.data?.today || [];
                    
                    resultDiv.innerHTML = `
                        <div class="success">
                            <h3>API Response Summary:</h3>
                            <p><strong>Status:</strong> ${response.status}</p>
                            <p><strong>Success:</strong> ${data.success}</p>
                            <p><strong>Message:</strong> ${data.message}</p>
                            <p><strong>Calendar Events:</strong> ${calendarEvents.length}</p>
                            <p><strong>Weekly Schedule:</strong> ${weeklySchedule.length}</p>
                            <p><strong>Today:</strong> ${today.length}</p>
                        </div>
                        
                        <h3>Sample Data:</h3>
                        <pre>${JSON.stringify(data.data, null, 2)}</pre>
                    `;
                } else {
                    statusDiv.className = 'error';
                    statusDiv.textContent = '❌ API call failed';
                    
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h3>Error Details:</h3>
                            <p><strong>Status:</strong> ${response.status}</p>
                            <p><strong>Success:</strong> ${data.success}</p>
                            <p><strong>Message:</strong> ${data.message || 'No message'}</p>
                        </div>
                        
                        <h3>Full Response:</h3>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                
                statusDiv.className = 'error';
                statusDiv.textContent = '❌ Network error occurred';
                
                resultDiv.innerHTML = `
                    <div class="error">
                        <h3>Error Details:</h3>
                        <p><strong>Error:</strong> ${error.message}</p>
                    </div>
                `;
            }
        }
        
        // Test on page load
        testAPI();
    </script>
</body>
</html>
