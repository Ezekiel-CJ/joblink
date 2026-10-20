<?php
$page_title = "Top Rated Jobs";
require_once '../../includes/config.php';
require_once '../../includes/ai_recommendations.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'lgu_admin') {
    redirect(SITE_URL . 'pages/lgu-login.php', 'Unauthorized access.', 'error');
}

$db = getDB();
$period = isset($_GET['period']) ? $_GET['period'] : 'all';

// Build query based on period
$date_filter = "";
if ($period == 'week') {
    $date_filter = "AND j.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($period == 'month') {
    $date_filter = "AND j.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($period == 'quarter') {
    $date_filter = "AND j.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
}

// Get top rated jobs with rankings
$top_jobs = $db->query("SELECT j.*, 
                         ep.business_name,
                         c.name as category_name,
                         COALESCE(AVG(jr.rating), 0) as avg_rating,
                         COUNT(DISTINCT jr.id) as review_count,
                         COUNT(DISTINCT a.id) as applicant_count,
                         SUM(CASE WHEN a.status = 'accepted' THEN 1 ELSE 0 END) as hired_count
                         FROM jobs j
                         LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
                         LEFT JOIN job_categories c ON j.category_id = c.id
                         LEFT JOIN job_ratings jr ON j.id = jr.job_id
                         LEFT JOIN applications a ON j.id = a.job_id
                         WHERE j.status = 'approved' $date_filter
                         GROUP BY j.id
                         HAVING avg_rating > 0 OR applicant_count > 0
                         ORDER BY avg_rating DESC, applicant_count DESC
                         LIMIT 20")->fetch_all(MYSQLI_ASSOC);

// Get overall stats
$stats = $db->query("SELECT 
                     COUNT(DISTINCT j.id) as total_jobs_rated,
                     COALESCE(AVG(jr.rating), 0) as average_rating_overall,
                     COUNT(jr.id) as total_reviews
                     FROM jobs j
                     LEFT JOIN job_ratings jr ON j.id = jr.job_id
                     WHERE j.status = 'approved'")->fetch_assoc();

// Get top categories by rating
$top_categories = $db->query("SELECT c.name, 
                              COALESCE(AVG(jr.rating), 0) as avg_rating,
                              COUNT(j.id) as job_count
                              FROM job_categories c
                              LEFT JOIN jobs j ON c.id = j.category_id AND j.status = 'approved'
                              LEFT JOIN job_ratings jr ON j.id = jr.job_id
                              GROUP BY c.id
                              HAVING job_count > 0
                              ORDER BY avg_rating DESC
                              LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Get top employers by rating
$top_employers = $db->query("SELECT ep.business_name, 
                             ep.user_id,
                             COALESCE(AVG(jr.rating), 0) as avg_rating,
                             COUNT(DISTINCT j.id) as job_count,
                             SUM(CASE WHEN a.status = 'accepted' THEN 1 ELSE 0 END) as total_hires
                             FROM employer_profiles ep
                             JOIN users u ON ep.user_id = u.id
                             LEFT JOIN jobs j ON u.id = j.employer_id AND j.status = 'approved'
                             LEFT JOIN job_ratings jr ON j.id = jr.job_id
                             LEFT JOIN applications a ON j.id = a.job_id
                             GROUP BY ep.user_id
                             HAVING job_count > 0
                             ORDER BY avg_rating DESC
                             LIMIT 5")->fetch_all(MYSQLI_ASSOC);

require_once '../../includes/header-dashboard.php';
?>

<style>
    .stats-grid-top {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
        margin-bottom: 40px;
    }
    
    .stat-card-top {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 24px;
        padding: 28px;
        text-align: center;
    }
    
    .stat-number-top {
        font-size: 42px;
        font-weight: 800;
        background: linear-gradient(135deg, #ffffff, #22d3ee);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    
    .period-selector {
        display: flex;
        gap: 12px;
        margin-bottom: 30px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }
    
    .period-btn {
        padding: 8px 24px;
        border-radius: 40px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        color: var(--white-muted);
        text-decoration: none;
        transition: all 0.2s;
    }
    
    .period-btn.active {
        background: linear-gradient(135deg, #2563eb, #06b6d4);
        color: white;
    }
    
    .rating-stars {
        display: inline-flex;
        gap: 4px;
        color: #f59e0b;
        font-size: 14px;
    }
    
    .rating-number {
        font-size: 18px;
        font-weight: 700;
        margin-left: 8px;
    }
    
    .job-card-rated {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 20px;
        transition: all 0.3s;
    }
    
    .job-card-rated:hover {
        transform: translateY(-3px);
        background: rgba(37,99,235,0.05);
    }
    
    .rank-badge {
        display: inline-block;
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #f59e0b, #fbbf24);
        border-radius: 12px;
        text-align: center;
        line-height: 32px;
        font-weight: 700;
        color: #1e293b;
        margin-right: 12px;
    }
    
    .rank-badge.gold {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
    }
    
    .rank-badge.silver {
        background: linear-gradient(135deg, #9ca3af, #6b7280);
    }
    
    .rank-badge.bronze {
        background: linear-gradient(135deg, #d97706, #b45309);
    }
    
    .category-table, .employer-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .category-table th, .category-table td,
    .employer-table th, .employer-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    
    .category-table th, .employer-table th {
        background: rgba(37,99,235,0.1);
        font-weight: 600;
    }
    
    .two-columns {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-top: 40px;
    }
    
    .section-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 24px;
        padding: 24px;
    }
    
    @media (max-width: 768px) {
        .stats-grid-top {
            grid-template-columns: 1fr;
            gap: 16px;
        }
        .two-columns {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        .stat-number-top {
            font-size: 32px;
        }
        .job-card-rated {
            padding: 16px;
        }
    }
    .dashboard-main {
        margin-left: -60px !important;
        padding-left: 200px !important;
    }
</style>

<div class="dashboard-main">
    <div style="margin-bottom: 24px;">
        <h1>Top Rated Jobs</h1>
        <p class="text-muted-light">Monitor job quality and employer performance based on ratings and engagement.</p>
    </div>
    
    <!-- Period Selector -->
    <div class="period-selector">
        <a href="?period=week" class="period-btn <?php echo $period == 'week' ? 'active' : ''; ?>">Last 7 Days</a>
        <a href="?period=month" class="period-btn <?php echo $period == 'month' ? 'active' : ''; ?>">Last 30 Days</a>
        <a href="?period=quarter" class="period-btn <?php echo $period == 'quarter' ? 'active' : ''; ?>">Last 90 Days</a>
        <a href="?period=all" class="period-btn <?php echo $period == 'all' ? 'active' : ''; ?>">All Time</a>
    </div>
    
    <!-- Stats -->
    <div class="stats-grid-top">
        <div class="stat-card-top">
            <i class="fas fa-chart-line" style="font-size: 32px; color: var(--azure-light);"></i>
            <div class="stat-number-top"><?php echo number_format($stats['average_rating_overall']); ?></div>
            <div class="text-muted-light">Average Rating (out of 5)</div>
            <div class="rating-stars" style="margin-top: 8px;">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star" style="<?php echo $i <= round($stats['average_rating_overall']) ? 'color: #f59e0b;' : 'color: rgba(255,255,255,0.2);'; ?>"></i>
                <?php endfor; ?>
            </div>
        </div>
        <div class="stat-card-top">
            <i class="fas fa-trophy" style="font-size: 32px; color: var(--azure-light);"></i>
            <div class="stat-number-top"><?php echo number_format($stats['total_jobs_rated']); ?></div>
            <div class="text-muted-light">Jobs with Ratings</div>
        </div>
        <div class="stat-card-top">
            <i class="fas fa-comment-dots" style="font-size: 32px; color: var(--azure-light);"></i>
            <div class="stat-number-top"><?php echo number_format($stats['total_reviews']); ?></div>
            <div class="text-muted-light">Total Reviews</div>
        </div>
    </div>
    
    <!-- Top Rated Jobs List -->
    <h3 style="margin-bottom: 20px;"><i class="fas fa-crown"></i> Top Rated Jobs</h3>
    
    <?php if (!empty($top_jobs)): ?>
        <?php $rank = 1; foreach ($top_jobs as $job): 
            $rank_class = '';
            if ($rank == 1) $rank_class = 'gold';
            elseif ($rank == 2) $rank_class = 'silver';
            elseif ($rank == 3) $rank_class = 'bronze';
        ?>
            <div class="job-card-rated">
                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div style="display: flex; align-items: center;">
                        <span class="rank-badge <?php echo $rank_class; ?>">#<?php echo $rank; ?></span>
                        <div>
                            <h3 style="margin-bottom: 4px;"><?php echo htmlspecialchars($job['title']); ?></h3>
                            <div class="text-muted-light" style="font-size: 13px;">
                                <i class="fas fa-building"></i> <?php echo htmlspecialchars($job['business_name']); ?>
                                <span style="margin: 0 8px">•</span>
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($job['category_name']); ?>
                            </div>
                        </div>
                    </div>
                    <div style="margin-left: auto; text-align: right;">
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star" style="<?php echo $i <= round($job['avg_rating']) ? 'color: #f59e0b;' : 'color: rgba(255,255,255,0.2);'; ?>"></i>
                            <?php endfor; ?>
                            <span class="rating-number"><?php echo number_format($job['avg_rating'], 1); ?></span>
                        </div>
                        <div class="text-dim" style="font-size: 12px;">
                            <?php echo $job['review_count']; ?> reviews • <?php echo $job['applicant_count']; ?> applicants • <?php echo $job['hired_count']; ?> hired
                        </div>
                    </div>
                </div>
                <div style="margin-top: 16px; display: flex; gap: 16px; flex-wrap: wrap;">
                    <div>
                        <span class="salary-tag">₱<?php echo number_format($job['salary_min']); ?> - ₱<?php echo number_format($job['salary_max']); ?></span>
                    </div>
                    <div>
                        <span class="text-muted-light"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location'] ?: 'Remote'); ?></span>
                    </div>
                    <div>
                        <a href="<?php echo SITE_URL; ?>job-details.php?id=<?php echo $job['id']; ?>" target="_blank" class="btn-outline-light" style="padding: 4px 16px; font-size: 12px;">
                            View Job →
                        </a>
                    </div>
                </div>
            </div>
        <?php $rank++; endforeach; ?>
    <?php else: ?>
        <div class="text-center" style="padding: 60px; background: rgba(255,255,255,0.03); border-radius: 20px;">
            <i class="fas fa-star" style="font-size: 48px; color: var(--white-dim);"></i>
            <h3>No ratings yet</h3>
            <p class="text-muted-light">Jobs will appear here once they receive ratings.</p>
        </div>
    <?php endif; ?>
    
    <!-- Two Column Section -->
    <div class="two-columns">
        <!-- Top Categories by Rating -->
        <div class="section-card">
            <h3><i class="fas fa-folder-tree"></i> Top Categories by Rating</h3>
            <table class="category-table">
                <thead>
                    <tr><th>Category</th><th>Jobs</th><th>Avg Rating</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($top_categories as $cat): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                            <td><?php echo $cat['job_count']; ?></td>
                            <td>
                                <div class="rating-stars" style="display: inline-flex; margin-right: 8px;">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star" style="font-size: 12px; <?php echo $i <= round($cat['avg_rating']) ? 'color: #f59e0b;' : 'color: rgba(255,255,255,0.2);'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <?php echo number_format($cat['avg_rating'], 1); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Top Employers by Rating -->
        <div class="section-card">
            <h3><i class="fas fa-building"></i> Top Employers by Rating</h3>
            <table class="employer-table">
                <thead>
                    <tr><th>Employer</th><th>Jobs</th><th>Hires</th><th>Rating</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($top_employers as $emp): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($emp['business_name']); ?></strong></td>
                            <td><?php echo $emp['job_count']; ?></td>
                            <td><?php echo $emp['total_hires']; ?></td>
                            <td>
                                <div class="rating-stars" style="display: inline-flex; margin-right: 8px;">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star" style="font-size: 12px; <?php echo $i <= round($emp['avg_rating']) ? 'color: #f59e0b;' : 'color: rgba(255,255,255,0.2);'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <?php echo number_format($emp['avg_rating'], 1); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($top_employers)): ?>
                        <tr><td colspan="4" style="text-align: center;">No data available</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer-dashboard.php'; ?>