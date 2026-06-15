<?php
// ============================================================
//  api/auth/register.php — Secure Registration Handler
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

// ── Rate Limiting (simple IP-based, 5 attempts / 10 min) ────
$ip        = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rateKey   = 'reg_attempts_' . md5($ip);
$maxTries  = 5;
$window    = 600; // 10 minutes in seconds

if (!isset($_SESSION[$rateKey])) {
    $_SESSION[$rateKey] = ['count' => 0, 'first_attempt' => time()];
}

$attempts = &$_SESSION[$rateKey];

// Reset window if expired
if ((time() - $attempts['first_attempt']) > $window) {
    $attempts = ['count' => 0, 'first_attempt' => time()];
}

$attempts['count']++;

if ($attempts['count'] > $maxTries) {
    $waitSeconds = $window - (time() - $attempts['first_attempt']);
    http_response_code(429);
    echo json_encode([
        'status'  => 'error',
        'message' => "Too many attempts. Please wait {$waitSeconds} seconds before trying again."
    ]);
    exit;
}

// ── CSRF Validation ─────────────────────────────────────────
$csrfToken = $_POST['csrf_token'] ?? '';

if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid security token. Please refresh and try again.']);
    exit;
}

// Rotate CSRF token after use
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ── Collect & Sanitize Inputs ────────────────────────────────
$fullName = trim(htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES, 'UTF-8'));
$email    = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';
$terms    = isset($_POST['terms']) ? true : false;

$errors = [];

// ── Full Name Validation ──────────────────────────────────────
if (empty($fullName)) {
    $errors['full_name'] = 'Full name is required.';
} elseif (strlen($fullName) < 2) {
    $errors['full_name'] = 'Name must be at least 2 characters.';
} elseif (strlen($fullName) > 100) {
    $errors['full_name'] = 'Name must not exceed 100 characters.';
} elseif (!preg_match("/^[a-zA-Z\s\-'.]+$/u", $fullName)) {
    $errors['full_name'] = 'Name contains invalid characters.';
}

// ── Email Validation ──────────────────────────────────────────
if (empty($email)) {
    $errors['email'] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email address.';
} elseif (strlen($email) > 191) {
    $errors['email'] = 'Email is too long.';
}

// ── Password Validation ───────────────────────────────────────
if (empty($password)) {
    $errors['password'] = 'Password is required.';
} elseif (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters.';
} elseif (strlen($password) > 128) {
    $errors['password'] = 'Password must not exceed 128 characters.';
} elseif (!preg_match('/[A-Z]/', $password)) {
    $errors['password'] = 'Password must include at least one uppercase letter.';
} elseif (!preg_match('/[a-z]/', $password)) {
    $errors['password'] = 'Password must include at least one lowercase letter.';
} elseif (!preg_match('/[0-9]/', $password)) {
    $errors['password'] = 'Password must include at least one number.';
} elseif (!preg_match('/[\W_]/', $password)) {
    $errors['password'] = 'Password must include at least one special character.';
}

if ($password !== $confirm) {
    $errors['confirm_password'] = 'Passwords do not match.';
}

// ── Terms Validation ──────────────────────────────────────────
if (!$terms) {
    $errors['terms'] = 'You must agree to the Terms & Conditions.';
}

// ── Profile Image Upload (optional) ──────────────────────────
$profileImagePath = null;
$uploadDir = dirname(__DIR__, 2) . '/uploads/avatars/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!empty($_FILES['profile_image']['name'])) {
    $file         = $_FILES['profile_image'];
    $maxSize      = 2 * 1024 * 1024; // 2 MB
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowedExts  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $fileExt      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors['profile_image'] = 'Image upload failed. Please try again.';
    } elseif ($file['size'] > $maxSize) {
        $errors['profile_image'] = 'Image must be under 2 MB.';
    } elseif (!in_array($fileExt, $allowedExts)) {
        $errors['profile_image'] = 'Only JPG, PNG, GIF, WEBP images are allowed.';
    } else {
        // Verify MIME type via file content (not just extension)
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            $errors['profile_image'] = 'Invalid image file. Please upload a real image.';
        } else {
            $newFileName      = uniqid('avatar_', true) . '.' . $fileExt;
            $destinationPath  = $uploadDir . $newFileName;

            if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
                $errors['profile_image'] = 'Failed to save image. Please try again.';
            } else {
                $profileImagePath = 'uploads/avatars/' . $newFileName;
            }
        }
    }
}

// ── Return validation errors early ───────────────────────────
if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'errors' => $errors]);
    exit;
}

// ── Check Email Uniqueness ────────────────────────────────────
try {
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $checkStmt->execute([$email]);

    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'status' => 'error',
            'errors' => ['email' => 'This email is already registered. Please sign in instead.']
        ]);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred. Please try again.']);
    exit;
}

// ── Hash Password (bcrypt, cost 12) ──────────────────────────
$hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// ── Insert User ───────────────────────────────────────────────
try {
    $insertStmt = $pdo->prepare("
        INSERT INTO users (full_name, email, password, profile_image, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $insertStmt->execute([$fullName, $email, $hashedPassword, $profileImagePath]);

    $userId = $pdo->lastInsertId();

    // Invalidate rate limit on success
    unset($_SESSION[$rateKey]);

    // Start authenticated session
    session_regenerate_id(true);
    $_SESSION['user_id']   = $userId;
    $_SESSION['user_name'] = $fullName;
    $_SESSION['user_email']= $email;
    $_SESSION['logged_in'] = true;

    echo json_encode([
        'status'   => 'success',
        'message'  => 'Account created successfully! Welcome aboard.',
        'redirect' => '/smart-planner/index.php'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Registration failed. Please try again.']);
}
