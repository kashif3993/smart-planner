// progress.js
document.addEventListener('DOMContentLoaded', function() {
    // Check if Chart.js is loaded
    if (typeof Chart !== 'undefined') {
        const ctx = document.getElementById('trendChart').getContext('2d');
        
        // Gradient for line chart
        let gradient = ctx.createLinearGradient(0, 0, 0, 180);
        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.2)'); // primary color light
        gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');

        const trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: window.trendLabels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Tasks Due',
                    data: window.trendData || [0, 0, 0, 0, 0, 0, 0],
                    borderColor: '#6366f1',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 10,
                        titleFont: { size: 13 },
                        bodyFont: { size: 13 },
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        display: false,
                        beginAtZero: true
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            color: '#64748b',
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
        
        // Handle filter buttons
        const filterBtns = document.querySelectorAll('.trend-filters button');
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // In a real app, this would fetch new data via AJAX.
                // For now, we'll just show the dynamic 1-week data for all to prove it works dynamically.
                let newData = window.trendData || [0, 0, 0, 0, 0, 0, 0];
                
                trendChart.data.datasets[0].data = newData;
                trendChart.update();
            });
        });
    }

    // FAB functionality (e.g. create task or log activity)
    const fab = document.querySelector('.fab-action');
    if (fab) {
        fab.addEventListener('click', function() {
            window.location.href = '/smart-planner/events/manage.php';
        });
    }

    // Adapt ring chart for mobile resize dynamically
    const updateRingChart = () => {
        const ringFill = document.querySelector('.eff-ring-fill');
        if (ringFill) {
            const width = window.innerWidth;
            let dashArray, progress;
            // Get progress from PHP generated value in DOM or calculate
            const centerText = document.querySelector('.eff-center-text h2');
            progress = centerText ? parseInt(centerText.innerText) : 0;
            
            if (width <= 320) {
                dashArray = 377; // 2 * pi * 60
            } else if (width <= 480) {
                dashArray = 502; // 2 * pi * 80
            } else {
                dashArray = 628; // 2 * pi * 100
            }
            
            ringFill.style.strokeDasharray = dashArray;
            ringFill.style.strokeDashoffset = dashArray - (dashArray * progress / 100);
        }
    };
    
    window.addEventListener('resize', updateRingChart);
    // Initial call
    setTimeout(updateRingChart, 100); // slight delay to ensure CSS applies
});
