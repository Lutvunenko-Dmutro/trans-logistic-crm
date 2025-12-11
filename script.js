document.addEventListener('DOMContentLoaded', function() {
    // 1. Theme Logic
    const html = document.documentElement;
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    
    // Default to dark if preferred
    const savedTheme = localStorage.getItem('theme') || 'dark';
    html.setAttribute('data-bs-theme', savedTheme);
    updateIcon(savedTheme);

    if(themeToggle) {
        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            html.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateIcon(newTheme);
            updateChart(newTheme);
        });
    }

    function updateIcon(theme) {
        if(themeIcon) {
            themeIcon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
        }
    }

    // 2. Chart Logic
    const ctx = document.getElementById('kpiChart');
    let myChart;

    if (ctx) {
        const labels = JSON.parse(ctx.dataset.labels);
        const counts = JSON.parse(ctx.dataset.counts);

        function initChart(theme) {
            const textColor = theme === 'dark' ? '#adb5bd' : '#495057';
            const borderColor = theme === 'dark' ? '#2b3035' : '#ffffff';
            
            if (myChart) myChart.destroy();

            myChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: counts,
                        backgroundColor: ['#0d6efd', '#198754', '#0dcaf0', '#ffc107', '#dc3545'],
                        borderWidth: 4,
                        borderColor: borderColor
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right', labels: { color: textColor, usePointStyle: true, padding: 20 } }
                    },
                    cutout: '75%'
                }
            });
        }
        initChart(savedTheme);
        window.updateChart = initChart; // Make global for toggle
    }
    
    // 3. Auto-close alerts
    setTimeout(() => {
        let alert = document.querySelector('.alert');
        if (alert) {
            let bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 4000);
});