// summary.js - Chart.js implementations

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
    
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded');
        return;
    }

    // Common Chart Options for Dark Mode
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: '#94a3b8', // slate-400
                    font: { family: 'Poppins' }
                }
            },
            tooltip: {
                backgroundColor: '#1e293b',
                titleColor: '#f8fafc',
                bodyColor: '#cbd5e1',
                borderColor: '#334155',
                borderWidth: 1,
                padding: 12,
                cornerRadius: 8,
                displayColors: true
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#334155' }, // slate-700
                ticks: { color: '#94a3b8', font: { family: 'Poppins' } }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#94a3b8', font: { family: 'Poppins' } }
            }
        }
    };

    // ----------------------------
    // XP History Chart (Bar)
    // ----------------------------
    const ctxXp = document.getElementById('xpChart');
    if (ctxXp) {
        const m = window.SUMMARY_DATA?.months || [];
        const mx = window.SUMMARY_DATA?.monthXp || [];
        const mg = window.SUMMARY_DATA?.monthGoal || [];

        new Chart(ctxXp.getContext('2d'), {
            type: 'bar',
            data: {
                labels: m,
                datasets: [
                    {
                        label: 'XP Earned',
                        data: mx,
                        backgroundColor: '#6366f1', // Primary
                        borderRadius: 6,
                        barThickness: 20
                    },
                    {
                        label: 'Goal',
                        data: mg,
                        type: 'line',
                        borderColor: '#ec4899', // Secondary
                        borderDash: [5, 5],
                        pointRadius: 0,
                        borderWidth: 2,
                        fill: false
                    }
                ]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    title: { display: false }
                }
            }
        });
    }

    // ----------------------------
    // Category Trends Chart (Line)
    // ----------------------------
    const ctxCat = document.getElementById('categoryChart');
    if (ctxCat) {
        const cs = window.SUMMARY_DATA?.categorySeries || [];
        
        // Color Palette
        const colors = [
            '#6366f1', // Indigo
            '#ec4899', // Pink
            '#06b6d4', // Cyan
            '#8b5cf6', // Violet
            '#10b981', // Emerald
            '#f59e0b'  // Amber
        ];

        const datasets = cs.map((s, idx) => ({
            label: s.label,
            data: s.data,
            borderColor: colors[idx % colors.length],
            backgroundColor: colors[idx % colors.length] + '20', // 20% opacity
            tension: 0.4,
            borderWidth: 2,
            pointBackgroundColor: '#1e293b',
            pointBorderColor: colors[idx % colors.length],
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true
        }));

        new Chart(ctxCat.getContext('2d'), {
            type: 'line',
            data: {
                labels: window.SUMMARY_DATA?.months || [],
                datasets: datasets
            },
            options: commonOptions
        });
    }
});
