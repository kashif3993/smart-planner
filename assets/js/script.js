// assets/js/script.js
document.addEventListener('DOMContentLoaded', () => {
    console.log('Smart Event Planner loaded successfully.');
    
    // Global Mobile Menu Toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');
    
    // Create an overlay element
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        });
        
        // Close sidebar when clicking outside
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }

    // Dropdown Toggles
    const toggleDropdown = (btnId, menuClass) => {
        const btn = document.getElementById(btnId);
        const menu = document.querySelector(`.${menuClass}`);
        if (btn && menu) {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                // Close other dropdowns
                document.querySelectorAll('.dropdown-menu.show').forEach(m => {
                    if (m !== menu) m.classList.remove('show');
                });
                menu.classList.toggle('show');
            });
        }
    };

    toggleDropdown('notifBtn', 'notif-menu');
    toggleDropdown('profileBtn', 'profile-menu');

    // Close dropdowns when clicking outside
    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-menu.show').forEach(m => {
            m.classList.remove('show');
        });
    });
});
