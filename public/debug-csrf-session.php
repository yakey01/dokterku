<?php
session_start();
header('Content-Type: application/json');

// Get Laravel's session info
$laravelSession = $_COOKIE['dokterku_session'] ?? 'Not found';
$phpSession = session_id();

// Check CSRF token from different sources
$headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? 'Not provided';
$sessionToken = $_SESSION['_token'] ?? 'Not in session';

echo json_encode([
    'cookies' => $_COOKIE,
    'session_id' => $phpSession,
    'laravel_session_cookie' => $laravelSession,
    'csrf_token_from_header' => $headerToken,
    'csrf_token_from_session' => $sessionToken,
    'session_data' => $_SESSION,
    'server_info' => [
        'HTTP_HOST' => $_SERVER['HTTP_HOST'],
        'SERVER_NAME' => $_SERVER['SERVER_NAME'],
        'REQUEST_URI' => $_SERVER['REQUEST_URI'],
    ]
], JSON_PRETTY_PRINT);