// budget.js

let donutChartInstance = null;
let lineChartInstance = null;

const formatCurrency = (val) => parseFloat(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

const categoryColors = {
    'Venue': '#4f46e5',
    'Catering': '#3b82f6',
    'Marketing': '#8b5cf6',
    'Entertainment': '#0d9488',
    'Logistics': '#ef4444',
    'Decor': '#10b981'
};

const getCatColor = (name, index) => {
    for (let key in categoryColors) {
        if (name.includes(key)) return categoryColors[key];
    }
    const fallback = ['#4f46e5', '#8b5cf6', '#0d9488', '#ef4444', '#f59e0b', '#3b82f6'];
    return fallback[index % fallback.length];
};

document.addEventListener('DOMContentLoaded', () => {
    fetchBudgetAnalytics();
});

async function fetchBudgetAnalytics() {
    try {
        const res = await fetch('/smart-planner/api/budget.php');
        const result = await res.json();
        
        if (result.status === 'success') {
            const data = result.data;
            document.getElementById('event-subtitle').innerText = `Track expenses and manage allocations for "${data.event_name}"`;
            
            // Metrics
            document.getElementById('total-budget').innerText = formatCurrency(data.metrics.total_budget);
            document.getElementById('total-spent').innerText = formatCurrency(data.metrics.total_spent);
            document.getElementById('remaining-budget').innerText = formatCurrency(data.metrics.remaining);
            document.getElementById('health-pct').innerText = `${data.metrics.health_pct}%`;
            document.getElementById('health-bar').style.width = `${data.metrics.health_pct}%`;
            
            if (data.metrics.health_pct > 100) {
                document.getElementById('health-bar').style.backgroundColor = 'var(--text-red)';
                document.querySelector('.green-badge').className = 'badge red-badge';
                document.querySelector('.red-badge').innerText = 'Critical';
            }
            
            if (data.metrics.velocity_text) {
                document.getElementById('velocity-badge').innerText = data.metrics.velocity_text;
                document.getElementById('velocity-badge').style.display = 'inline-block';
            }

            // Donut
            renderDonut(data.donut);
            
            // Horizontal Bars
            renderHorizBars(data.bars);
            
            // Line Chart
            renderLineChart(data.trend);
            
            // Alerts
            renderAlerts(data.alerts);
        }
    } catch (err) {
        console.error(err);
    }
}

function renderDonut(data) {
    if (!data || data.length === 0) return;
    
    // Sort to get top category
    data.sort((a,b) => b.value - a.value);
    document.getElementById('top-category-name').innerText = data[0].label;
    
    const labels = data.map(d => d.label);
    const values = data.map(d => d.value);
    const bgColors = labels.map((l, i) => getCatColor(l, i));
    
    // Build legend
    const legendContainer = document.getElementById('donut-legend');
    legendContainer.innerHTML = '';
    data.forEach((d, i) => {
        legendContainer.innerHTML += `
            <div class="legend-item">
                <div class="legend-item-left">
                    <span class="legend-dot" style="background:${bgColors[i]}"></span>
                    ${d.label}
                </div>
                <div class="legend-item-right">${d.percentage}%</div>
            </div>
        `;
    });

    if (donutChartInstance) donutChartInstance.destroy();
    
    const ctx = document.getElementById('donutChart').getContext('2d');
    donutChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: bgColors,
                borderWidth: 0,
                cutout: '75%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            }
        }
    });
}

function renderHorizBars(data) {
    const container = document.getElementById('bars-container');
    container.innerHTML = '';
    
    if (!data || data.length === 0) return;
    
    // Find global max to scale the bars
    let maxVal = 0;
    data.forEach(d => {
        if (d.estimated > maxVal) maxVal = d.estimated;
        if (d.actual > maxVal) maxVal = d.actual;
    });
    
    if (maxVal === 0) maxVal = 1;

    data.forEach(d => {
        const estPct = (d.estimated / maxVal) * 100;
        const actPct = (d.actual / maxVal) * 100;
        const isOver = d.actual > d.estimated;
        
        let labelAdd = isOver ? `<span style="color:var(--text-red); font-size: 0.7rem; float:right;">Over budget by ${Math.round(((d.actual - d.estimated)/d.estimated)*100)}%</span>` : '';
        
        const html = `
            <div class="horiz-bar-item">
                <div class="horiz-bar-header">
                    <span class="horiz-bar-label">${d.label}</span>
                    <span class="horiz-bar-value">${formatCurrency(d.actual)} / ${formatCurrency(d.estimated)}</span>
                </div>
                <div class="horiz-bar-track">
                    <div class="horiz-bar-estimated" style="width: ${estPct}%"></div>
                    <div class="horiz-bar-actual ${isOver ? 'over' : ''}" style="width: ${actPct}%"></div>
                </div>
                ${labelAdd}
            </div>
        `;
        container.innerHTML += html;
    });
}

function renderLineChart(data) {
    if (!data || data.length === 0) return;
    
    const labels = data.map(d => d.date);
    const values = data.map(d => d.amount);

    if (lineChartInstance) lineChartInstance.destroy();
    
    const ctx = document.getElementById('lineChart').getContext('2d');
    
    // Create gradient
    let gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(79, 70, 229, 0.2)');
    gradient.addColorStop(1, 'rgba(79, 70, 229, 0)');

    lineChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Spent',
                data: values,
                borderColor: '#4f46e5',
                borderWidth: 2,
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { maxTicksLimit: 6 }
                },
                y: {
                    display: false,
                    beginAtZero: true
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
}

function renderAlerts(alerts) {
    const container = document.getElementById('alerts-container');
    container.innerHTML = '';
    
    if (!alerts || alerts.length === 0) {
        container.innerHTML = '<p class="muted-text">No active budget alerts.</p>';
        return;
    }
    
    alerts.forEach(a => {
        const badgeClass = a.type === 'critical' ? 'critical' : 'upcoming';
        const badgeText = a.type === 'critical' ? 'Critical' : 'Upcoming';
        const btnHtml = a.type === 'critical' ? `<button class="alert-btn" onclick="window.location.href='/smart-planner/expenses'">Review Items</button>` : '';
        
        container.innerHTML += `
            <div class="alert-box">
                <div class="alert-box-header">
                    <h4>${a.title}</h4>
                    <span class="alert-badge ${badgeClass}">${badgeText}</span>
                </div>
                <p>${a.message}</p>
                ${btnHtml}
            </div>
        `;
    });
}
