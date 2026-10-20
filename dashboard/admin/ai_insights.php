<?php
$page_title = "AI Workforce Insights";
require_once '../../includes/config.php';
require_once '../../includes/ai_recommendations.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'lgu_admin') {
    redirect(SITE_URL . 'pages/lgu-login.php', 'Unauthorized access.', 'error');
}

$db = getDB();
$force_refresh = isset($_GET['refresh']) ? true : false;

// Get AI-generated insights
$insights = getAIWorkforceInsights($force_refresh);

// Get real-time stats for context
$stats = [
    'total_jobs' => $db->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'approved'")->fetch_assoc()['count'],
    'total_employers' => $db->query("SELECT COUNT(*) as count FROM employer_profiles WHERE verification_status = 'verified'")->fetch_assoc()['count'],
    'total_jobseekers' => $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'jobseeker'")->fetch_assoc()['count'],
    'total_applications' => $db->query("SELECT COUNT(*) as count FROM applications")->fetch_assoc()['count'],
    'total_hires' => $db->query("SELECT COUNT(*) as count FROM applications WHERE status = 'accepted'")->fetch_assoc()['count'],
    'active_jobs_30d' => $db->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'approved' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['count'],
    'new_jobseekers_30d' => $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'jobseeker' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['count']
];

// Get skill demand data for charts
$skill_demand = $db->query("SELECT required_skills FROM jobs WHERE status = 'approved' AND required_skills IS NOT NULL");
$skill_counts = [];
foreach ($skill_demand->fetch_all(MYSQLI_ASSOC) as $row) {
    $skills = explode(',', $row['required_skills']);
    foreach ($skills as $skill) {
        $skill = trim($skill);
        if ($skill) $skill_counts[$skill] = ($skill_counts[$skill] ?? 0) + 1;
    }
}
arsort($skill_counts);
$top_skills = array_slice($skill_counts, 0, 10, true);

