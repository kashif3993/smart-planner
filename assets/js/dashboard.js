// assets/js/dashboard.js

document.addEventListener('DOMContentLoaded', () => {
    


    // 2. Render Spending Chart
    const chartContainer = document.getElementById('spending-chart-container');
    if (chartContainer) {
        const data = [
            { month: 'JAN', value: 40, color: '#4f46e5' },
            { month: 'FEB', value: 65, color: '#7c3aed' },
            { month: 'MAR', value: 30, color: '#0ea5e9' },
            { month: 'APR', value: 85, color: '#4338ca' },
            { month: 'MAY', value: 50, color: '#6366f1' },
            { month: 'JUN', value: 45, color: '#38bdf8' }
        ];

        data.forEach(item => {
            const wrap = document.createElement('div');
            wrap.className = 'bar-wrap';
            
            const bar = document.createElement('div');
            bar.className = 'bar';
            bar.style.backgroundColor = item.color;
            // Animate height after a short delay
            setTimeout(() => {
                bar.style.height = `${item.value}%`;
            }, 100);

            const label = document.createElement('span');
            label.className = 'bar-label';
            label.textContent = item.month;

            wrap.appendChild(bar);
            wrap.appendChild(label);
            chartContainer.appendChild(wrap);
        });
    }

    // 3. Animate Progress Ring
    const ringFill = document.querySelector('.ring-fill');
    if (ringFill) {
        const radius = ringFill.r.baseVal.value;
        const circumference = radius * 2 * Math.PI;
        
        ringFill.style.strokeDasharray = `${circumference} ${circumference}`;
        ringFill.style.strokeDashoffset = `${circumference}`;

        function setProgress(percent) {
            const offset = circumference - percent / 100 * circumference;
            ringFill.style.strokeDashoffset = offset;
        }

        // Read progress from data attribute
        const progressCard = document.querySelector('.global-progress');
        const targetProgress = progressCard ? parseInt(progressCard.getAttribute('data-progress') || 0) : 0;

        // Animate to target progress
        setTimeout(() => {
            setProgress(targetProgress);
        }, 300);
    }

});
