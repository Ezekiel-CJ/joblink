<?php
$page_title = "Analytics Dashboard";
require_once '../../includes/config.php';
require_once '../../includes/ai_recommendations.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'employer') {
    redirect(SITE_URL . 'login.php', 'Please login to access your dashboard.', 'error');
}

$user_id = $_SESSION['user_id'];
$db = getDB();
$period = isset($_GET['period']) ? $_GET['period'] : '30_days';

$analytics = getEmployerAnalytics($user_id, $period);

// Get jobs with stats
$jobs = $db->query("SELECT j.id, j.title, j.status, j.created_at,
                    COUNT(DISTINCT a.id) as applicants,
                    SUM(CASE WHEN a.status = 'accepted' THEN 1 ELSE 0 END) as hired,
                    SUM(CASE WHEN a.status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted
                    FROM jobs j
                    LEFT JOIN applications a ON j.id = a.job_id
                    WHERE j.employer_id = $user_id
                    GROUP BY j.id
                    ORDER BY j.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$total_applicants = array_sum(array_column($jobs, 'applicants'));
$total_hired = array_sum(array_column($jobs, 'hired'));
$total_shortlisted = array_sum(array_column($jobs, 'shortlisted'));

require_once '../../includes/header-dashboard.php';
?>

<style>
    .analytics-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card-analytics {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        padding: 24px;
        transition: all 0.3s;
    }
    
    .stat-card-analytics:hover {
        transform: translateY(-3px);
        background: rgba(37,99,235,0.05);
    }
    
    .stat-number-analytics {
        font-size: 32px;
        font-weight: 700;
        color: var(--azure-light);
    }
    
    .stat-label-analytics {
        font-size: 13px;
        color: var(--white-muted);
        margin-top: 8px;
    }
    
    .stat-trend-up {
        color: #10b981;
        font-size: 12px;
        margin-left: 8px;
    }
    
    .stat-trend-down {
        color: #ef4444;
        font-size: 12px;
        margin-left: 8px;
    }
    
    .chart-container {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 24px;
        padding: 24px;
        margin-bottom: 30px;
    }
    
    .chart-container h3 {
        margin-bottom: 20px;
        font-size: 18px;
    }
    
    .period-selector {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        justify-content: flex-end;
    }
    
    .period-btn {
        padding: 6px 16px;
        border-radius: 30px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        color: var(--white-muted);
        text-decoration: none;
        font-size: 13px;
        transition: all 0.2s;
    }
    
    .period-btn.active {
        background: linear-gradient(135deg, var(--royal), var(--azure));
        color: white;
    }
    
    .period-btn:hover {
        background: rgba(37,99,235,0.2);
        color: white;
    }
    
    .jobs-table-analytics {
        width: 100%;
        border-collapse: collapse;
    }
    
    .jobs-table-analytics th,
    .jobs-table-analytics td {
        padding: 14px;
        text-align: left;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    
    .jobs-table-analytics th {
        background: rgba(37,99,235,0.1);
        font-weight: 600;
    }
    
    .progress-bar-container {
        width: 100px;
        height: 6px;
        background: rgba(255,255,255,0.1);
        border-radius: 3px;
        overflow: hidden;
    }
    
    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #10b981, #06b6d4);
        border-radius: 3px;
    }
    
    .insight-card {
        background: linear-gradient(135deg, rgba(37,99,235,0.08), rgba(6,182,212,0.04));
        border: 1px solid rgba(37,99,235,0.2);
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .insight-card i {
        color: var(--azure-light);
        margin-right: 10px;
    }
    
    @media (max-width: 768px) {
        .analytics-stats {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .stat-number-analytics {
            font-size: 24px;
        }
        .jobs-table-analytics {
            font-size: 12px;
        }
        .jobs-table-analytics th,
        .jobs-table-analytics td {
            padding: 8px;
        }
    }
    .dashboard-main {
        margin-left: -60px !important;
        padding-left: 200px !important;
    }
</style>

<div class="dashboard-main">
    <div style="margin-bottom: 24px;">
        <h1>Analytics Dashboard</h1>
        <p class="text-muted-light">Track your hiring performance and job posting metrics.</p>
    </div>
    
    <!-- Period Selector -->
    <div class="period-selector">
        <a href="?period=7_days" class="period-btn <?php echo $period == '7_days' ? 'active' : ''; ?>">Last 7 Days</a>
        <a href="?period=30_days" class="period-btn <?php echo $period == '30_days' ? 'active' : ''; ?>">Last 30 Days</a>
        <a href="?period=90_days" class="period-btn <?php echo $period == '90_days' ? 'active' : ''; ?>">Last 90 Days</a>
    </div>
    
    <!-- Stats Cards -->
    <div class="analytics-stats">
        <div class="stat-card-analytics">
            <i class="fas fa-eye" style="font-size: 24px; color: var(--azure-light);"></i>
            <div class="stat-number-analytics"><?php echo number_format($analytics['totals']['total_views'] ?? 0); ?></div>
            <div class="stat-label-analytics">Total Job Views</div>
        </div>
        <div class="stat-card-analytics">
            <i class="fas fa-users" style="font-size: 24px; color: var(--azure-light);"></i>
            <div class="stat-number-analytics"><?php echo number_format($analytics['totals']['total_applications'] ?? 0); ?></div>
            <div class="stat-label-analytics">Total Applications</div>
        </div>
        <div class="stat-card-analytics">
            <i class="fas fa-star" style="font-size: 24px; color: var(--azure-light);"></i>
            <div class="stat-number-analytics"><?php echo number_format($analytics['totals']['total_shortlists'] ?? 0); ?></div>
            <div class="stat-label-analytics">Shortlisted</div>
        </div>
        <div class="stat-card-analytics">
            <i class="fas fa-user-check" style="font-size: 24px; color: var(--azure-light);"></i>
            <div class="stat-number-analytics"><?php echo number_format($analytics['totals']['total_hires'] ?? 0); ?></div>
            <div class="stat-label-analytics">Total Hires</div>
        </div>
    </div>
    
    <!-- Conversion Rates -->
    <div class="analytics-stats">
        <div class="stat-card-analytics">
            <i class="fas fa-chart-line" style="font-size: 24px; color: var(--azure-light);"></i>
            <div class="stat-number-analytics"><?php echo $analytics['conversion_rate']; ?>%</div>
            <div class="stat-label-analytics">View to Application Rate</div>
        </div>
        <div class="stat-card-analytics">
            <i class="fas fa-chart-simple" style="font-size: 24px; color: var(--azure-light);"></i>
            <div class="stat-number-analytics"><?php echo $analytics['hire_rate']; ?>%</div>
            <div class="stat-label-analytics">Application to Hire Rate</div>
        </div>
        <div class="stat-card-analytics">
            <i class="fas fa-briefcase" style="font-size: 24px; color: var(--azure-light);"></i>
            <div class="stat-number-analytics"><?php echo count($jobs); ?></div>
            <div class="stat-label-analytics">Total Job Postings</div>
        </div>
        <div class="stat-card-analytics">
            <i class="fas fa-chart-bar" style="font-size: 24px; color: var(--azure-light);"></i>
            <div class="stat-number-analytics"><?php echo $analytics['totals']['jobs_with_applications'] ?? 0; ?></div>
            <div class="stat-label-analytics">Jobs with Applications</div>
        </div>
    </div>
    
    <!-- Chart -->
    <div class="chart-container">
        <h3><i class="fas fa-chart-line"></i> Daily Trends</h3>
        <canvas id="trendsChart" style="height: 300px; width: 100%;"></canvas>
    </div>
    
    <!-- Jobs Performance Table -->
    <h3 style="margin-bottom: 20px;">Job Performance</h3>
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; overflow-x: auto;">
        <table class="jobs-table-analytics">
            <thead>
                <tr>
                    <th>Job Title</th>
                    <th>Posted Date</th>
                    <th>Applicants</th>
                    <th>Shortlisted</th>
                    <th>Hired</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($jobs)): ?>
                    <?php foreach ($jobs as $job): 
                        $applicant_count = $job['applicants'] ?? 0;
                        $shortlisted = $job['shortlisted'] ?? 0;
                        $hired = $job['hired'] ?? 0;
                        $shortlist_rate = $applicant_count > 0 ? round(($shortlisted / $applicant_count) * 100) : 0;
                        $hire_rate = $shortlisted > 0 ? round(($hired / $shortlisted) * 100) : 0;
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($job['title']); ?></strong></td>
                            <td><?php echo date('M d, Y', strtotime($job['created_at'])); ?></td>
                            <td>
                                <?php echo $applicant_count; ?>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill" style="width: <?php echo min(100, $applicant_count * 2); ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <?php echo $shortlisted; ?> (<?php echo $shortlist_rate; ?>%)
                            </td>
                            <td>
                                <?php echo $hired; ?> (<?php echo $hire_rate; ?>%)
                            </td>
                            <td><?php echo getStatusBadge($job['status']); ?></td>
                            <td>
                                <a href="applicants.php?job_id=<?php echo $job['id']; ?>" class="btn-outline-light" style="padding: 4px 12px; font-size: 12px;">
                                    <i class="fas fa-users"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <i class="fas fa-briefcase" style="font-size: 48px; color: var(--white-dim); margin-bottom: 16px; display: block;"></i>
                            <p class="text-muted-light">No jobs posted yet.</p>
                            <a href="post-job.php" class="btn-primary-gradient" style="margin-top: 16px; display: inline-block;">Post a Job</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Trends Chart
    const trendsData = <?php 
        $trends = array_reverse($analytics['trends']);
        $dates = [];
        $views = [];
        $applications = [];
        foreach ($trends as $t) {
            $dates[] = date('M d', strtotime($t['date']));
            $views[] = intval($t['views']);
            $applications[] = intval($t['applications']);
        }
        echo json_encode(['dates' => $dates, 'views' => $views, 'applications' => $applications]);
    ?>;
    
    const ctx = document.getElementById('trendsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: trendsData.dates,
            datasets: [
                {
                    label: 'Job Views',
                    data: trendsData.views,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Applications',
                    data: trendsData.applications,
                    borderColor: '#06b6d4',
                    backgroundColor: 'rgba(6,182,212,0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    labels: { color: '#ffffff' }
                }
            },
            scales: {
                y: {
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    ticks: { color: '#94a3b8' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8' }
                }
            }
        }
    });
</script>

<?php require_once '../../includes/footer-dashboard.php'; ?>