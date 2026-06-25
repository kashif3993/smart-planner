// assets/js/events.js

let allEvents = [];

// ==========================================
// TASK FILTER FUNCTION
// ==========================================
function filterTasks() {
    const search   = (document.getElementById('taskSearchInput')?.value || '').toLowerCase();
    const phase    = document.getElementById('filterPhase')?.value    || '';
    const priority = document.getElementById('filterPriority')?.value || '';
    const status   = document.getElementById('filterStatus')?.value   || '';

    const rows      = document.querySelectorAll('.task-table tbody tr.task-row');
    const phaseRows = document.querySelectorAll('.task-table tbody tr.phase-row');

    // Hide all phase headers first; we'll re-show if needed
    phaseRows.forEach(pr => pr.style.display = 'none');

    rows.forEach(row => {
        const taskName     = row.querySelector('.t-name')?.textContent.toLowerCase()  || '';
        const taskPhase    = row.querySelector('td:nth-child(2)')?.textContent.trim() || '';
        const taskDue      = row.querySelector('td:nth-child(3)')?.textContent.trim() || '';
        const taskPriority = row.querySelector('.badge')?.textContent.trim()          || '';
        const taskStatus   = row.querySelector('.status-text')?.textContent.trim()    || '';

        const matchSearch   = !search   || taskName.includes(search);
        const matchPhase    = !phase    || taskPhase    === phase;
        const matchPriority = !priority || taskPriority === priority;
        const matchStatus   = !status   || taskStatus   === status || 
                              (status === 'Pending' && taskStatus === 'Pending') ||
                              (status === 'Completed' && taskStatus === 'Done');

        row.style.display = (matchSearch && matchPhase && matchPriority && matchStatus) ? '' : 'none';
    });

    // Re-show phase headers that have at least one visible task below them
    phaseRows.forEach(phaseRow => {
        let next = phaseRow.nextElementSibling;
        while (next && next.classList.contains('task-row')) {
            if (next.style.display !== 'none') {
                phaseRow.style.display = '';
                break;
            }
            next = next.nextElementSibling;
        }
    });
}


document.addEventListener('DOMContentLoaded', () => {
    // Detail View: Handle task status toggles
    const statusCheckboxes = document.querySelectorAll('.task-status-cb');
    statusCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const statusTextSpan = this.closest('.status-toggle').querySelector('.status-text');
            if (this.checked) {
                statusTextSpan.textContent = 'Done';
            } else {
                statusTextSpan.textContent = 'Pending';
            }
        });
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('new')) {
        setTimeout(() => openEventModal(), 100);
    }

    // List View: Fetch and Render Events
    const eventsGrid = document.getElementById('eventsGrid');
    if (eventsGrid) {
        fetchEvents();
    }

    // Modal Form Submission
    const eventForm = document.getElementById('eventForm');
    if (eventForm) {
        eventForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(eventForm);
            
            fetch('/smart-planner/api/events.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    closeEventModal();
                    if(document.getElementById('eventsGrid')) {
                        fetchEvents(); // Refresh list if on list view
                    } else {
                        window.location.reload(); // Refresh detail view
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error('Error saving event:', err);
                alert('A network error occurred.');
            });
        });
    }

    // Task Modal Form Submission
    const taskForm = document.getElementById('taskForm');
    if (taskForm) {
        taskForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(taskForm);
            
            fetch('/smart-planner/api/tasks.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    closeTaskModal();
                    window.location.reload(); // Refresh detail view to show new task
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error('Error saving task:', err);
                alert('A network error occurred.');
            });
        });
    }
});

function fetchEvents() {
    fetch('/smart-planner/api/events.php?action=list')
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            allEvents = data.data;
            renderEventsGrid();
        } else {
            document.getElementById('eventsGrid').innerHTML = '<p>Error loading events.</p>';
        }
    })
    .catch(err => {
        console.error('Error fetching events:', err);
        document.getElementById('eventsGrid').innerHTML = '<p>Error loading events.</p>';
    });
}

function renderEventsGrid() {
    const grid = document.getElementById('eventsGrid');
    if (!grid) return;
    
    grid.innerHTML = '';
    
    allEvents.forEach(evt => {
        // Calculate progress (total_tasks vs completed_tasks)
        const total = parseInt(evt.total_tasks) || 0;
        const completed = parseInt(evt.completed_tasks) || 0;
        const progress = total > 0 ? Math.round((completed / total) * 100) : 0;
        
        // Format Currency & Budget
        const budgetFormatted = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'PKR' }).format(evt.total_budget || 0);
        
        // Format Date
        const dateObj = new Date(evt.event_date);
        const dateFormatted = dateObj.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
        
        // Calculate Days Remaining
        const today = new Date();
        const diffTime = dateObj - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        const daysText = diffDays < 0 ? `${Math.abs(diffDays)} Days Ago` : `${diffDays} Days Remaining`;
        
        const cardHtml = `
            <div class="card event-grid-card" onclick="window.location.href='/smart-planner/events.php?id=${evt.id}'">
                <div style="display:flex; justify-content: space-between;">
                    <span class="badge badge-purple">${evt.event_type}</span>
                    <button class="icon-btn" onclick="event.stopPropagation(); openEventModal(${JSON.stringify(evt).replace(/"/g, '&quot;')})" style="padding:4px; border:none; background:none; cursor:pointer;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg></button>
                </div>
                <h3>${evt.event_name}</h3>
                
                <div class="grid-card-meta">
                    <div class="meta-col">
                        <span class="meta-label">Date</span>
                        <span class="meta-value">${dateFormatted}</span>
                    </div>
                    <div class="meta-col">
                        <span class="meta-label">Budget</span>
                        <span class="meta-value">${budgetFormatted}</span>
                    </div>
                </div>
                
                <div class="progress-row">
                    <span class="progress-label">Planning Progress</span>
                    <span class="progress-val">${progress}%</span>
                </div>
                <div class="progress-bar-thin" style="margin-bottom: 0;">
                    <div class="fill purple-bg" style="width: ${progress}%;"></div>
                </div>
                
                <div class="grid-card-footer">
                    <div class="days-left">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        ${daysText}
                    </div>
                    <div class="avatars">
                        <div class="avatar-circle" style="background-image: url('https://i.pravatar.cc/150?img=11')"></div>
                        <div class="avatar-circle" style="background-image: url('https://i.pravatar.cc/150?img=32')"></div>
                        <div class="avatar-circle">+${evt.guest_count > 2 ? evt.guest_count - 2 : 0}</div>
                    </div>
                </div>
            </div>
        `;
        grid.insertAdjacentHTML('beforeend', cardHtml);
    });

    // Add "Plan New Event" Card
    const newCardHtml = `
        <div class="card plan-new-card" onclick="openEventModal()">
            <div class="plan-new-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </div>
            <h3>Plan New Event</h3>
            <p>Start drafting your next masterpiece with our premium planning tools.</p>
            <button class="btn btn-primary" onclick="event.stopPropagation(); openEventModal()">Create Event</button>
        </div>
    `;
    grid.insertAdjacentHTML('beforeend', newCardHtml);
}

