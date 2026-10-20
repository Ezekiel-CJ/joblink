<?php
$page_title = "Browse Jobs";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'jobseeker') {
    redirect(SITE_URL . 'login.php', 'Please login to browse jobs.', 'error');
}

$user_id = $_SESSION['user_id'];
$db = getDB();

// Get user skills for match calculation
$profile = $db->query("SELECT skills FROM jobseeker_profiles WHERE user_id = $user_id")->fetch_assoc();
$user_skills = $profile ? explode(',', $profile['skills'] ?? '') : [];

// Get filters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$location = isset($_GET['location']) ? sanitize($_GET['location']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$min_salary = isset($_GET['min_salary']) ? (int)$_GET['min_salary'] : 0;
$employment_type = isset($_GET['employment_type']) ? sanitize($_GET['employment_type']) : '';
$sort_by = isset($_GET['sort_by']) ? sanitize($_GET['sort_by']) : 'newest';

// =====================================================
// DEBUG: CHECK CATEGORIES IN DATABASE
// =====================================================
$category_check = $db->query("SELECT COUNT(*) as count FROM job_categories");
$category_count = $category_check ? $category_check->fetch_assoc()['count'] : 0;

// If no categories exist, insert default ones
if ($category_count == 0) {
    $db->query("INSERT INTO job_categories (name, description, sort_order) VALUES 
        ('Information Technology', 'Software development, IT support, networking', 1),
        ('Administration', 'Office management, clerical, secretarial', 2),
        ('Healthcare', 'Medical, nursing, caregiving', 3),
        ('Education', 'Teaching, training, academic support', 4),
        ('Construction', 'Building, engineering, skilled trades', 5),
        ('Food Service', 'Restaurants, catering, hospitality', 6),
        ('Sales & Marketing', 'Retail, advertising, business development', 7),
        ('Agriculture', 'Farming, fisheries, agribusiness', 8),
        ('Transport & Logistics', 'Driving, delivery, warehousing', 9),
        ('Manufacturing', 'Production, assembly, quality control', 10)");
    $category_count = 10;
}

// Also check if jobs have category_id set, if not, update them
$jobs_without_category = $db->query("SELECT COUNT(*) as count FROM jobs WHERE category_id IS NULL OR category_id = 0");
$jobs_missing = $jobs_without_category ? $jobs_without_category->fetch_assoc()['count'] : 0;

if ($jobs_missing > 0) {
    // Get IT category ID as default
    $default_cat = $db->query("SELECT id FROM job_categories WHERE name LIKE '%Technology%' LIMIT 1")->fetch_assoc();
    if ($default_cat) {
        $db->query("UPDATE jobs SET category_id = " . $default_cat['id'] . " WHERE category_id IS NULL OR category_id = 0");
    }
}

// Get all categories for filter
$categories = $db->query("SELECT * FROM job_categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Build query
$query = "SELECT j.*, 
          c.name as category_name, 
          u.full_name as employer_name, 
          ep.business_name,
          ep.logo_path
          FROM jobs j 
          LEFT JOIN job_categories c ON j.category_id = c.id 
          LEFT JOIN users u ON j.employer_id = u.id 
          LEFT JOIN employer_profiles ep ON u.id = ep.user_id 
          WHERE j.status = 'approved'";

// Apply filters
if (!empty($search)) {
    $search_escaped = $db->real_escape_string($search);
    $query .= " AND (j.title LIKE '%$search_escaped%' 
                OR j.description LIKE '%$search_escaped%' 
                OR j.required_skills LIKE '%$search_escaped%')";
}
if (!empty($location)) {
    $location_escaped = $db->real_escape_string($location);
    $query .= " AND j.location LIKE '%$location_escaped%'";
}
if ($category > 0) {
    $query .= " AND j.category_id = $category";
}
if ($min_salary > 0) {
    $query .= " AND j.salary_max >= $min_salary";
}
if (!empty($employment_type)) {
    $query .= " AND j.employment_type = '$employment_type'";
}

// Apply sorting
switch ($sort_by) {
    case 'match':
        $query .= " ORDER BY j.created_at DESC";
        break;
    case 'salary_high':
        $query .= " ORDER BY j.salary_max DESC";
        break;
    case 'salary_low':
        $query .= " ORDER BY j.salary_min ASC";
        break;
    case 'oldest':
        $query .= " ORDER BY j.created_at ASC";
        break;
    default:
        $query .= " ORDER BY j.created_at DESC";
        break;
}
$query .= " LIMIT 50";

$result = $db->query($query);
$jobs = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Calculate match scores
foreach ($jobs as &$job) {
    $job_skills = !empty($job['required_skills']) ? explode(',', $job['required_skills']) : [];
    $match_score = 0;
    if (!empty($user_skills) && !empty($job_skills)) {
        $matches = 0;
        foreach ($user_skills as $uskill) {
            $uskill = trim(strtolower($uskill));
            foreach ($job_skills as $jskill) {
                if (trim(strtolower($jskill)) === $uskill) {
                    $matches++;
                    break;
                }
            }
        }
        $match_score = round(($matches / count($job_skills)) * 100);
    }
    $job['match_score'] = $match_score;
    
    // If category_name is null, try to get it directly
    if (empty($job['category_name']) && !empty($job['category_id'])) {
        $cat_query = $db->query("SELECT name FROM job_categories WHERE id = " . $job['category_id']);
        if ($cat_query && $cat_row = $cat_query->fetch_assoc()) {
            $job['category_name'] = $cat_row['name'];
        }
    }
}

// Sort by match score if requested
if ($sort_by === 'match') {
    usort($jobs, function($a, $b) {
        return $b['match_score'] - $a['match_score'];
    });
}

// Get unique locations for filter
$locations = $db->query("SELECT DISTINCT location FROM jobs WHERE status = 'approved' AND location IS NOT NULL AND location != '' ORDER BY location LIMIT 20")->fetch_all(MYSQLI_ASSOC);

require_once '../../includes/header-dashboard.php';
?>

<style>
    .jobs-page {
        display: flex;
        gap: 32px;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .filters-sidebar {
        width: 280px;
        flex-shrink: 0;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 24px;
        padding: 24px;
        position: sticky;
        top: 100px;
        height: fit-content;
    }
    
    .filter-group {
        margin-bottom: 24px;
    }
    
    .filter-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: rgba(255,255,255,0.5);
        margin-bottom: 10px;
    }
    
    .filter-group input,
    .filter-group select {
        width: 100%;
        padding: 12px 14px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        color: white;
        font-family: inherit;
        font-size: 14px;
    }
    
    .filter-group input:focus,
    .filter-group select:focus {
        outline: none;
        border-color: #2563eb;
    }
    
    .filter-group input::placeholder {
        color: rgba(255,255,255,0.3);
    }
    
    .filter-group select option {
        background: #0f172a;
        color: white;
    }
    
    .filter-actions {
        display: flex;
        gap: 12px;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    
    .sort-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
        padding-bottom: 16px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    
    .sort-options {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .sort-btn {
        padding: 6px 16px;
        border-radius: 30px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        color: rgba(255,255,255,0.6);
        text-decoration: none;
        font-size: 13px;
        transition: all 0.2s;
    }
    
    .sort-btn.active {
        background: linear-gradient(135deg, #2563eb, #06b6d4);
        color: white;
        border-color: transparent;
    }
    
    .sort-btn:hover {
        background: rgba(37,99,235,0.2);
        color: white;
    }
    
    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }
    
    .results-count h2 {
        font-size: 24px;
        margin-bottom: 4px;
    }
    
    .job-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 20px;
        transition: all 0.3s;
    }
    
    .job-card:hover {
        transform: translateY(-3px);
        background: rgba(37,99,235,0.05);
        border-color: rgba(37,99,235,0.2);
    }
    
    .job-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 16px;
    }
    
    .job-title-section h3 {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 6px;
    }
    
    .job-company {
        color: rgba(255,255,255,0.6);
        font-size: 14px;
    }
    
    .match-badge {
        display: inline-block;
        background: rgba(16,185,129,0.15);
        color: #10b981;
        padding: 5px 14px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .match-badge.high {
        background: rgba(16,185,129,0.15);
        color: #10b981;
    }
    
    .match-badge.medium {
        background: rgba(6,182,212,0.15);
        color: #06b6d4;
    }
    
    .match-badge.low {
        background: rgba(245,158,11,0.15);
        color: #f59e0b;
    }
    
    .job-details {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 16px;
        font-size: 13px;
        color: rgba(255,255,255,0.5);
    }
    
    .job-details span {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .category-badge {
        display: inline-block;
        background: rgba(6,182,212,0.12);
        padding: 5px 14px;
        border-radius: 30px;
        font-size: 12px;
        color: #22d3ee;
    }
    
    .salary-tag {
        display: inline-block;
        background: rgba(6,182,212,0.12);
        padding: 5px 14px;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 500;
        color: #22d3ee;
    }
    
    .job-skills {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin: 16px 0;
    }
    
    .skill-tag {
        background: rgba(255,255,255,0.05);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        color: rgba(255,255,255,0.6);
    }
    
    .skill-tag.matched {
        background: rgba(16,185,129,0.15);
        color: #10b981;
    }
    
    .job-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid rgba(255,255,255,0.05);
    }
    
    .job-date {
        font-size: 12px;
        color: rgba(255,255,255,0.4);
    }
    
    .empty-state {
        text-align: center;
        padding: 60px;
        background: rgba(255,255,255,0.03);
        border-radius: 20px;
    }
    
    .empty-state i {
        font-size: 48px;
        color: rgba(255,255,255,0.2);
        margin-bottom: 16px;
    }
    
    .debug-info {
        background: rgba(245,158,11,0.1);
        border: 1px solid rgba(245,158,11,0.3);
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 20px;
        font-size: 12px;
    }
    
    @media (max-width: 900px) {
        .jobs-page {
            flex-direction: column;
        }
        .filters-sidebar {
            width: 100%;
            position: static;
        }
    }
    
    @media (max-width: 640px) {
        .job-header {
            flex-direction: column;
        }
        .job-footer {
            flex-direction: column;
            align-items: stretch;
        }
        .job-footer a {
            text-align: center;
        }
        .sort-options {
            justify-content: center;
        }
    }
    .dashboard-main {
    margin-left: -60px !important;
    padding-left: 200px !important;
}
</style>

<div class="dashboard-main">
    <div class="jobs-page">
        
        <!-- Filters Sidebar -->
        <div class="filters-sidebar">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-filter"></i> Filters</h3>
            
            <form method="GET" action="" id="filterForm">
                <?php if ($sort_by !== 'newest'): ?>
                    <input type="hidden" name="sort_by" value="<?php echo htmlspecialchars($sort_by); ?>">
                <?php endif; ?>
                
                <div class="filter-group">
                    <label><i class="fas fa-search"></i> Keyword</label>
                    <input type="text" name="search" placeholder="Job title, skills, or company" value="<?php echo htmlspecialchars($search); ?>">
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
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label><i class="fas fa-money-bill-wave"></i> Min Salary</label>
                    <select name="min_salary">
                        <option value="">Any Salary</option>
                        <option value="20000" <?php echo $min_salary == 20000 ? 'selected' : ''; ?>>₱20,000+</option>
                        <option value="30000" <?php echo $min_salary == 30000 ? 'selected' : ''; ?>>₱30,000+</option>
                        <option value="40000" <?php echo $min_salary == 40000 ? 'selected' : ''; ?>>₱40,000+</option>
                        <option value="50000" <?php echo $min_salary == 50000 ? 'selected' : ''; ?>>₱50,000+</option>
                        <option value="70000" <?php echo $min_salary == 70000 ? 'selected' : ''; ?>>₱70,000+</option>
                        <option value="100000" <?php echo $min_salary == 100000 ? 'selected' : ''; ?>>₱100,000+</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label><i class="fas fa-briefcase"></i> Employment Type</label>
                    <select name="employment_type">
                        <option value="">All Types</option>
                        <option value="full-time" <?php echo $employment_type === 'full-time' ? 'selected' : ''; ?>>Full Time</option>
                        <option value="part-time" <?php echo $employment_type === 'part-time' ? 'selected' : ''; ?>>Part Time</option>
                        <option value="contract" <?php echo $employment_type === 'contract' ? 'selected' : ''; ?>>Contract</option>
                        <option value="internship" <?php echo $employment_type === 'internship' ? 'selected' : ''; ?>>Internship</option>
                        <option value="remote" <?php echo $employment_type === 'remote' ? 'selected' : ''; ?>>Remote</option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn-primary-gradient" style="flex: 1;">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <a href="jobs.php" class="btn-outline-light" style="padding: 10px 16px;">
                        <i class="fas fa-sync-alt"></i> Reset
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Jobs List -->
        <div class="jobs-list">
            
            <!-- Sort Bar -->
            <div class="sort-bar">
                <div class="results-count">
                    <h2><?php echo count($jobs); ?> Jobs Found</h2>
                    <?php if (!empty($user_skills)): ?>
                        <p class="text-muted-light" style="font-size: 13px;">
                            <i class="fas fa-chart-line"></i> Based on your <?php echo count($user_skills); ?> skills
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="sort-options">
                    <span class="text-dim" style="font-size: 13px;">Sort by:</span>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'newest'])); ?>" class="sort-btn <?php echo $sort_by === 'newest' ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i> Newest
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'match'])); ?>" class="sort-btn <?php echo $sort_by === 'match' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i> Best Match
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'salary_high'])); ?>" class="sort-btn <?php echo $sort_by === 'salary_high' ? 'active' : ''; ?>">
                        <i class="fas fa-arrow-up"></i> Salary (High-Low)
                    </a>
                </div>
            </div>
            
            <!-- Job Cards -->
            <?php if (!empty($jobs)): ?>
                <?php foreach ($jobs as $job): 
                    $match_class = 'low';
                    if ($job['match_score'] >= 70) $match_class = 'high';
                    elseif ($job['match_score'] >= 40) $match_class = 'medium';
                    
                    $job_skills_array = !empty($job['required_skills']) ? explode(',', $job['required_skills']) : [];
                    
                    // Display category name (with fallback)
                    $display_category = !empty($job['category_name']) ? $job['category_name'] : 'Uncategorized';
                ?>
                    <div class="job-card">
                        <div class="job-header">
                            <div class="job-title-section">
                                <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                                <div class="job-company">
                                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($job['business_name'] ?? $job['employer_name']); ?>
                                </div>
                            </div>
                            <?php if ($job['match_score'] > 0): ?>
                                <div class="match-badge <?php echo $match_class; ?>">
                                    <i class="fas fa-chart-line"></i> <?php echo $job['match_score']; ?>% Match
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="job-details">
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location'] ?: 'Remote'); ?></span>
                            <span><i class="fas fa-briefcase"></i> <?php echo ucfirst(str_replace('-', ' ', $job['employment_type'] ?? 'Full-time')); ?></span>
                            <span class="category-badge"><i class="fas fa-tag"></i> <?php echo $display_category; ?></span>
                            <span class="salary-tag">₱<?php echo number_format($job['salary_min']); ?> - ₱<?php echo number_format($job['salary_max']); ?></span>
                        </div>
                        
                        <?php if (!empty($job_skills_array)): ?>
                            <div class="job-skills">
                                <?php foreach (array_slice($job_skills_array, 0, 5) as $skill): 
                                    $skill_trim = trim($skill);
                                    $is_matched = in_array(strtolower($skill_trim), array_map('strtolower', $user_skills));
                                ?>
                                    <span class="skill-tag <?php echo $is_matched ? 'matched' : ''; ?>">
                                        <?php echo htmlspecialchars($skill_trim); ?>
                                        <?php if ($is_matched): ?> ✓<?php endif; ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if (count($job_skills_array) > 5): ?>
                                    <span class="skill-tag">+<?php echo count($job_skills_array) - 5; ?> more</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <p class="text-muted-light" style="font-size: 14px; line-height: 1.5;">
                            <?php echo htmlspecialchars(substr(strip_tags($job['description']), 0, 150)); ?>...
                        </p>
                        
                        <div class="job-footer">
                            <div class="job-date">
                                <i class="fas fa-calendar-alt"></i> Posted <?php echo date('M d, Y', strtotime($job['created_at'])); ?>
                            </div>
                            <a href="<?php echo SITE_URL; ?>job-details.php?id=<?php echo $job['id']; ?>" class="btn-primary-gradient" style="padding: 8px 24px;">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>No jobs found</h3>
                    <p class="text-muted-light">Try adjusting your search or filter criteria.</p>
                    <a href="jobs.php" class="btn-outline-light" style="margin-top: 16px; display: inline-block;">
                        <i class="fas fa-sync-alt"></i> Clear All Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.getElementById('filterForm')?.addEventListener('submit', function(e) {});
</script>

<?php require_once '../../includes/footer-dashboard.php'; ?>