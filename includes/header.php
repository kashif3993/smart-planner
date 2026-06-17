<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$header_user_name = $_SESSION['user_name'] ?? 'User';
$header_user_image = !empty($_SESSION['profile_image']) ? '/smart-planner/' . $_SESSION['profile_image'] : 'https://i.pravatar.cc/150?img=11';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>EventPro - Premium Planner</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/smart-planner/assets/css/style.css?v=<?php echo time(); ?>">
    <?php if (isset($page_css)): ?>
    <link rel="stylesheet" href="/smart-planner/assets/css/<?php echo $page_css; ?>?v=<?php echo time(); ?>">
    <?php endif; ?>
</head>
<body>
    <div class="app-container">
        <!-- Header / Topbar -->
        <header class="topbar">
            <button id="mobile-menu-btn" class="mobile-menu-btn" aria-label="Toggle Menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
            </button>
            <div class="search-bar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" placeholder="Search events, tasks, or budgets...">
            </div>
            <div class="topbar-actions">
                <!-- Notifications Dropdown -->
                <div class="dropdown notification-dropdown">
                    <button class="icon-btn notifications active-notify" id="notifBtn" aria-expanded="false" aria-haspopup="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    </button>
                    <div class="dropdown-menu notif-menu" aria-labelledby="notifBtn">
                        <div class="dropdown-header">Notifications</div>
                        <ul class="notif-list">
                            <li>No new notifications</li>
                        </ul>
                    </div>
                </div>
               
                <!-- User Profile Dropdown -->
                <div class="dropdown profile-dropdown">
                    <div class="user-profile" id="profileBtn" aria-expanded="false" aria-haspopup="true" style="cursor: pointer;">
                        <img src="<?php echo htmlspecialchars($header_user_image); ?>" alt="User Profile">
                        <span class="user-name-text"><?php echo htmlspecialchars($header_user_name); ?></span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                    </div>
                    <div class="dropdown-menu profile-menu" aria-labelledby="profileBtn">
                        <div class="dropdown-header">
                            <strong><?php echo htmlspecialchars($header_user_name); ?></strong>
                        </div>
                        <a href="/smart-planner/profile.php" class="dropdown-item">My Profile</a>
                        <a href="/smart-planner/settings.php" class="dropdown-item">Settings</a>
                        <div class="dropdown-divider"></div>
                        <a href="/smart-planner/api/auth/logout.php" class="dropdown-item text-danger">Logout</a>
                    </div>
                </div>
            </div>
        </header>
