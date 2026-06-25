// categories.js

let allocationMixChart = null;

document.addEventListener('DOMContentLoaded', () => {
    fetchCategories();
    fetchMetrics();

    // Modal logic
    const modal = document.getElementById('categoryModal');
    document.querySelector('.close-modal').onclick = () => closeModal();
    document.querySelector('.cancel-modal').onclick = () => closeModal();
    window.onclick = (event) => { if (event.target == modal) closeModal(); };
    document.getElementById('categoryForm').addEventListener('submit', handleFormSubmit);
});

// Utilities
const formatCurrency = (val) => parseFloat(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

const getCategoryStyles = (name) => {
    const lower = name.toLowerCase();
    if (lower.includes('cater') || lower.includes('food')) return { color: 'purple', icon: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 2v20M7 2v20a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V2M3 2v20"/></svg>' };
    if (lower.includes('venue') || lower.includes('location')) return { color: 'blue', icon: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>' };
    if (lower.includes('decor') || lower.includes('flower')) return { color: 'teal', icon: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="8"/><line x1="12" y1="16" x2="12" y2="22"/><line x1="2" y1="12" x2="8" y2="12"/><line x1="16" y1="12" x2="22" y2="12"/></svg>' };
    if (lower.includes('music') || lower.includes('entertain')) return { color: 'orange', icon: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>' };
    if (lower.includes('photo') || lower.includes('video')) return { color: 'gray', icon: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>' };
    return { color: 'blue', icon: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>' };
};

let allCategories = [];

async function fetchCategories() {
    try {
        const response = await fetch('/smart-planner/api/categories.php?action=list');
        const result = await response.json();
        
        if (result.status === 'success') {
            allCategories = result.data;
            renderCategories();
        }
    } catch (err) {
        console.error(err);
    }
}

function renderCategories() {
    const container = document.getElementById('categories-container');
    container.innerHTML = '';
    
    allCategories.forEach(cat => {
        const style = getCategoryStyles(cat.category_name);
        
        let statusClass = 'status-safe';
        let progressLabel = `${cat.utilization}%`;
        let overBudgetHtml = '';
        
        if (cat.total_spent > cat.allocated_amount && cat.allocated_amount > 0) {
            statusClass = 'status-danger';
            progressLabel = `${cat.utilization}%`;
            overBudgetHtml = `<span style="color:var(--text-red); font-weight:600; display:flex; align-items:center; gap:4px; font-size:0.75rem;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Over Budget
            </span>`;
        } else if (cat.utilization > 85) {
            statusClass = 'status-warning';
        }
        
        const isNegative = cat.remaining < 0;
        const remClass = isNegative ? 'negative' : 'positive';
        
        const card = document.createElement('div');
        card.className = 'cat-card';
        card.innerHTML = `
            <div class="cat-actions dropdown" style="position:absolute; right:1rem; top:1rem;">
                <button class="icon-btn" onclick="openModal(${cat.id})" style="border:none;background:transparent;cursor:pointer;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><polygon points="16 3 21 8 8 21 3 21 3 16 16 3"></polygon></svg>
                </button>
            </div>
            
            <div class="cat-header">
                <div class="cat-icon ${style.color}">${style.icon}</div>
                <div class="cat-title-area">
                    <h3>${cat.category_name}</h3>
                    <span class="cat-badge">${cat.suggested_percentage}% Allocation</span>
                </div>
            </div>
            
            <div class="cat-stats">
                <div class="stat-col">
                    <span class="stat-label">Budget Cap</span>
                    <span class="stat-val">${formatCurrency(cat.allocated_amount)}</span>
                </div>
                <div class="stat-col right">
                    <span class="stat-label">Spent</span>
                    <span class="stat-val" style="${isNegative ? 'color:var(--text-red);' : ''}">${formatCurrency(cat.total_spent)}</span>
                </div>
            </div>
            
            <div class="cat-progress-wrapper ${statusClass}">
                <div class="progress-header">
                    ${overBudgetHtml || '<span>Utilization</span>'}
                    <span class="progress-label">${progressLabel}</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-fill" style="width: ${Math.min(cat.utilization, 100)}%;"></div>
                </div>
            </div>
            
            <div class="cat-remaining">
                <span class="rem-label">Remaining</span>
                <span class="rem-val ${remClass}">${isNegative ? '' : ''}${formatCurrency(cat.remaining)}</span>
            </div>
        `;
        container.appendChild(card);
    });
    
    // Add New Category Card
    const newCard = document.createElement('div');
    newCard.className = 'cat-card cat-card-new';
    newCard.onclick = () => openModal();
    newCard.innerHTML = `
        <div class="new-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        </div>
        <h3>New Category</h3>
        <p>Define a new department and allocate event funds.</p>
    `;
    container.appendChild(newCard);
}

// Charts & Insights
async function fetchMetrics() {
    try {
        const response = await fetch('/smart-planner/api/categories.php?action=metrics');
        const result = await response.json();
        
        if (result.status === 'success') {
            renderChart(result.data.chart);
            renderInsights(result.data.insights);
        }
    } catch (err) {
        console.error(err);
    }
}

function renderChart(data) {
    if (typeof Chart === 'undefined') return;
    
    const ctx = document.getElementById('allocationMixChart').getContext('2d');
    
    const labels = data.map(d => d.category_name);
    const spentData = data.map(d => parseFloat(d.total_spent));
    // For stacked bar, planned is allocated - spent (if allocated > spent), but if spent > allocated, planned = 0
    const plannedData = data.map(d => {
        let p = parseFloat(d.allocated_amount) - parseFloat(d.total_spent);
        return p > 0 ? p : 0;
    });

    if (allocationMixChart) {
        allocationMixChart.destroy();
    }
    
    allocationMixChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Spent',
                    data: spentData,
                    backgroundColor: '#4f46e5',
                    borderSkipped: false,
                },
                {
                    label: 'Planned (Remaining)',
                    data: plannedData,
                    backgroundColor: '#dbeafe',
                    borderSkipped: false,
                    borderRadius: {topLeft: 4, topRight: 4}
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { stacked: true, grid: { display: false } },
                y: { stacked: true, display: false, beginAtZero: true }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += formatCurrency(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
}

function renderInsights(insights) {
    const container = document.getElementById('insights-container');
    container.innerHTML = '';
    
    if (!insights || insights.length === 0) {
        container.innerHTML = '<p style="color:#9ca3af; font-size:0.875rem;">No actionable insights at this time.</p>';
        return;
    }
    
    insights.forEach(item => {
        const iconSvg = item.type === 'overflow' 
            ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>'
            : item.type === 'savings'
            ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 17 22 17 22 11"/></svg>'
            : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
            
        const iconClass = item.type === 'overflow' ? 'danger' : item.type === 'savings' ? 'success' : 'info';
        
        const div = document.createElement('div');
        div.className = 'insight-item';
        div.innerHTML = `
            <div class="insight-icon ${iconClass}">${iconSvg}</div>
            <div class="insight-text">
                <h4>${item.title}</h4>
                <p>${item.message}</p>
            </div>
        `;
        container.appendChild(div);
    });
}


// Modal Logic
function openModal(id = null) {
    const modal = document.getElementById('categoryModal');
    const form  = document.getElementById('categoryForm');
    form.reset();
    
    if (id) {
        const cat = allCategories.find(c => c.id == id);
        if (cat) {
            document.getElementById('modal-title').innerText = 'Edit Category';
            document.getElementById('category_id').value = cat.id;
            document.getElementById('category_name').value = cat.category_name;
            document.getElementById('allocated_amount').value = cat.allocated_amount;
            document.getElementById('suggested_percentage').value = cat.suggested_percentage;
            document.getElementById('notes').value = cat.notes || '';
        }
    } else {
        document.getElementById('modal-title').innerText = 'New Category';
        document.getElementById('category_id').value = '';
    }
    
    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('categoryModal').classList.remove('active');
    document.getElementById('categoryForm').reset();
}

async function handleFormSubmit(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    
    try {
        const response = await fetch('/smart-planner/api/categories.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        
        if (result.status === 'success') {
            closeModal();
            await fetchCategories();
            await fetchMetrics();
        } else {
            alert('Error: ' + result.message);
        }
    } catch(err) {
        console.error(err);
        alert('An error occurred.');
    }
}
