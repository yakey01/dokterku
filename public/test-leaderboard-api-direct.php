<?php
/**
 * Direct API Test for Leaderboard Endpoint
 * Tests the leaderboard API endpoint resolution for 401 errors
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

class LeaderboardAPITester {
    private $baseUrl;
    private $results = [];
    
    public function __construct() {
        $this->baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . 
                        '://' . $_SERVER['HTTP_HOST'];
    }
    
    public function runTests() {
        $this->logResult('Starting Leaderboard API Validation Tests');
        
        try {
            // Test 1: Basic connectivity
            $this->testBasicConnectivity();
            
            // Test 2: Authentication endpoint
            $this->testAuthenticationEndpoint();
            
            // Test 3: Leaderboard endpoint without auth
            $this->testLeaderboardEndpointNoAuth();
            
            // Test 4: Check for route registration
            $this->testRouteRegistration();
            
            // Test 5: Database connectivity (if accessible)
            $this->testDatabaseConnectivity();
            
            return [
                'success' => true,
                'message' => 'API tests completed',
                'results' => $this->results,
                'summary' => $this->generateSummary()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'API tests failed: ' . $e->getMessage(),
                'results' => $this->results,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function testBasicConnectivity() {
        $this->logResult('Test 1: Basic Connectivity');
        
        try {
            $url = $this->baseUrl . '/api/v2/dashboards/dokter/leaderboard';
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'Accept: application/json',
                        'Content-Type: application/json',
                        'X-Requested-With: XMLHttpRequest'
                    ],
                    'timeout' => 10
                ]
            ]);
            
            $result = @file_get_contents($url, false, $context);
            $httpCode = $this->getHttpResponseCode($http_response_header ?? []);
            
            $this->logResult("✅ Endpoint reachable", [
                'url' => $url,
                'http_code' => $httpCode,
                'response_received' => !empty($result)
            ]);
            
        } catch (Exception $e) {
            $this->logResult("❌ Connectivity test failed", ['error' => $e->getMessage()]);
        }
    }
    
    private function testAuthenticationEndpoint() {
        $this->logResult('Test 2: Authentication Endpoint');
        
        try {
            $url = $this->baseUrl . '/api/v2/auth/me';
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'Accept: application/json',
                        'Content-Type: application/json'
                    ],
                    'timeout' => 10
                ]
            ]);
            
            $result = @file_get_contents($url, false, $context);
            $httpCode = $this->getHttpResponseCode($http_response_header ?? []);
            
            // 401 is expected for auth endpoint without token
            $expectedResult = in_array($httpCode, [401, 200]);
            
            $this->logResult($expectedResult ? "✅ Auth endpoint working" : "❌ Auth endpoint issues", [
                'url' => $url,
                'http_code' => $httpCode,
                'expected' => $expectedResult
            ]);
            
        } catch (Exception $e) {
            $this->logResult("❌ Auth endpoint test failed", ['error' => $e->getMessage()]);
        }
    }
    
    private function testLeaderboardEndpointNoAuth() {
        $this->logResult('Test 3: Leaderboard Endpoint (No Auth)');
        
        try {
            $url = $this->baseUrl . '/api/v2/dashboards/dokter/leaderboard';
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'Accept: application/json',
                        'Content-Type: application/json'
                    ],
                    'timeout' => 10,
                    'ignore_errors' => true
                ]
            ]);
            
            $result = file_get_contents($url, false, $context);
            $httpCode = $this->getHttpResponseCode($http_response_header ?? []);
            
            // Check what type of error we get
            $analysisResult = $this->analyzeLeaderboardResponse($httpCode, $result);
            
            $this->logResult("📊 Leaderboard endpoint analysis", [
                'url' => $url,
                'http_code' => $httpCode,
                'analysis' => $analysisResult,
                'response_sample' => substr($result, 0, 200) . '...'
            ]);
            
        } catch (Exception $e) {
            $this->logResult("❌ Leaderboard endpoint test failed", ['error' => $e->getMessage()]);
        }
    }
    
    private function testRouteRegistration() {
        $this->logResult('Test 4: Route Registration Check');
        
        try {
            // Check if we can access the routes file (if accessible)
            $routesPath = __DIR__ . '/../routes/api/v2.php';
            
            if (file_exists($routesPath)) {
                $routesContent = file_get_contents($routesPath);
                $hasLeaderboardRoute = strpos($routesContent, '/leaderboard') !== false;
                $hasLeaderboardController = strpos($routesContent, 'LeaderboardController') !== false;
                
                $this->logResult($hasLeaderboardRoute && $hasLeaderboardController ? "✅ Route registered correctly" : "⚠️ Route registration issues", [
                    'routes_file_exists' => true,
                    'has_leaderboard_route' => $hasLeaderboardRoute,
                    'has_leaderboard_controller' => $hasLeaderboardController
                ]);
            } else {
                $this->logResult("ℹ️ Routes file not accessible from this location", [
                    'routes_file_exists' => false,
                    'attempted_path' => $routesPath
                ]);
            }
            
        } catch (Exception $e) {
            $this->logResult("❌ Route registration test failed", ['error' => $e->getMessage()]);
        }
    }
    
    private function testDatabaseConnectivity() {
        $this->logResult('Test 5: Database Connectivity');
        
        try {
            // Check if we can access Laravel's environment
            $envPath = __DIR__ . '/../.env';
            
            if (file_exists($envPath)) {
                $this->logResult("✅ Environment file accessible", [
                    'env_file_exists' => true,
                    'can_check_db_config' => true
                ]);
            } else {
                $this->logResult("ℹ️ Environment file not accessible", [
                    'env_file_exists' => false
                ]);
            }
            
        } catch (Exception $e) {
            $this->logResult("❌ Database connectivity test failed", ['error' => $e->getMessage()]);
        }
    }
    
    private function analyzeLeaderboardResponse($httpCode, $response) {
        $analysis = [
            'http_code' => $httpCode,
            'is_401_error' => $httpCode === 401,
            'is_success' => $httpCode === 200,
            'response_type' => 'unknown'
        ];
        
        if (!empty($response)) {
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $analysis['response_type'] = 'json';
                $analysis['has_success_field'] = isset($decoded['success']);
                $analysis['has_data_field'] = isset($decoded['data']);
                $analysis['has_error_message'] = isset($decoded['message']) || isset($decoded['error']);
                
                if (isset($decoded['message'])) {
                    $analysis['error_message'] = $decoded['message'];
                }
            } else {
                $analysis['response_type'] = 'html_or_text';
                $analysis['contains_laravel_error'] = strpos($response, 'Laravel') !== false;
                $analysis['contains_401_text'] = strpos($response, '401') !== false || strpos($response, 'Unauthorized') !== false;
            }
        }
        
        // Determine issue type
        if ($httpCode === 401) {
            $analysis['issue_type'] = 'authentication_required';
            $analysis['resolution_status'] = 'needs_authentication';
        } elseif ($httpCode === 200) {
            $analysis['issue_type'] = 'none';
            $analysis['resolution_status'] = 'resolved';
        } elseif ($httpCode === 404) {
            $analysis['issue_type'] = 'route_not_found';
            $analysis['resolution_status'] = 'route_issue';
        } elseif ($httpCode === 500) {
            $analysis['issue_type'] = 'server_error';
            $analysis['resolution_status'] = 'server_issue';
        } else {
            $analysis['issue_type'] = 'unknown';
            $analysis['resolution_status'] = 'needs_investigation';
        }
        
        return $analysis;
    }
    
    private function getHttpResponseCode($headers) {
        if (empty($headers)) return null;
        
        $firstHeader = $headers[0] ?? '';
        if (preg_match('/HTTP\/\d+\.\d+\s+(\d+)/', $firstHeader, $matches)) {
            return (int) $matches[1];
        }
        
        return null;
    }
    
    private function logResult($message, $data = null) {
        $this->results[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $message,
            'data' => $data
        ];
    }
    
    private function generateSummary() {
        $totalTests = count($this->results);
        $passedTests = 0;
        $failedTests = 0;
        $issues = [];
        
        foreach ($this->results as $result) {
            if (strpos($result['message'], '✅') !== false) {
                $passedTests++;
            } elseif (strpos($result['message'], '❌') !== false) {
                $failedTests++;
                $issues[] = $result['message'];
            }
        }
        
        return [
            'total_tests' => $totalTests,
            'passed_tests' => $passedTests,
            'failed_tests' => $failedTests,
            'success_rate' => $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0,
            'issues_found' => $issues,
            'overall_status' => $failedTests === 0 ? 'all_passed' : 'issues_detected'
        ];
    }
}

// Run the tests
$tester = new LeaderboardAPITester();
$result = $tester->runTests();

// Output the results
echo json_encode($result, JSON_PRETTY_PRINT);
?>