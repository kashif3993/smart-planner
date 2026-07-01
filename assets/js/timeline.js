let velocityChart = null;
let currentView = 'vertical'; // 'vertical' or 'horizontal'
let timelineData = [];

document.addEventListener('DOMContentLoaded', () => {
    fetchTimelineData();

    document.getElementById('btn-vertical-view').addEventListener('click', () => setView('vertical'));
    document.getElementById('btn-horizontal-view').addEventListener('click', () => setView('horizontal'));
});

async function fetchTimelineData() {
    try {
        const response = await fetch('/smart-planner/api/timeline.php?action=data');
        const result = await response.json();

        if (result.status === 'success') {
            const data = result.data;
            document.getElementById('timeline-project-name').textContent = data.event_name;
            document.getElementById('kpi-days-left').textContent = data.days_left;
            document.getElementById('kpi-completed').textContent = data.completed_percent + '%';
            document.getElementById('velocity-text').textContent = data.velocity_text;
            
            timelineData = data.timeline;
            renderTimeline();
            initChart(data.velocity_chart);
        } else {
            document.getElementById('timeline-container').innerHTML = `<p class="text-danger" style="padding:2rem;">Error: ${result.message}</p>`;
        }
    } catch (err) {
        console.error('Error fetching timeline data:', err);
        document.getElementById('timeline-container').innerHTML = '<p class="text-danger" style="padding:2rem;">Failed to load timeline data.</p>';
    }
}

function setView(view) {
    currentView = view;
    document.getElementById('btn-vertical-view').classList.toggle('active', view === 'vertical');
    document.getElementById('btn-horizontal-view').classList.toggle('active', view === 'horizontal');
    renderTimeline();
}

function renderTimeline() {
    const container = document.getElementById('timeline-container');
    container.className = `timeline-wrapper ${currentView}`;
    container.innerHTML = '';

    if (timelineData.length === 0) {
        container.innerHTML = '<p style="padding:2rem; text-align:center; color:var(--text-muted);">No tasks or milestones found for this event.</p>';
        return;
    }

    timelineData.forEach((item, index) => {
        const sideClass = (index % 2 === 0) ? 'left' : 'right';
        const dateObj = new Date(item.due_date);
        const dateStr = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        
        let dotInner = '';
        if (item.status === 'Complete') {
            dotInner = '<i class="fa-solid fa-check" style="color: #6366f1; font-size: 12px; position:absolute; top:0; left:0; right:0; bottom:0; margin:auto; display:flex; align-items:center; justify-content:center;"></i>';
        }

        const html = `
            <div class="timeline-item ${sideClass}">
                <div class="timeline-dot status-${item.status}">
                    ${dotInner}
                </div>
                <div class="timeline-card">
                    <div class="timeline-card-header">
                        <span class="timeline-date">Due Date: ${dateStr}</span>
                        <span class="status-indicator ${item.status}">${item.status}</span>
                    </div>
                    <span class="timeline-phase">${item.phase}</span>
                    <h3 class="timeline-title">${item.task_name}</h3>
                    <p class="timeline-notes">${item.notes}</p>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    });
}

function initChart(dataArray) {
    if (typeof Chart === 'undefined') return;

    const ctx = document.getElementById('velocityChart').getContext('2d');
    
    let gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(199, 210, 254, 1)');
    gradient.addColorStop(1, 'rgba(224, 231, 255, 1)');

    if (velocityChart) {
        velocityChart.destroy();
    }

    velocityChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['W1', 'W2', 'W3', 'W4', 'W5', 'W6'],
            datasets: [{
                label: 'Velocity',
                data: dataArray,
                backgroundColor: gradient,
                borderRadius: 8,
                borderSkipped: false,
                barThickness: 30
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { display: false, beginAtZero: true },
                x: { 
                    grid: { display: false, drawBorder: false },
                    ticks: { display: false }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: { enabled: true }
            }
        }
    });
}
