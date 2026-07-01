<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="fa-solid fa-layer-group" style="font-size: 24px;"></i>
        </div>
        <div class="brand-text">
            <h2>EventPro</h2>
            <span>Premium Planner</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo ($current_page ?? '') === 'dashboard' ? 'active' : ''; ?>">
                <a href="/smart-planner/dashboard">
                    <i class="fa-solid fa-border-all" style="font-size: 18px; width: 24px; text-align: center;"></i>
                    Dashboard
                </a>
            </li>
            <li class="<?php echo ($current_page ?? '') === 'events' ? 'active' : ''; ?>">
                <a href="/smart-planner/events">
                    <i class="fa-regular fa-calendar" style="font-size: 18px; width: 24px; text-align: center;"></i>
                    Events
                </a>
            </li>
            <li class="<?php echo ($current_page ?? '') === 'timeline' ? 'active' : ''; ?>">
                <a href="/smart-planner/timeline">
                    <i class="fa-solid fa-timeline" style="font-size: 18px; width: 24px; text-align: center;"></i>
                    Timeline
                </a>
            </li>
            <li class="<?php echo ($current_page ?? '') === 'activity' ? 'active' : ''; ?>">
                <a href="/smart-planner/activity">
                    <i class="fa-solid fa-bolt" style="font-size: 18px; width: 24px; text-align: center;"></i>
                    Activity Log
                </a>
            </li>

            <li class="<?php echo ($current_page ?? '') === 'budget' ? 'active' : ''; ?>">
                <a href="/smart-planner/budget">
                    <i class="fa-solid fa-chart-pie" style="font-size: 18px; width: 24px; text-align: center;"></i>
                    Budget
                </a>
            </li>
            <li class="<?php echo ($current_page ?? '') === 'vendors' ? 'active' : ''; ?>">
                <a href="/smart-planner/vendors">
                    <i class="fa-solid fa-store" style="font-size: 18px; width: 24px; text-align: center;"></i>
                    Vendors
                </a>
            </li>
            <li class="<?php echo ($current_page ?? '') === 'expenses' ? 'active' : ''; ?>">
                <a href="/smart-planner/expenses">
                    <i class="fa-solid fa-money-bill-wave" style="font-size: 18px; width: 24px; text-align: center;"></i>
                    Expenses
                </a>
            </li>
            <li class="<?php echo ($current_page ?? '') === 'progress' ? 'active' : ''; ?>">
                <a href="/smart-planner/progress">
                    <i class="fa-solid fa-arrow-trend-up" style="font-size: 18px; width: 24px; text-align: center;"></i>
                    Progress
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-bottom">
        <a href="/smart-planner/event?new=1" class="btn btn-primary btn-block" style="display:flex; justify-content:center; align-items:center; gap:8px; text-decoration:none;">
            <i class="fa-solid fa-plus"></i>
            New Event
        </a>
        <ul class="bottom-nav">
            <li class="<?php echo ($current_page ?? '') === 'settings' ? 'active' : ''; ?>">
                <a href="/smart-planner/settings">
                    <i class="fa-solid fa-gear" style="font-size: 18px; width: 24px; text-align: center;"></i>
                    Settings
                </a>
            </li>
            <li class="<?php echo ($current_page ?? '') === 'system_health' ? 'active' : ''; ?>">
                <a href="/smart-planner/system_health">
                    <i class="fa-solid fa-stethoscope" style="font-size: 18px; width: 24px; text-align: center;"></i>
                    System Health
                </a>
            </li>
        </ul>
    </div>
</aside>
