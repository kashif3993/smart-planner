<?php
// ============================================================
//  login.php — Login Page
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
  <title>Sign In — Smart Event Planner</title>
  <meta name="description" content="Sign in to your Smart Event Planner account.">
  <meta name="robots" content="noindex, nofollow">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&family=Kalam:wght@300;400;700&display=swap" rel="stylesheet">

  <!-- Login CSS -->
  <link rel="stylesheet" href="/smart-planner/assets/css/login.css">
</head>
<body>

<div class="login-wrapper" role="main">

  <!-- ═══════════════════════════════
       LEFT PANEL
  ════════════════════════════════ -->
  <aside class="left-panel" aria-label="Welcome Back">

    <div class="left-top">
      <h1 class="left-headline">Welcome<br>Back!</h1>
      <p class="left-subtext">
        Log in to continue managing your<br>
        premium corporate events<br>
        &amp; weddings.
      </p>
      <div class="doodle-line"></div>
    </div>
    
    <div class="illustration-card">
      <div class="illustration-inner">
        <div class="calendar-doodle">
          Plan, Execute &amp; Celebrate<br>
          <span style="font-size: 1.5rem">✨</span>
        </div>
      </div>
      <div class="testimonial">
        <div class="testimonial-avatar">A</div>
        <div class="testimonial-info">
          <div class="testimonial-name">Alice Planner</div>
          <div class="testimonial-role">"My favorite event tool!"</div>
        </div>
      </div>
    </div>

  </aside>

  <!-- ═══════════════════════════════
       RIGHT PANEL — Form
  ════════════════════════════════ -->
  <section class="right-panel" aria-label="Login form">

    <h2 class="form-heading">Sign In</h2>
    <div class="form-underline" aria-hidden="true"></div>

    <!-- Flash message container -->
    <div id="flash-msg" class="flash-message" role="alert" aria-live="polite"></div>

    <form id="login-form" novalidate autocomplete="off">

      <!-- CSRF Token -->
      <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

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
        <div style="display: flex; justify-content: space-between; align-items: baseline;">
          <label class="field-label" for="password">Password</label>
          <a href="#" class="forgot-link" style="font-size: .8rem; color: var(--accent); text-decoration: none; font-weight: 500;">Forgot password?</a>
        </div>
        <div class="password-wrapper">
          <input
            type="password"
            id="password"
            name="password"
            class="field-input"
            placeholder="Enter your password"
            autocomplete="current-password"
            maxlength="128"
            required
            aria-describedby="password_error"
          >
          <button type="button" class="toggle-password" id="togglePass" aria-label="Toggle password visibility">
            <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
        <div class="field-error" id="password_error" role="alert">
          <span>⚠</span><span class="err-text"></span>
        </div>
      </div>

      <!-- Remember Me -->
      <div class="terms-check" style="margin-top: 10px;">
        <input type="checkbox" id="remember" name="remember">
        <label for="remember">Remember me for 30 days</label>
      </div>

      <!-- Submit -->
      <button type="submit" class="btn-submit" id="submitBtn">
        <div class="spinner" id="spinner" aria-hidden="true"></div>
        <span class="btn-text">Log In →</span>
      </button>

    </form>

    <p class="form-footer">
      Don't have an account? <a href="/smart-planner/register.php">Create Account</a>
    </p>

  </section>
</div>

<script src="/smart-planner/assets/js/login.js"></script>
</body>
</html>
