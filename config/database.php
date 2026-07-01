<?php
// config/database.php

// Define the path to the .env file (one folder up from config/)
$envPath = __DIR__ . '/../.env';

if (file_exists($envPath)) {
    // Parse the .env file
    $envVariables = parse_ini_file($envPath);
    
    // Assign variables from .env or fallback to defaults if not found
    $host     = $envVariables['DB_HOST'] ?? 'localhost';
    $dbname   = $envVariables['DB_NAME'] ?? 'smart_event_planner';
    $username = $envVariables['DB_USER'] ?? 'root';
    $password = $envVariables['DB_PASS'] ?? '';
    // Define App URLs for easy portability
    $app_url = $envVariables['APP_URL'] ?? '/smart-planner';
    define('APP_URL', rtrim($app_url, '/'));
    define('BASE_DIR', dirname(__DIR__));
} else {
    // Fallback if .env file is missing
    $host     = 'localhost';
    $dbname   = 'smart_event_planner';
    $username = 'root';
    $password = '';
    define('APP_URL', '/smart-planner');
    define('BASE_DIR', dirname(__DIR__));
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Set robust security settings for PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Prevents SQL injection via emulation

    // Initialize Global Error Handler
    require_once __DIR__ . '/error_handler.php';
    registerGlobalErrorHandler($pdo);

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
