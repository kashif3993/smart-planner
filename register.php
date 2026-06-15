<?php
// ============================================================
//  register.php — Registration Page
//  Smart Event Planner
// ============================================================

session_start();

// Redirect if already logged in
if (!empty($_SESSION['logged_in'])) {
    header('Location: /smart-planner/index.php');
    exit;
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — Smart Event Planner</title>
  <meta name="description" content="Join Smart Event Planner and start managing your events with real-time budget tracking, vendor management, and automated guest invitations.">
  <meta name="robots" content="noindex, nofollow">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&family=Kalam:wght@300;400;700&display=swap" rel="stylesheet">

  <!-- Register CSS -->
  <link rel="stylesheet" href="/smart-planner/assets/css/register.css">
</head>
<body>

<div class="register-wrapper" role="main">

  <!-- ═══════════════════════════════
       LEFT PANEL
  ════════════════════════════════ -->
  <aside class="left-panel" aria-label="App highlights">

    <div class="left-top">
      <h1 class="left-headline">Plan Events<br>with Precision.</h1>
      <p class="left-subtext">
        Join 10,000+ professional planners<br>
        managing premium corporate events<br>
        &amp; weddings worldwide.
      </p>
      <div class="doodle-line"></div>

      <ul class="feature-list" role="list">
        <li class="feature-item">
          <span class="feature-icon">📊</span>
          Real-time Budget Tracking
        </li>
        <li class="feature-item">
          <span class="feature-icon">🤝</span>
          Vendor Management Portal
        </li>
        <li class="feature-item">
          <span class="feature-icon">✉️</span>
          Automated Guest Invitations
        </li>
        <li class="feature-item">
          <span class="feature-icon">📍</span>
          Smart Location Mapping
        </li>
      </ul>
    </div>

   
  </aside>

  <!-- ═══════════════════════════════
       RIGHT PANEL — Form
  ════════════════════════════════ -->
  <section class="right-panel" aria-label="Registration form">

    <div class="step-badge">
      <span class="step-text">Step 1 of 2</span>
    </div>

    <h2 class="form-heading">Create Account</h2>
    <div class="form-underline" aria-hidden="true"></div>

    <!-- Flash message container -->
    <div id="flash-msg" class="flash-message" role="alert" aria-live="polite"></div>

    <form id="register-form" novalidate enctype="multipart/form-data" autocomplete="off">

      <!-- CSRF Token -->
      <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

      <!-- Full Name -->
      <div class="field-group">
        <label class="field-label" for="full_name">Full Name</label>
        <input
          type="text"
          id="full_name"
          name="full_name"
          class="field-input"
          placeholder="Johnathan Doe"
          autocomplete="name"
          maxlength="100"
          required
          aria-describedby="full_name_error"
        >
        <div class="field-error" id="full_name_error" role="alert">
          <span>⚠</span><span class="err-text"></span>
        </div>
      </div>

      <!-- Email -->
      <div class="field-group">
        <label class="field-label" for="email">Email Address</label>
        <input
          type="email"
          id="email"
          name="email"
          class="field-input"
          placeholder="john@eventpro.com"
          autocomplete="email"
          maxlength="191"
          required
          aria-describedby="email_error"
        >
        <div class="field-error" id="email_error" role="alert">
          <span>⚠</span><span class="err-text"></span>
        </div>
      </div>

      <!-- Password -->
      <div class="field-group">
        <label class="field-label" for="password">Password</label>
        <div class="password-wrapper">
          <input
            type="password"
            id="password"
            name="password"
            class="field-input"
            placeholder="Min. 8 characters"
            autocomplete="new-password"
            maxlength="128"
            required
            aria-describedby="password_error password_strength_label"
          >
          <button type="button" class="toggle-password" id="togglePass" aria-label="Toggle password visibility">
            <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>

        <!-- Strength bar -->
        <div class="strength-bar-wrap" aria-hidden="true">
          <div class="strength-segment" id="seg1"></div>
          <div class="strength-segment" id="seg2"></div>
          <div class="strength-segment" id="seg3"></div>
          <div class="strength-segment" id="seg4"></div>
        </div>
        <div class="strength-label">
          <span id="password_strength_label"></span>
        </div>

        <div class="field-error" id="password_error" role="alert">
          <span>⚠</span><span class="err-text"></span>
        </div>
      </div>

      <!-- Confirm Password -->
      <div class="field-group">
        <label class="field-label" for="confirm_password">Confirm Password</label>
        <div class="password-wrapper">
          <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            class="field-input"
            placeholder="Re-enter your password"
            autocomplete="new-password"
            maxlength="128"
            required
            aria-describedby="confirm_password_error"
          >
          <button type="button" class="toggle-password" id="toggleConfirm" aria-label="Toggle confirm password visibility">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
        <div class="field-error" id="confirm_password_error" role="alert">
          <span>⚠</span><span class="err-text"></span>
        </div>
      </div>

      <!-- Profile Image -->
      <div class="field-group">
        <label class="field-label">Profile Image <em style="font-weight:400;color:#9ca3af;">(optional)</em></label>
        <div class="upload-zone" id="uploadZone" role="button" tabindex="0" aria-label="Upload profile image">
          <input type="file" name="profile_image" id="profile_image" accept=".jpg,.jpeg,.png,.gif,.webp" aria-label="Choose profile image">
          <img src="" alt="Preview" class="upload-preview" id="imagePreview">
          <div class="upload-icon" id="uploadIcon">📸</div>
          <div class="upload-text" id="uploadText">
            Drag and drop or <span>Browse files</span><br>
            JPG, PNG up to 2MB
          </div>
        </div>
        <div class="field-error" id="profile_image_error" role="alert">
          <span>⚠</span><span class="err-text"></span>
        </div>
      </div>

      <!-- Terms -->
      <div class="terms-check">
        <input type="checkbox" id="terms" name="terms" required aria-describedby="terms_error">
        <label for="terms">
          I agree to the <a href="#" target="_blank">Terms &amp; Conditions</a> and <a href="#" target="_blank">Privacy Policy</a>
        </label>
      </div>
      <div class="field-error" id="terms_error" role="alert">
        <span>⚠</span><span class="err-text"></span>
      </div>

      <!-- Submit -->
      <button type="submit" class="btn-submit" id="submitBtn">
        <div class="spinner" id="spinner" aria-hidden="true"></div>
        <span class="btn-text">Continue →</span>
      </button>

    </form>

    <p class="form-footer">
      Already have an account? <a href="/smart-planner/login.php">Sign In</a>
    </p>

  </section>
</div>

<script src="/smart-planner/assets/js/register.js"></script>
</body>
</html>