// Modal Functions
function openEventModal(eventData = null) {
    const modal = document.getElementById('eventModal');
    const form = document.getElementById('eventForm');
    const title = document.getElementById('modalTitle');
    const deleteBtn = document.getElementById('btnDeleteEvent');
    
    form.reset();
    
    if (eventData) {
        // Editing Mode
        title.textContent = 'Edit Event';
        document.getElementById('formAction').value = 'update';
        document.getElementById('eventId').value = eventData.id;
        
        document.getElementById('eventName').value = eventData.event_name;
        document.getElementById('eventType').value = eventData.event_type;
        document.getElementById('eventDate').value = eventData.event_date;
        document.getElementById('totalBudget').value = eventData.total_budget;
        document.getElementById('venueName').value = eventData.venue_name || '';
        document.getElementById('location').value = eventData.location || '';
        document.getElementById('guestCount').value = eventData.guest_count;
        document.getElementById('status').value = eventData.status;
        document.getElementById('description').value = eventData.description || '';
        
        deleteBtn.style.display = 'block';
    } else {
        // Create Mode
        title.textContent = 'Create New Event';
        document.getElementById('formAction').value = 'create';
        document.getElementById('eventId').value = '';
        deleteBtn.style.display = 'none';
    }
    
    modal.classList.add('show');
}

function closeEventModal() {
    const modal = document.getElementById('eventModal');
    modal.classList.remove('show');
}

function deleteEvent() {
    if (!confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
        return;
    }
    
    const eventId = document.getElementById('eventId').value;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', eventId);
    
    fetch('/smart-planner/api/events.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            closeEventModal();
            if(document.getElementById('eventsGrid')) {
                fetchEvents();
            } else {
                // If on detail view, redirect to list
                window.location.href = '/smart-planner/events';
            }
        } else {
            alert('Error deleting event: ' + data.message);
        }
    })
    .catch(err => {
        console.error('Error deleting event:', err);
        alert('A network error occurred.');
    });
}

// ==========================================
// TASK MODAL FUNCTIONS
// ==========================================

function openTaskModal(taskData = null) {
    const modal = document.getElementById('taskModal');
    const form = document.getElementById('taskForm');
    const title = document.getElementById('taskModalTitle');
    const deleteBtn = document.getElementById('btnDeleteTask');
    const saveBtn = document.getElementById('btnSaveTask');
    
    form.reset();
    
    if (taskData) {
        // Editing Mode
        title.textContent = 'Edit Task';
        saveBtn.textContent = 'Update Task';
        document.getElementById('taskFormAction').value = 'update';
        document.getElementById('taskId').value = taskData.id;
        
        document.getElementById('taskName').value = taskData.task_name;
        document.getElementById('taskPhase').value = taskData.phase;
        document.getElementById('taskDueDate').value = taskData.due_date || '';
        document.getElementById('taskPriority').value = taskData.priority;
        document.getElementById('taskStatus').value = taskData.status;
        document.getElementById('taskNotes').value = taskData.notes || '';
        
        deleteBtn.style.display = 'block';
    } else {
        // Create Mode
        title.textContent = 'Add New Task';
        saveBtn.textContent = 'Save Task';
        document.getElementById('taskFormAction').value = 'create';
        document.getElementById('taskId').value = '';
        deleteBtn.style.display = 'none';
    }
    
    modal.classList.add('show');
}

function closeTaskModal() {
    const modal = document.getElementById('taskModal');
    modal.classList.remove('show');
}

function deleteTask() {
    if (!confirm('Are you sure you want to delete this task?')) {
        return;
    }
    
    const taskId = document.getElementById('taskId').value;
    _doDeleteTask(taskId);
}

// Called directly from row Delete button (no modal needed)
function deleteTaskById(taskId) {
    if (!confirm('Are you sure you want to delete this task?')) {
        return;
    }
    _doDeleteTask(taskId);
}

function _doDeleteTask(taskId) {
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', taskId);
    
    fetch('/smart-planner/api/tasks.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            closeTaskModal();
            window.location.reload();
        } else {
            alert('Error deleting task: ' + data.message);
        }
    })
    .catch(err => {
        console.error('Error deleting task:', err);
        alert('A network error occurred.');
    });
}
