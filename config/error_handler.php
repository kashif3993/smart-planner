<?php
// config/error_handler.php

function registerGlobalErrorHandler($pdo) {
    // Create the error logs table if it doesn't exist
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS system_errors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                error_level VARCHAR(50),
                error_message TEXT,
                file_path VARCHAR(255),
                line_num INT,
                resolved TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    } catch (PDOException $e) {
        // Silently fail if table creation fails (to prevent infinite error loops)
    }

    $logError = function($level, $message, $file, $line) use ($pdo) {
        try {
            // Prevent recursive loops
            static $logging = false;
            if ($logging) return;
            $logging = true;

            $stmt = $pdo->prepare("INSERT INTO system_errors (error_level, error_message, file_path, line_num) VALUES (?, ?, ?, ?)");
            $stmt->execute([$level, $message, $file, $line]);
            
            $logging = false;
        } catch (Exception $e) {
            // Do nothing
        }
    };

    // 1. Handle standard PHP errors (Warnings, Notices)
    set_error_handler(function($errno, $errstr, $errfile, $errline) use ($logError) {
        // Skip notices to avoid clutter
        if ($errno === E_NOTICE || $errno === E_USER_NOTICE || $errno === E_DEPRECATED) return false;
        
        $level = "Error ($errno)";
        if ($errno === E_WARNING || $errno === E_USER_WARNING) $level = 'Warning';
        
        $logError($level, $errstr, $errfile, $errline);
        return false; // Continue normal error execution
    });

    // 2. Handle uncaught Exceptions (including PDOExceptions)
    set_exception_handler(function($exception) use ($logError) {
        $logError('Exception', $exception->getMessage(), $exception->getFile(), $exception->getLine());
        // Do not die here, let the system handle it or display generic message
    });

    // 3. Handle Fatal Errors (Out of memory, undefined functions)
    register_shutdown_function(function() use ($logError) {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_PARSE])) {
            $logError('Fatal Error', $error['message'], $error['file'], $error['line']);
        }
    });
}
?>
