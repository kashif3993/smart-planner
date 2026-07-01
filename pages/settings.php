<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /smart-planner/login');
    exit;
}

$page_css = 'settings.css';
$page_js = 'settings.js';
$current_page = 'settings';

require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/aside.php';
require_once dirname(__DIR__) . '/config/database.php';

// Fetch current user details
$stmt = $pdo->prepare("SELECT full_name, email, profile_image FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<main class="main-content settings-main">
    <div class="dashboard-header flex-header">
        <div>
            <h1>Settings & Data Management</h1>
            <p>Manage your account preferences, backup your data, or reset your workspace.</p>
        </div>
    </div>

    <div class="settings-grid">
        <!-- Profile Card -->
        <div class="card settings-card mb-4">
            <div class="card-header border-bottom">
                <div class="card-title-group">
                    <div class="icon-circle bg-light-blue" style="background: var(--info-light, #dbeafe);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--info, #3b82f6)" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div>
                        <h3>Profile Information</h3>
                        <p class="text-muted">Update your account's profile information and email address.</p>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form id="profileForm" onsubmit="updateProfile(event)" enctype="multipart/form-data">
                    <div class="form-group mb-3" style="display: flex; align-items: center; gap: 15px;">
                        <?php 
                        $imgSrc = !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '/smart-planner/assets/img/default-avatar.png'; 
                        ?>
                        <img src="<?php echo $imgSrc; ?>" alt="Profile" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0;">
                        <div>
                            <label for="profile_image" class="form-label" style="font-weight: 600; font-size: 0.9rem; margin-bottom: 5px; display: block;">Profile Image</label>
                            <input type="file" id="profile_image" name="profile_image" accept="image/*" class="form-control" style="font-size: 0.85rem;">
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="full_name" class="form-label" style="font-weight: 600; font-size: 0.9rem; margin-bottom: 5px; display: block;">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="email" class="form-label" style="font-weight: 600; font-size: 0.9rem; margin-bottom: 5px; display: block;">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>
                    <button type="submit" id="btn-save-profile" class="btn btn-primary mt-2" style="padding: 10px 20px; font-weight: 600; border-radius: 8px;">Save Changes</button>
                    <span id="profile-status" class="status-text ms-3" style="display:none; margin-left: 15px; font-size: 0.9rem;"></span>
                </form>
            </div>
        </div>

        <!-- Data Backup Card -->
        <div class="card settings-card">
            <div class="card-header border-bottom">
                <div class="card-title-group">
                    <div class="icon-circle bg-light-purple">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </div>
                    <div>
                        <h3>Data Backup</h3>
                        <p class="text-muted">Download a complete archive of your events, tasks, expenses, and budgets.</p>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <p>Keeping a local backup of your event planner data is a good practice. Your backup will be generated as a JSON file containing all your records, which you can safely store offline.</p>
                
                <div class="backup-actions">
                    <button id="btn-backup" class="btn btn-primary btn-lg mt-3" onclick="triggerBackup()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Download Data Backup
                    </button>
                    <span id="backup-status" class="status-text ms-3" style="display:none;"></span>
                </div>
            </div>
        </div>

        <!-- Danger Zone Card -->
        <div class="card settings-card danger-card mt-4">
            <div class="card-header border-bottom">
                <div class="card-title-group">
                    <div class="icon-circle bg-light-danger">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                    <div>
                        <h3 class="text-danger">Danger Zone</h3>
                        <p class="text-muted">Irreversible actions regarding your account and data.</p>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="danger-row">
                    <div class="danger-info">
                        <h4>Reset Workspace Data</h4>
                        <p class="text-muted">This will permanently delete all your events, tasks, budgets, vendors, and expenses. Your user account will remain active, but your dashboard will be completely reset to its initial state.</p>
                    </div>
                    <div class="danger-action">
                        <button class="btn btn-outline-danger" onclick="openResetModal()">Reset Data</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Reset Confirmation Modal -->
<div id="resetModal" class="modal">
    <div class="modal-content border-top-danger">
        <div class="modal-header">
            <h2 id="modal-title" class="text-danger">Reset Workspace?</h2>
            <span class="close-modal" onclick="closeResetModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>This action <strong>CANNOT</strong> be undone. All your events, expenses, vendor categories, and tasks will be permanently erased.</p>
            <p>Please type <strong>RESET</strong> to confirm.</p>
            
            <div class="form-group mt-3">
                <input type="text" id="reset-confirm-input" class="form-control" placeholder="Type RESET">
            </div>
            <div class="form-actions mt-4">
                <button type="button" class="btn btn-secondary cancel-modal" onclick="closeResetModal()">Cancel</button>
                <button type="button" id="btn-confirm-reset" class="btn btn-danger" disabled onclick="executeReset()">Permanently Delete Data</button>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
