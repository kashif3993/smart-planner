// assets/js/settings.js

document.addEventListener('DOMContentLoaded', () => {
    console.log('Settings JS loaded');
    
    // Listen for input in the reset confirmation box
    const confirmInput = document.getElementById('reset-confirm-input');
    const confirmBtn = document.getElementById('btn-confirm-reset');
    
    if (confirmInput && confirmBtn) {
        confirmInput.addEventListener('input', function() {
            if (this.value === 'RESET') {
                confirmBtn.disabled = false;
            } else {
                confirmBtn.disabled = true;
            }
        });
    }
});

// Update Profile
function updateProfile(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-save-profile');
    const statusText = document.getElementById('profile-status');
    const formData = new FormData(document.getElementById('profileForm'));
    formData.append('action', 'update_profile');

    const originalText = btn.innerHTML;
    btn.innerHTML = 'Saving...';
    btn.disabled = true;
    statusText.style.display = 'none';

    fetch('/smart-planner/api/settings.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        statusText.textContent = data.message;
        statusText.style.display = 'inline-block';
        if(data.status === 'success') {
            statusText.style.color = 'var(--success, #10b981)';
            // Update UI names if necessary
            const navNames = document.querySelectorAll('.user-name');
            navNames.forEach(el => el.textContent = formData.get('full_name'));
            
            // Update profile image if new one was uploaded
            if (data.new_image) {
                const img = document.querySelector('img[alt="Profile"]');
                if (img) img.src = data.new_image;
            }
        } else {
            statusText.style.color = 'var(--danger, #ef4444)';
        }
    })
    .catch(err => {
        statusText.textContent = 'Network error occurred.';
        statusText.style.display = 'inline-block';
        statusText.style.color = 'var(--danger, #ef4444)';
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        setTimeout(() => { statusText.style.display = 'none'; }, 5000);
    });
}

// Trigger the Backup download
function triggerBackup() {
    const btn = document.getElementById('btn-backup');
    const statusText = document.getElementById('backup-status');
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="margin-right: 5px;"></span> Generating Backup...';
    btn.disabled = true;
    statusText.style.display = 'none';

    // We can initiate a download by dynamically creating a form or an invisible iframe,
    // or by fetching the data and creating an ObjectURL. Using fetch gives us better control over errors.
    fetch('/smart-planner/api/settings.php?action=backup', {
        method: 'POST'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.blob();
    })
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        const dateStr = new Date().toISOString().split('T')[0];
        a.download = `smart_planner_backup_${dateStr}.json`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        
        statusText.textContent = "Backup downloaded successfully!";
        statusText.style.display = 'inline-block';
        statusText.style.color = 'var(--success, #10b981)';
    })
    .catch(error => {
        console.error('Backup Error:', error);
        statusText.textContent = "Failed to generate backup.";
        statusText.style.display = 'inline-block';
        statusText.style.color = 'var(--danger, #ef4444)';
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        setTimeout(() => {
            statusText.style.display = 'none';
        }, 5000);
    });
}

// Reset Modal Functions
function openResetModal() {
    const modal = document.getElementById('resetModal');
    const input = document.getElementById('reset-confirm-input');
    const btn = document.getElementById('btn-confirm-reset');
    
    input.value = '';
    btn.disabled = true;
    
    modal.classList.add('show');
}

function closeResetModal() {
    const modal = document.getElementById('resetModal');
    modal.classList.remove('show');
}

// Execute Data Reset
function executeReset() {
    const confirmInput = document.getElementById('reset-confirm-input');
    if (confirmInput.value !== 'RESET') {
        alert("Please type RESET to confirm.");
        return;
    }
    
    const btn = document.getElementById('btn-confirm-reset');
    btn.innerHTML = 'Resetting Data...';
    btn.disabled = true;
    
    const formData = new FormData();
    formData.append('action', 'reset');
    
    fetch('/smart-planner/api/settings.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            alert('Your workspace has been successfully reset. You will now be redirected to the dashboard.');
            window.location.href = '/smart-planner/dashboard';
        } else {
            alert('Error resetting data: ' + data.message);
            btn.innerHTML = 'Permanently Delete Data';
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error('Error during reset:', err);
        alert('A network error occurred while resetting data.');
        btn.innerHTML = 'Permanently Delete Data';
        btn.disabled = false;
    });
}