// Get application trends by month
$monthly_trends = $db->query("SELECT 
                               DATE_FORMAT(created_at, '%Y-%m') as month,
                               COUNT(*) as applications
                               FROM applications 
                               WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                               GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                               ORDER BY month ASC")->fetch_all(MYSQLI_ASSOC);

// Get category distribution
$categories = $db->query("SELECT c.name, COUNT(j.id) as job_count 
                          FROM job_categories c 
                          LEFT JOIN jobs j ON c.id = j.category_id AND j.status = 'approved'
                          GROUP BY c.id 
                          ORDER BY job_count DESC 
                          LIMIT 6")->fetch_all(MYSQLI_ASSOC);

require_once '../../includes/header-dashboard.php';
?>

<style>
    .insight-header {
        background: linear-gradient(135deg, rgba(37,99,235,0.1), rgba(6,182,212,0.05));
        border-radius: 32px;
        padding: 32px;
        margin-bottom: 30px;
        text-align: center;
    }
    
    .ai-badge-large {
        display: inline-block;
        background: linear-gradient(135deg, #8b5cf6, #06b6d4);
        border-radius: 30px;
        padding: 8px 24px;
        font-size: 14px;
        margin-bottom: 20px;
    }
    
    .ai-summary {
        background: rgba(255,255,255,0.05);
        border-radius: 24px;
        padding: 28px;
        margin-bottom: 30px;
        border-left: 4px solid #06b6d4;
    }
    
    .insight-card-ai {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 24px;
        padding: 24px;
        margin-bottom: 20px;
        transition: all 0.3s;
    }
    
    .insight-card-ai:hover {
        transform: translateY(-3px);
        background: rgba(37,99,235,0.05);
    }
    
    .insight-icon {
        width: 48px;
        height: 48px;
        background: rgba(6,182,212,0.15);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
    }
    
    .insight-icon i {
        font-size: 24px;
        color: #06b6d4;
    }
    
    .recommendation-card {
        background: linear-gradient(135deg, rgba(6,182,212,0.08), rgba(37,99,235,0.04));
        border: 1px solid rgba(6,182,212,0.2);
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 16px;
    }
    
    .stats-grid-ai {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .stat-card-ai {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        padding: 20px;
        text-align: center;
    }
    
    .stat-number-ai {
        font-size: 32px;
        font-weight: 700;
        color: #06b6d4;
    }
    
    .refresh-btn {
        float: right;
        margin-top: -50px;
    }
    
    .chart-container-ai {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 24px;
        padding: 24px;
        margin-top: 30px;
    }
    
    .skill-bar {
        margin: 12px 0;
    }
    
    .skill-name {
        display: inline-block;
        width: 120px;
        font-size: 13px;
    }
    
    .skill-bar-fill {
        display: inline-block;
        height: 8px;
        background: linear-gradient(90deg, #2563eb, #06b6d4);
        border-radius: 4px;
        vertical-align: middle;
    }
    
    @media (max-width: 768px) {
        .stats-grid-ai {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .insight-header {
            padding: 20px;
        }
        .refresh-btn {
            float: none;
            margin-top: 16px;
            display: inline-block;
        }
        .skill-name {
            width: 100px;
            font-size: 11px;
        }
    }
    .dashboard-main {
        margin-left: -60px !important;
        padding-left: 200px !important;
    }
</style>

<div class="dashboard-main">
    <div style="margin-bottom: 24px;">
        <h1>AI Workforce Insights</h1>
        <p class="text-muted-light">
            <i class="fas fa-robot"></i> AI-powered analysis of JobLinkLocal employment ecosystem
        </p>
        <a href="?refresh=1" class="refresh-btn btn-outline-light" onclick="return confirm('Refresh AI insights? This may take a few seconds.');">
            <i class="fas fa-sync-alt"></i> Refresh Insights
        </a>
    </div>
    
    <!-- AI Header -->
    <div class="insight-header">
        <div class="ai-badge-large">
            <i class="fas fa-brain"></i> AI-Generated Analysis
        </div>
        <h2 style="font-size: 28px; margin-bottom: 16px;">Workforce Intelligence Report</h2>
        <p class="text-muted-light">Generated on <?php echo date('F d, Y \a\t h:i A'); ?></p>
    </div>
    
    <!-- AI Summary -->
    <div class="ai-summary">
        <i class="fas fa-quote-left" style="font-size: 32px; color: #06b6d4; opacity: 0.5; margin-bottom: 16px; display: block;"></i>
        <p style="font-size: 18px; line-height: 1.6;">
            <?php echo htmlspecialchars($insights['summary'] ?? 'Based on current platform data, the local job market shows promising growth opportunities.'); ?>
        </p>
    </div>
    
    <!-- Stats Overview -->
    <div class="stats-grid-ai">
        <div class="stat-card-ai">
            <div class="stat-number-ai"><?php echo number_format($stats['total_jobs']); ?></div>
            <div class="text-muted-light">Active Jobs</div>
            <div class="text-dim" style="font-size: 12px;">+<?php echo $stats['active_jobs_30d']; ?> in 30 days</div>
        </div>
        <div class="stat-card-ai">
            <div class="stat-number-ai"><?php echo number_format($stats['total_jobseekers']); ?></div>
            <div class="text-muted-light">Job Seekers</div>
            <div class="text-dim" style="font-size: 12px;">+<?php echo $stats['new_jobseekers_30d']; ?> new</div>
        </div>
        <div class="stat-card-ai">
            <div class="stat-number-ai"><?php echo number_format($stats['total_applications']); ?></div>
            <div class="text-muted-light">Applications</div>
            <div class="text-dim" style="font-size: 12px;">Hiring rate: <?php echo $stats['total_applications'] > 0 ? round(($stats['total_hires'] / $stats['total_applications']) * 100, 1) : 0; ?>%</div>
        </div>
        <div class="stat-card-ai">
            <div class="stat-number-ai"><?php echo number_format($stats['total_hires']); ?></div>
            <div class="text-muted-light">Successful Hires</div>
            <div class="text-dim" style="font-size: 12px;">Jobs that placed candidates</div>
        </div>
    </div>
    
    <!-- AI Insights Cards -->
    <h3 style="margin-bottom: 20px;"><i class="fas fa-lightbulb"></i> Key Insights</h3>
    
    <?php if (!empty($insights['insights'])): ?>
        <?php foreach ($insights['insights'] as $insight): ?>
            <div class="insight-card-ai">
                <div class="insight-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <p style="font-size: 16px; line-height: 1.5;"><?php echo htmlspecialchars($insight); ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="insight-card-ai">
            <div class="insight-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <p style="font-size: 16px; line-height: 1.5;">The local job market is growing steadily with <?php echo number_format($stats['total_jobs']); ?> active positions.</p>
        </div>
        <div class="insight-card-ai">
            <div class="insight-icon">
                <i class="fas fa-tags"></i>
            </div>
            <p style="font-size: 16px; line-height: 1.5;">Top skills in demand: <?php echo implode(', ', array_keys(array_slice($top_skills, 0, 3))); ?>.</p>
        </div>
        <div class="insight-card-ai">
            <div class="insight-icon">
                <i class="fas fa-chart-simple"></i>
            </div>
            <p style="font-size: 16px; line-height: 1.5;">Application-to-hire ratio is <?php echo $stats['total_applications'] > 0 ? round(($stats['total_hires'] / $stats['total_applications']) * 100, 1) : 0; ?>%.</p>
        </div>
    <?php endif; ?>
    
    <!-- AI Recommendations -->
    <h3 style="margin: 30px 0 20px;"><i class="fas fa-clipboard-list"></i> Actionable Recommendations</h3>
    
    <?php if (!empty($insights['recommendations'])): ?>
        <?php foreach ($insights['recommendations'] as $recommendation): ?>
            <div class="recommendation-card">
                <i class="fas fa-check-circle" style="color: #10b981; margin-right: 12px;"></i>
                <span><?php echo htmlspecialchars($recommendation); ?></span>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="recommendation-card">
            <i class="fas fa-check-circle" style="color: #10b981; margin-right: 12px;"></i>
            <span>Encourage more employers to post jobs in high-demand sectors.</span>
        </div>
        <div class="recommendation-card">
            <i class="fas fa-check-circle" style="color: #10b981; margin-right: 12px;"></i>
            <span>Partner with local training institutions for upskilling programs.</span>
        </div>
        <div class="recommendation-card">
            <i class="fas fa-check-circle" style="color: #10b981; margin-right: 12px;"></i>
            <span>Host job fairs to connect job seekers with top employers.</span>
        </div>
    <?php endif; ?>
    
    <!-- Top Skills Chart -->
    <div class="chart-container-ai">
        <h3><i class="fas fa-tags"></i> Top In-Demand Skills</h3>
        <?php foreach (array_slice($top_skills, 0, 8, true) as $skill => $count): 
            $max = max($top_skills);
            $width = ($count / $max) * 100;
        ?>
            <div class="skill-bar">
                <span class="skill-name"><?php echo htmlspecialchars($skill); ?></span>
                <div style="display: inline-block; width: calc(100% - 130px);">
                    <div class="skill-bar-fill" style="width: <?php echo $width; ?>%;"></div>
                </div>
                <span style="float: right; font-size: 12px;"><?php echo $count; ?> jobs</span>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Application Trends Chart -->
    <div class="chart-container-ai">
        <h3><i class="fas fa-chart-line"></i> Application Trends (Last 6 Months)</h3>
        <canvas id="trendsChart" style="height: 300px; width: 100%;"></canvas>
    </div>
    
    <!-- Category Distribution Chart -->
    <div class="chart-container-ai">
        <h3><i class="fas fa-chart-pie"></i> Jobs by Category</h3>
        <canvas id="categoryChart" style="height: 300px; width: 100%;"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Trends Chart
    const trendsData = <?php 
        $months = [];
        $applications = [];
        foreach ($monthly_trends as $t) {
            $months[] = date('M Y', strtotime($t['month'] . '-01'));
            $applications[] = intval($t['applications']);
        }
        echo json_encode(['months' => $months, 'applications' => $applications]);
    ?>;
    
    const ctx1 = document.getElementById('trendsChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: trendsData.months,
            datasets: [{
                label: 'Applications',
                data: trendsData.applications,
                borderColor: '#06b6d4',
                backgroundColor: 'rgba(6,182,212,0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#ffffff',
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { labels: { color: '#ffffff' } }
            },
            scales: {
                y: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#94a3b8' } },
                x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
            }
        }
    });
    
    // Category Chart
    const categoryData = <?php 
        $cat_names = [];
        $cat_counts = [];
        foreach ($categories as $cat) {
            $cat_names[] = $cat['name'];
            $cat_counts[] = intval($cat['job_count']);
        }
        echo json_encode(['names' => $cat_names, 'counts' => $cat_counts]);
    ?>;
    
    const ctx2 = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: categoryData.names,
            datasets: [{
                label: 'Number of Jobs',
                data: categoryData.counts,
                backgroundColor: 'rgba(59,130,246,0.7)',
                borderColor: '#3b82f6',
                borderWidth: 1,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { labels: { color: '#ffffff' } }
            },
            scales: {
                y: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#94a3b8', stepSize: 1 } },
                x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
            }
        }
    });
</script>

<?php require_once '../../includes/footer-dashboard.php'; ?>