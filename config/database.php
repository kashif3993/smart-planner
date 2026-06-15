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
} else {
    // Fallback if .env file is missing
    $host     = 'localhost';
    $dbname   = 'smart_event_planner';
    $username = 'root';
    $password = '';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
