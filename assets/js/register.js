// ============================================================
//  assets/js/register.js — Client-side validation & UX
// ============================================================

'use strict';

// ── DOM refs ─────────────────────────────────────────────────
const form           = document.getElementById('register-form');
const submitBtn      = document.getElementById('submitBtn');
const flashMsg       = document.getElementById('flash-msg');
const passwordInput  = document.getElementById('password');
const confirmInput   = document.getElementById('confirm_password');
const uploadZone     = document.getElementById('uploadZone');
const fileInput      = document.getElementById('profile_image');
const imagePreview   = document.getElementById('imagePreview');
const uploadIcon     = document.getElementById('uploadIcon');
const uploadText     = document.getElementById('uploadText');

// ── Strength colours ─────────────────────────────────────────
const strengthColors = {
  0: '#e5e7eb',
  1: '#ef4444',   // weak
  2: '#f59e0b',   // fair
  3: '#3b82f6',   // good
  4: '#10b981',   // strong
};

const strengthLabels = {
  0: '',
  1: '😬 Too weak',
  2: '🤔 Fair',
  3: '👍 Good',
  4: '🔒 Strong',
};

// ── Password strength calculator ──────────────────────────────
function calcStrength(pwd) {
  let score = 0;
  if (pwd.length >= 8)          score++;
  if (/[A-Z]/.test(pwd))        score++;
  if (/[0-9]/.test(pwd))        score++;
  if (/[\W_]/.test(pwd))        score++;
  return score;
}

function updateStrengthBar(pwd) {
  const score = pwd.length ? calcStrength(pwd) : 0;
  const label = document.getElementById('password_strength_label');

  for (let i = 1; i <= 4; i++) {
    const seg = document.getElementById('seg' + i);
    seg.style.background = i <= score ? strengthColors[score] : strengthColors[0];
  }
  label.textContent      = strengthLabels[score];
  label.style.color      = strengthColors[score];
}

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
function validateFullName() {
  const val = document.getElementById('full_name').value.trim();
  if (!val)                              { showError('full_name', 'Full name is required.'); return false; }
  if (val.length < 2)                    { showError('full_name', 'Name must be at least 2 characters.'); return false; }
  if (val.length > 100)                  { showError('full_name', 'Name must not exceed 100 characters.'); return false; }
  if (!/^[a-zA-Z\s\-'.]+$/.test(val))   { showError('full_name', 'Name contains invalid characters.'); return false; }
  clearError('full_name'); markValid('full_name');
  return true;
}

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
  if (!val)                     { showError('password', 'Password is required.'); return false; }
  if (val.length < 8)           { showError('password', 'At least 8 characters required.'); return false; }
  if (!/[A-Z]/.test(val))       { showError('password', 'Include at least one uppercase letter.'); return false; }
  if (!/[a-z]/.test(val))       { showError('password', 'Include at least one lowercase letter.'); return false; }
  if (!/[0-9]/.test(val))       { showError('password', 'Include at least one number.'); return false; }
  if (!/[\W_]/.test(val))       { showError('password', 'Include at least one special character.'); return false; }
  clearError('password'); markValid('password');
  return true;
}

function validateConfirm() {
  const val = confirmInput.value;
  if (!val)                               { showError('confirm_password', 'Please confirm your password.'); return false; }
  if (val !== passwordInput.value)        { showError('confirm_password', 'Passwords do not match.'); return false; }
  clearError('confirm_password'); markValid('confirm_password');
  return true;
}

function validateTerms() {
  if (!document.getElementById('terms').checked) {
    showError('terms', 'You must accept the Terms & Conditions.'); return false;
  }
  clearError('terms');
  return true;
}

// ── Real-time listeners ───────────────────────────────────────
document.getElementById('full_name').addEventListener('blur',  validateFullName);
document.getElementById('email').addEventListener('blur',       validateEmail);
passwordInput.addEventListener('input',   () => { updateStrengthBar(passwordInput.value); });
passwordInput.addEventListener('blur',    validatePassword);
confirmInput.addEventListener('blur',     validateConfirm);
document.getElementById('terms').addEventListener('change', validateTerms);

// ── Password toggle ───────────────────────────────────────────
function addToggle(btnId, inputEl) {
  document.getElementById(btnId).addEventListener('click', () => {
    const isPass = inputEl.type === 'password';
    inputEl.type = isPass ? 'text' : 'password';
    const btn    = document.getElementById(btnId);
    btn.setAttribute('aria-label', isPass ? 'Hide password' : 'Show password');
  });
}
addToggle('togglePass',    passwordInput);
addToggle('toggleConfirm', confirmInput);

// ── Image upload preview ──────────────────────────────────────
function handleFile(file) {
  if (!file) return;
  const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  const maxSize      = 2 * 1024 * 1024;

  if (!allowedTypes.includes(file.type)) {
    showError('profile_image', 'Only JPG, PNG, GIF, WEBP are allowed.'); return;
  }
  if (file.size > maxSize) {
    showError('profile_image', 'Image must be under 2 MB.'); return;
  }
  clearError('profile_image');

  const reader = new FileReader();
  reader.onload = (e) => {
    imagePreview.src = e.target.result;
    imagePreview.classList.add('visible');
    uploadIcon.style.display = 'none';
    uploadText.innerHTML     = `<strong>${file.name}</strong><br><span style="color:var(--success)">✓ Ready to upload</span>`;
  };
  reader.readAsDataURL(file);
}

fileInput.addEventListener('change', () => handleFile(fileInput.files[0]));

// Drag & Drop
uploadZone.addEventListener('dragover', (e) => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
uploadZone.addEventListener('dragleave', ()  => uploadZone.classList.remove('drag-over'));
uploadZone.addEventListener('drop', (e)      => {
  e.preventDefault();
  uploadZone.classList.remove('drag-over');
  const file = e.dataTransfer.files[0];
  if (file) {
    // Manually assign to file input via DataTransfer
    const dt = new DataTransfer();
    dt.items.add(file);
    fileInput.files = dt.files;
    handleFile(file);
  }
});

// Keyboard accessibility for upload zone
uploadZone.addEventListener('keydown', (e) => {
  if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); }
});

// ── Form submission ───────────────────────────────────────────
form.addEventListener('submit', async (e) => {
  e.preventDefault();
  clearFlash();

  // Run all validators
  const valid = [
    validateFullName(),
    validateEmail(),
    validatePassword(),
    validateConfirm(),
    validateTerms(),
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
    const response = await fetch('/smart-planner/api/auth/register.php', {
      method:      'POST',
      body:        formData,
      credentials: 'same-origin',
    });

    const data = await response.json();

    if (data.status === 'success') {
      showFlash(data.message, 'success');
      form.reset();
      imagePreview.classList.remove('visible');
      uploadIcon.style.display = '';
      uploadText.innerHTML     = 'Drag and drop or <span>Browse files</span><br>JPG, PNG up to 2MB';
      updateStrengthBar('');

      // Redirect after short delay
      setTimeout(() => {
        window.location.href = data.redirect || '/smart-planner/index.php';
      }, 1200);

    } else if (data.errors) {
      // Map server errors to fields
      Object.entries(data.errors).forEach(([field, msg]) => showError(field, msg));
      showFlash('Please fix the errors highlighted below.');

    } else {
      showFlash(data.message || 'Something went wrong. Please try again.');
    }

  } catch (err) {
    console.error('Registration error:', err);
    showFlash('Network error. Please check your connection and try again.');

  } finally {
    submitBtn.disabled = false;
    submitBtn.classList.remove('loading');
  }
});
