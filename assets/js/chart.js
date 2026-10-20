// =====================================================
// CHART.JS - WORKFORCE ANALYTICS
// For LGU Admin Dashboard
// =====================================================

// Monthly Job Trends Chart
function initJobTrendsChart(canvasId, labels, data) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Job Postings',
                data: data,
                borderColor: '#06b6d4',
                backgroundColor: 'rgba(6,182,212,0.1)',
                borderWidth: 3,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#ffffff',
                pointRadius: 5,
                pointHoverRadius: 7,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: '#ffffff', font: { size: 12 } }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#ffffff',
                    bodyColor: '#94a3b8',
                    borderColor: '#3b82f6',
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    ticks: { color: '#94a3b8' }
                },
                x: {
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    ticks: { color: '#94a3b8' }
                }
            }
        }
    });
}

// Top Categories Bar Chart
function initCategoriesChart(canvasId, labels, data) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Number of Jobs',
                data: data,
                backgroundColor: 'rgba(59,130,246,0.7)',
                borderColor: '#3b82f6',
                borderWidth: 1,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: '#ffffff', font: { size: 12 } }
                }
            },
            scales: {
                y: {
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    ticks: { color: '#94a3b8', stepSize: 1 }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8' }
                }
            }
        }
    });
}

// Skill Demand Chart
function initSkillsChart(canvasId, labels, data) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    return new Chart(ctx, {
        type: 'horizontalBar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Demand Count',
                data: data,
                backgroundColor: 'rgba(6,182,212,0.7)',
                borderColor: '#06b6d4',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: { labels: { color: '#ffffff' } }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    ticks: { color: '#94a3b8', stepSize: 1 }
                },
                y: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8' }
                }
            }
        }
    });
}

// Job Status Donut Chart
function initStatusChart(canvasId, data) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Approved', 'Pending', 'Rejected', 'Closed'],
            datasets: [{
                data: data,
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#64748b'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#ffffff', font: { size: 12 } }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#ffffff',
                    bodyColor: '#94a3b8'
                }
            }
        }
    });
}