<?php
// ============================================================
//  api/auth/login.php — Secure Login Handler
//  Database: smart_event_planner
// ============================================================

session_start();

require_once dirname(__DIR__, 2) . '/config/database.php';

header('Content-Type: application/json');

// ── Only allow POST ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}



// ── Collect & Sanitize Inputs ────────────────────────────────
$email    = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
$password = $_POST['password'] ?? '';

$errors = [];

if (empty($email)) {
    $errors['email'] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email address.';
}

if (empty($password)) {
    $errors['password'] = 'Password is required.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'errors' => $errors]);
    exit;
}

// ── Authenticate User ─────────────────────────────────────────
try {
    $stmt = $pdo->prepare("SELECT id, full_name, email, password, profile_image FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Success

        // Start authenticated session
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email']= $user['email'];
        $_SESSION['profile_image'] = $user['profile_image'];
        $_SESSION['logged_in'] = true;

        echo json_encode([
            'status'   => 'success',
            'message'  => 'Logged in successfully! Redirecting...',
            'redirect' => '/smart-planner/dashboard' // Changed to use the cleaner route
        ]);

    } else {
        // Invalid credentials
        http_response_code(401);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Invalid email or password.'
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred. Please try again.']);
}
