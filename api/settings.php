<?php
// api/settings.php
require_once dirname(__DIR__) . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// Handle POST requests
if ($method === 'POST') {
    $action = $_POST['action'] ?? $_GET['action'] ?? null;

    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($full_name) || empty($email)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Name and email are required.']);
            exit;
        }
        
        $profile_image_path = null;
        
        // Handle file upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = dirname(__DIR__) . '/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_info = pathinfo($_FILES['profile_image']['name']);
            $ext = strtolower($file_info['extension']);
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($ext, $allowed_exts)) {
                $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
                $destination = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $destination)) {
                    $profile_image_path = '/smart-planner/uploads/' . $new_filename;
                }
            } else {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Invalid image format.']);
                exit;
            }
        }

        try {
            if ($profile_image_path) {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, profile_image = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $profile_image_path, $user_id]);
                $_SESSION['profile_image'] = $profile_image_path;
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $user_id]);
            }
            $_SESSION['name'] = $full_name; // Update session
            
            echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully.', 'new_image' => $profile_image_path]);
            exit;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to update profile.']);
            exit;
        }
    }
    elseif ($action === 'backup') {
        try {
            $backup_data = [
                'generated_at' => date('Y-m-d H:i:s'),
                'user_id' => $user_id,
                'events' => [],
                'vendor_categories' => [],
                'tasks' => [],
                'expenses' => []
            ];

            // Fetch events
            $stmt = $pdo->prepare("SELECT * FROM events WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $backup_data['events'] = $events;

            if (!empty($events)) {
                $event_ids = array_column($events, 'id');
                $inQuery = implode(',', array_fill(0, count($event_ids), '?'));
                
                // Fetch vendor categories
                $stmt = $pdo->prepare("SELECT * FROM vendor_categories WHERE event_id IN ($inQuery)");
                $stmt->execute($event_ids);
                $backup_data['vendor_categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Fetch tasks
                $stmt = $pdo->prepare("SELECT * FROM tasks WHERE event_id IN ($inQuery)");
                $stmt->execute($event_ids);
                $backup_data['tasks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Fetch expenses
                $stmt = $pdo->prepare("SELECT * FROM expenses WHERE event_id IN ($inQuery)");
                $stmt->execute($event_ids);
                $backup_data['expenses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Output JSON file for download
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="smart_planner_backup_' . date('Y-m-d') . '.json"');
            echo json_encode($backup_data, JSON_PRETTY_PRINT);
            exit;

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Database error during backup: ' . $e->getMessage()]);
            exit;
        }
    } 
    elseif ($action === 'reset') {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Fetch all event IDs for the user
            $stmt = $pdo->prepare("SELECT id FROM events WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $events = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($events)) {
                $inQuery = implode(',', array_fill(0, count($events), '?'));
                
                // Delete dependent records first (if ON DELETE CASCADE is not set)
                $stmt = $pdo->prepare("DELETE FROM expenses WHERE event_id IN ($inQuery)");
                $stmt->execute($events);
                
                $stmt = $pdo->prepare("DELETE FROM tasks WHERE event_id IN ($inQuery)");
                $stmt->execute($events);
                
                $stmt = $pdo->prepare("DELETE FROM vendor_categories WHERE event_id IN ($inQuery)");
                $stmt->execute($events);
                
                // Delete events
                $stmt = $pdo->prepare("DELETE FROM events WHERE user_id = ?");
                $stmt->execute([$user_id]);
            }

            // Commit transaction
            $pdo->commit();

            echo json_encode(['status' => 'success', 'message' => 'Workspace data successfully reset.']);
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to reset data: ' . $e->getMessage()]);
            exit;
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid action specified.']);
        exit;
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}
