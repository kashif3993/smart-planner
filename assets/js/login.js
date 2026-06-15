// ============================================================
//  assets/js/login.js — Client-side validation & UX
// ============================================================

'use strict';

// ── DOM refs ─────────────────────────────────────────────────
const form           = document.getElementById('login-form');
const submitBtn      = document.getElementById('submitBtn');
const flashMsg       = document.getElementById('flash-msg');
const passwordInput  = document.getElementById('password');

// ── Show / clear field error ──────────────────────────────────
function showError(fieldId, msg) {
  const errBox = document.getElementById(fieldId + '_error');
  const input  = document.getElementById(fieldId);

  if (errBox) {
    errBox.querySelector('.err-text').textContent = msg;
    errBox.classList.add('visible');
  }
  if (input) {
    input.classList.add('is-error');
    input.classList.remove('is-valid');
  }
}

function clearError(fieldId) {
  const errBox = document.getElementById(fieldId + '_error');
  const input  = document.getElementById(fieldId);

  if (errBox) {
    errBox.classList.remove('visible');
    errBox.querySelector('.err-text').textContent = '';
  }
  if (input) {
    input.classList.remove('is-error');
  }
}

function markValid(fieldId) {
  const input = document.getElementById(fieldId);
  if (input) {
    input.classList.add('is-valid');
    input.classList.remove('is-error');
  }
}

// ── Flash message ─────────────────────────────────────────────
function showFlash(msg, type = 'error') {
  flashMsg.textContent  = (type === 'error' ? '⚠ ' : '✅ ') + msg;
  flashMsg.className    = 'flash-message ' + type;
  flashMsg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function clearFlash() {
  flashMsg.textContent = '';
  flashMsg.className   = 'flash-message';
}

// ── Client-side validators ────────────────────────────────────
function validateEmail() {
  const val = document.getElementById('email').value.trim();
  const re  = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!val)          { showError('email', 'Email address is required.'); return false; }
  if (!re.test(val)) { showError('email', 'Please enter a valid email address.'); return false; }
  clearError('email'); markValid('email');
  return true;
}

function validatePassword() {
  const val = passwordInput.value;
  if (!val) { showError('password', 'Password is required.'); return false; }
  clearError('password'); markValid('password');
  return true;
}

// ── Real-time listeners ───────────────────────────────────────
document.getElementById('email').addEventListener('blur',       validateEmail);
passwordInput.addEventListener('blur',    validatePassword);

// ── Password toggle ───────────────────────────────────────────
function addToggle(btnId, inputEl) {
  document.getElementById(btnId).addEventListener('click', () => {
    const isPass = inputEl.type === 'password';
    inputEl.type = isPass ? 'text' : 'password';
    const btn    = document.getElementById(btnId);
    btn.setAttribute('aria-label', isPass ? 'Hide password' : 'Show password');
  });
}
if(document.getElementById('togglePass')) {
    addToggle('togglePass', passwordInput);
}

// ── Form submission ───────────────────────────────────────────
form.addEventListener('submit', async (e) => {
  e.preventDefault();
  clearFlash();

  // Run all validators
  const valid = [
    validateEmail(),
    validatePassword()
  ].every(Boolean);

  if (!valid) {
    showFlash('Please fix the errors highlighted below.');
    // Focus first invalid field
    const firstError = form.querySelector('.field-input.is-error');
    if (firstError) firstError.focus();
    return;
  }

  // UI loading state
  submitBtn.disabled = true;
  submitBtn.classList.add('loading');

  const formData = new FormData(form);

  try {
    const response = await fetch('/smart-planner/api/auth/login.php', {
      method:      'POST',
      body:        formData,
      credentials: 'same-origin',
    });

    const data = await response.json();

    if (data.status === 'success') {
      showFlash(data.message, 'success');
      form.reset();

      // Redirect after short delay
      setTimeout(() => {
        window.location.href = data.redirect || '/smart-planner/index.php';
      }, 1000);

    } else if (data.errors) {
      // Map server errors to fields
      Object.entries(data.errors).forEach(([field, msg]) => showError(field, msg));
      showFlash('Please fix the errors highlighted below.');

    } else {
      showFlash(data.message || 'Invalid email or password.');
    }

  } catch (err) {
    console.error('Login error:', err);
    showFlash('Network error. Please check your connection and try again.');

  } finally {
    submitBtn.disabled = false;
    submitBtn.classList.remove('loading');
  }
});
