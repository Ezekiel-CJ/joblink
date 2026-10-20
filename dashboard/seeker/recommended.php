<?php
$page_title = "Recommended Jobs";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'jobseeker') {
    redirect(SITE_URL . 'login.php', 'Please login to view recommendations.', 'error');
}

$user_id = $_SESSION['user_id'];
$db = getDB();

$profile = $db->query("SELECT skills FROM jobseeker_profiles WHERE user_id = $user_id")->fetch_assoc();
$user_skills = $profile ? explode(',', $profile['skills'] ?? '') : [];

$jobs = $db->query("SELECT * FROM jobs WHERE status = 'approved' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

$scored_jobs = [];
foreach ($jobs as $job) {
    $job_skills = !empty($job['required_skills']) ? explode(',', $job['required_skills']) : [];
    $match_score = 0;
    if (!empty($user_skills) && !empty($job_skills)) {
        $matches = 0;
        foreach ($user_skills as $uskill) {
            $uskill = trim(strtolower($uskill));
            foreach ($job_skills as $jskill) {
                if (trim(strtolower($jskill)) === $uskill) { $matches++; break; }
            }
        }
        $match_score = round(($matches / count($job_skills)) * 100);
    }
    $scored_jobs[] = ['job' => $job, 'score' => $match_score];
}
usort($scored_jobs, function($a, $b) { return $b['score'] - $a['score']; });

require_once '../../includes/header-dashboard.php';
?>

<style>
    .score-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .score-high { background: rgba(16,185,129,0.15); color: #10b981; }
    .score-mid { background: rgba(6,182,212,0.15); color: #06b6d4; }
    .score-low { background: rgba(245,158,11,0.15); color: #f59e0b; }
    .jobs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 24px; margin-top: 30px; }
    .job-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px; transition: all 0.3s; }
    .job-card:hover { transform: translateY(-3px); background: rgba(37,99,235,0.05); }
    .salary-tag { display: inline-block; background: rgba(6,182,212,0.15); padding: 4px 12px; border-radius: 20px; font-size: 13px; }
    .skill-highlight { background: rgba(16,185,129,0.15); color: #10b981; }
</style>

<div class="container-premium" style="padding: 30px 0;">
    <h1>Recommended for You</h1>
    
    <?php if (!empty($user_skills)): ?>
        <div style="background: rgba(6,182,212,0.1); border-radius: 16px; padding: 16px; margin: 20px 0;">
            <strong><i class="fas fa-tags"></i> Your Skills:</strong>
            <?php foreach ($user_skills as $skill): if (trim($skill)): ?>
                <span style="display: inline-block; background: rgba(6,182,212,0.15); padding: 4px 12px; border-radius: 20px; font-size: 12px; margin: 4px;"><?php echo htmlspecialchars(trim($skill)); ?></span>
            <?php endif; endforeach; ?>
        </div>
    <?php else: ?>
        <div style="background: rgba(245,158,11,0.1); border-radius: 16px; padding: 16px; margin: 20px 0;">
            <i class="fas fa-info-circle"></i> Add skills to your profile to get personalized job recommendations.
            <a href="profile.php" style="color: var(--azure-light); margin-left: 12px;">Update Profile →</a>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($scored_jobs)): ?>
        <div class="jobs-grid">
            <?php foreach ($scored_jobs as $item): 
                $job = $item['job']; $score = $item['score'];
                $score_class = 'score-low';
                if ($score >= 70) $score_class = 'score-high';
                elseif ($score >= 40) $score_class = 'score-mid';
            ?>
                <div class="job-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <h3 style="margin-bottom: 8px;"><?php echo htmlspecialchars($job['title']); ?></h3>
                        <?php if ($score > 0): ?><span class="score-badge <?php echo $score_class; ?>"><?php echo $score; ?>% Match</span><?php endif; ?>
                    </div>
                    <div class="text-muted-light" style="margin-bottom: 12px;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location'] ?: 'Remote'); ?></div>
                    <div class="salary-tag" style="margin-bottom: 16px;">₱<?php echo number_format($job['salary_min']); ?> - ₱<?php echo number_format($job['salary_max']); ?></div>
                    <a href="<?php echo SITE_URL; ?>job-details.php?id=<?php echo $job['id']; ?>" class="btn-primary-gradient" style="display: block; text-align: center; padding: 10px;">View Details</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 60px; background: rgba(255,255,255,0.03); border-radius: 20px;">
            <i class="fas fa-search" style="font-size: 48px; color: var(--white-dim); margin-bottom: 16px;"></i>
            <p class="text-muted-light">No jobs available at the moment.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer-dashboard.php'; ?>