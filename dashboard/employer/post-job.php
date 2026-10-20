<?php
$page_title = "Post a Job";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'employer') {
    redirect(SITE_URL . 'login.php', 'Please login to post jobs.', 'error');
}

$user_id = $_SESSION['user_id'];
$db = getDB();

$employer = $db->query("SELECT verification_status FROM employer_profiles WHERE user_id = $user_id")->fetch_assoc();
if ($employer['verification_status'] !== 'verified') redirect('index.php', 'Your business must be verified first.', 'warning');

$categories = $db->query("SELECT * FROM job_categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$all_skills = $db->query("SELECT name FROM skills ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$message = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $requirements = sanitize($_POST['requirements'] ?? '');
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $location = sanitize($_POST['location'] ?? '');
    $salary_min = !empty($_POST['salary_min']) ? (float)$_POST['salary_min'] : null;
    $salary_max = !empty($_POST['salary_max']) ? (float)$_POST['salary_max'] : null;
    $employment_type = sanitize($_POST['employment_type'] ?? 'full-time');
    $skills = isset($_POST['skills']) ? array_map('trim', $_POST['skills']) : [];
    $skills_string = implode(',', $skills);
    
    if (empty($title) || empty($description)) $error = "Please fill in all required fields.";
    else {
        $insert = $db->prepare("INSERT INTO jobs (employer_id, title, description, requirements, category_id, location, salary_min, salary_max, employment_type, required_skills, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $insert->bind_param("isssisddss", $user_id, $title, $description, $requirements, $category_id, $location, $salary_min, $salary_max, $employment_type, $skills_string);
        if ($insert->execute()) { logAudit($user_id, 'post_job', 'job', $insert->insert_id); $message = "Job posted! Pending LGU approval."; }
        else $error = "Failed to post job.";
    }
}

require_once '../../includes/header-dashboard.php';
?>

<style>
    .form-section { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 24px; padding: 32px; margin-bottom: 30px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 16px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; font-family: inherit; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .skills-container { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
    .skill-tag { background: rgba(6,182,212,0.15); padding: 8px 16px; border-radius: 30px; font-size: 13px; cursor: pointer; transition: all 0.2s; }
    .skill-tag:hover { background: rgba(6,182,212,0.3); }
    .skill-tag.selected { background: linear-gradient(135deg, #2563eb, #06b6d4); color: white; }
    .btn-submit { width: 100%; background: linear-gradient(135deg, #2563eb, #06b6d4); border: none; padding: 16px; border-radius: 40px; color: white; font-weight: 600; cursor: pointer; }
    .alert-success { background: rgba(16,185,129,0.15); border: 1px solid #10b981; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #10b981; }
    .alert-danger { background: rgba(239,68,68,0.15); border: 1px solid #ef4444; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #ef4444; }
    .info-note { margin-top: 20px; background: rgba(6,182,212,0.1); border-radius: 16px; padding: 16px; text-align: center; }
    
    /* Fix for dropdown select options visibility */
    select, .form-group select {
        background-color: #0f172a !important;
        color: #ffffff !important;
    }
    
    select option, .form-group select option {
        background-color: #1e293b !important;
        color: #ffffff !important;
    }
    
    select option:hover, .form-group select option:hover {
        background-color: #2563eb !important;
    }
    
    @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; gap: 16px; } }
</style>

<div class="container-premium" style="padding: 60px 0;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h1>Post a New Job</h1>
        <?php if ($message): ?><div class="alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert-danger"><?php echo $error; ?></div><?php endif; ?>
        
        <form method="POST">
            <div class="form-section"><h3>Basic Information</h3>
                <div class="form-group"><label>Job Title *</label><input type="text" name="title" required></div>
                <div class="form-group"><label>Category</label><select name="category_id"><option value="">Select Category</option><?php foreach ($categories as $cat): ?><option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Location</label><input type="text" name="location" placeholder="e.g., Makati City, Remote"></div>
                <div class="form-group"><label>Employment Type</label><select name="employment_type"><option value="full-time">Full Time</option><option value="part-time">Part Time</option><option value="contract">Contract</option><option value="internship">Internship</option><option value="remote">Remote</option></select></div>
            </div>
            
            <div class="form-section"><h3>Salary Information</h3>
                <div class="form-row"><div class="form-group"><label>Min Salary</label><input type="number" name="salary_min" step="1000" placeholder="₱"></div><div class="form-group"><label>Max Salary</label><input type="number" name="salary_max" step="1000" placeholder="₱"></div></div>
            </div>
            
            <div class="form-section"><h3>Job Details</h3>
                <div class="form-group"><label>Description *</label><textarea name="description" rows="6" required placeholder="Describe the role and responsibilities..."></textarea></div>
                <div class="form-group"><label>Requirements</label><textarea name="requirements" rows="4" placeholder="List qualifications and requirements..."></textarea></div>
            </div>
            
            <div class="form-section"><h3>Required Skills</h3>
                <div class="skills-container" id="skillsContainer"><?php foreach ($all_skills as $skill): ?><span class="skill-tag" data-skill="<?php echo htmlspecialchars($skill['name']); ?>"><?php echo htmlspecialchars($skill['name']); ?></span><?php endforeach; ?></div>
                <input type="hidden" name="skills[]" id="selectedSkills">
            </div>
            
            <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Submit for Approval</button>
        </form>
        <div class="info-note" style="margin-top: 20px; background: rgba(6,182,212,0.1); border-radius: 16px; padding: 16px; text-align: center;"><i class="fas fa-info-circle"></i> Jobs are reviewed by LGU within 24-48 hours.</div>
    </div>
</div>

<script>
    const skillTags = document.querySelectorAll('.skill-tag'), selectedSkills = document.getElementById('selectedSkills'), selected = [];
    skillTags.forEach(tag => { tag.addEventListener('click', function() { this.classList.toggle('selected'); selected.length = 0; document.querySelectorAll('.skill-tag.selected').forEach(s => selected.push(s.dataset.skill)); selectedSkills.value = selected.join(','); }); });
</script>

<?php require_once '../../includes/footer-dashboard.php'; ?>