<?php
$page_title = "Find Jobs";
require_once 'includes/config.php';

$db = getDB();
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$location = isset($_GET['location']) ? sanitize($_GET['location']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$query = "SELECT j.*, c.name as category_name, u.full_name as employer_name 
          FROM jobs j 
          LEFT JOIN job_categories c ON j.category_id = c.id 
          LEFT JOIN users u ON j.employer_id = u.id 
          WHERE j.status = 'approved'";

if (!empty($search)) {
    $query .= " AND (j.title LIKE '%$search%' OR j.description LIKE '%$search%')";
}
if (!empty($location)) {
    $query .= " AND j.location LIKE '%$location%'";
}
if ($category > 0) {
    $query .= " AND j.category_id = $category";
}

$query .= " ORDER BY j.created_at DESC LIMIT 50";
$jobs = $db->query($query)->fetch_all(MYSQLI_ASSOC);

$categories = $db->query("SELECT * FROM job_categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

require_once 'includes/header.php';
?>

<style>
    .jobs-page { display: grid; grid-template-columns: 280px 1fr; gap: 40px; }
    .filters-sidebar {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 24px;
        padding: 24px;
        position: sticky;
        top: 100px;
        height: fit-content;
    }
    .filter-group { margin-bottom: 24px; }
    .filter-group label { display: block; margin-bottom: 8px; font-weight: 500; }
    .filter-group input, .filter-group select {
        width: 100%;
        padding: 12px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        color: white;
    }
    .job-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 20px;
        transition: all 0.3s;
    }
    .job-card:hover { transform: translateY(-3px); background: rgba(37,99,235,0.05); }
    .salary-tag {
        display: inline-block;
        background: rgba(6,182,212,0.15);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 13px;
    }
    @media (max-width: 768px) { .jobs-page { grid-template-columns: 1fr; } }
    
    /* Category dropdown fix */
.filter-group select,
.job-filters select {
    background-color: #0f172a;
    color: #ffffff;
}

.filter-group select option,
.job-filters select option {
    background-color: #0f172a;
    color: #ffffff;
}
</style>

<div class="container-premium" style="padding: 60px 0;">
    <div class="jobs-page">
        <div class="filters-sidebar">
            <h3 style="margin-bottom: 20px;">Filter Jobs</h3>
            <form method="GET" action="">
                <div class="filter-group">
                    <label><i class="fas fa-briefcase"></i> Keyword</label>
                    <input type="text" name="search" placeholder="Job title or keyword" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-map-marker-alt"></i> Location</label>
                    <input type="text" name="location" placeholder="City or municipality" value="<?php echo htmlspecialchars($location); ?>">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-tag"></i> Category</label>
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary-gradient" style="width: 100%;">Apply Filters</button>
                <div style="margin-top: 16px; text-align: center;">
                    <a href="jobs.php" style="color: var(--white-muted); font-size: 13px;">Clear all</a>
                </div>
            </form>
        </div>
        
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h2><?php echo count($jobs); ?> Jobs Found</h2>
            </div>
            
            <?php if (!empty($jobs)): ?>
                <?php foreach ($jobs as $job): ?>
                    <div class="job-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <h3 style="margin-bottom: 8px;"><?php echo htmlspecialchars($job['title']); ?></h3>
                                <div class="text-muted-light" style="margin-bottom: 12px;">
                                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($job['employer_name']); ?>
                                    <span style="margin: 0 12px">•</span>
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location'] ?: 'Remote'); ?>
                                </div>
                                <div class="salary-tag">
                                    ₱<?php echo number_format($job['salary_min']); ?> - ₱<?php echo number_format($job['salary_max']); ?>
                                </div>
                                <p class="text-muted-light" style="margin-top: 12px;"><?php echo htmlspecialchars(substr($job['description'], 0, 150)) . '...'; ?></p>
                            </div>
                        </div>
                        <div style="margin-top: 20px; display: flex; gap: 12px;">
                            <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn-primary-gradient" style="padding: 10px 24px;">View Details</a>
                            <?php if (isLoggedIn() && $_SESSION['user_role'] === 'jobseeker'): ?>
                                <a href="apply.php?id=<?php echo $job['id']; ?>" class="btn-outline-light" style="padding: 10px 24px;">Apply Now</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; background: rgba(255,255,255,0.03); border-radius: 24px;">
                    <i class="fas fa-search" style="font-size: 48px; color: var(--white-dim); margin-bottom: 16px;"></i>
                    <h3>No jobs found</h3>
                    <p class="text-muted-light">Try adjusting your search or filter criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>