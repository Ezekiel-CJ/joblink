<?php
$page_title = "Edit Job";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'employer') {
    redirect(SITE_URL . 'login.php', 'Please login to edit jobs.', 'error');
}

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$db = getDB();

$job = $db->query("SELECT * FROM jobs WHERE id = $job_id AND employer_id = $user_id")->fetch_assoc();
if (!$job) redirect('manage-jobs.php', 'Job not found.', 'error');

$categories = $db->query("SELECT * FROM job_categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$all_skills = $db->query("SELECT name FROM skills ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$job_skills = !empty($job['required_skills']) ? explode(',', $job['required_skills']) : [];

$message = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $requirements = sanitize($_POST['requirements'] ?? '');
    $responsibilities = sanitize($_POST['responsibilities'] ?? '');
    $benefits = sanitize($_POST['benefits'] ?? '');
    $work_schedule = sanitize($_POST['work_schedule'] ?? '');
    $education_level = sanitize($_POST['education_level'] ?? '');
    $years_experience = !empty($_POST['years_experience']) ? (int)$_POST['years_experience'] : null;
    $hiring_quantity = !empty($_POST['hiring_quantity']) ? (int)$_POST['hiring_quantity'] : 1;
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $location = sanitize($_POST['location'] ?? '');
    $salary_min = !empty($_POST['salary_min']) ? (float)$_POST['salary_min'] : null;
    $salary_max = !empty($_POST['salary_max']) ? (float)$_POST['salary_max'] : null;
    $employment_type = sanitize($_POST['employment_type'] ?? 'full-time');
    $skills = isset($_POST['skills']) ? array_map('trim', $_POST['skills']) : [];
    $skills_string = implode(',', $skills);
    
    $update = $db->prepare("UPDATE jobs SET 
        title=?, description=?, requirements=?, responsibilities=?, benefits=?, 
        work_schedule=?, education_level=?, years_experience=?, hiring_quantity=?,
        category_id=?, location=?, salary_min=?, salary_max=?, employment_type=?, required_skills=? 
        WHERE id=? AND employer_id=?");
    
    $update->bind_param("sssssssiiissdssii", 
        $title, $description, $requirements, $responsibilities, $benefits,
        $work_schedule, $education_level, $years_experience, $hiring_quantity,
        $category_id, $location, $salary_min, $salary_max, $employment_type, $skills_string, 
        $job_id, $user_id);
    
    if ($update->execute()) { 
        logAudit($user_id, 'edit_job', 'job', $job_id); 
        if ($job['status'] === 'rejected') {
            $db->query("UPDATE jobs SET status='pending', rejection_reason=NULL WHERE id=$job_id");
        }
        $message = "Job updated successfully!"; 
        $job = $db->query("SELECT * FROM jobs WHERE id = $job_id")->fetch_assoc(); 
        $job_skills = explode(',', $job['required_skills'] ?? ''); 
    } else {
        $error = "Failed to update.";
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
    .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
    .skills-container { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
    .skill-tag { background: rgba(6,182,212,0.15); padding: 8px 16px; border-radius: 30px; font-size: 13px; cursor: pointer; transition: all 0.2s; }
    .skill-tag:hover { background: rgba(6,182,212,0.3); }
    .skill-tag.selected { background: linear-gradient(135deg, #2563eb, #06b6d4); color: white; }
    .status-warning { background: rgba(245,158,11,0.15); border: 1px solid #f59e0b; border-radius: 16px; padding: 16px; margin-bottom: 20px; }
    .alert-success { background: rgba(16,185,129,0.15); border: 1px solid #10b981; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #10b981; }
    .btn-save { width: 100%; background: linear-gradient(135deg, #2563eb, #06b6d4); border: none; padding: 16px; border-radius: 40px; color: white; font-weight: 600; cursor: pointer; transition: all 0.3s; }
    .btn-save:hover { transform: translateY(-2px); box-shadow: 0 0 20px rgba(37,99,235,0.4); }
    hr { border-color: rgba(255,255,255,0.1); margin: 20px 0; }
    @media (max-width: 768px) { .form-row, .form-row-3 { grid-template-columns: 1fr; gap: 16px; } }
  
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
</style>

<div class="container-premium" style="padding: 60px 0;">
    <div style="max-width: 900px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h1>Edit Job Posting</h1>
            <a href="manage-jobs.php" class="btn-outline-light"><i class="fas fa-arrow-left"></i> Back to Jobs</a>
        </div>
        
        <?php if ($job['status'] === 'rejected' && !empty($job['rejection_reason'])): ?>
            <div class="status-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>This job was declined. Reason:</strong> <?php echo htmlspecialchars($job['rejection_reason']); ?>
                <p class="text-muted-light" style="margin-top: 8px;">After editing, the job will be resubmitted for approval.</p>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <!-- Basic Information -->
            <div class="form-section">
                <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                <div class="form-group">
                    <label>Job Title *</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($job['title']); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $job['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" value="<?php echo htmlspecialchars($job['location'] ?? ''); ?>" placeholder="e.g., Makati City, Remote">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Employment Type</label>
                        <select name="employment_type">
                            <option value="full-time" <?php echo $job['employment_type'] === 'full-time' ? 'selected' : ''; ?>>Full Time</option>
                            <option value="part-time" <?php echo $job['employment_type'] === 'part-time' ? 'selected' : ''; ?>>Part Time</option>
                            <option value="contract" <?php echo $job['employment_type'] === 'contract' ? 'selected' : ''; ?>>Contract</option>
                            <option value="internship" <?php echo $job['employment_type'] === 'internship' ? 'selected' : ''; ?>>Internship</option>
                            <option value="remote" <?php echo $job['employment_type'] === 'remote' ? 'selected' : ''; ?>>Remote</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Hiring Quantity</label>
                        <input type="number" name="hiring_quantity" value="<?php echo $job['hiring_quantity'] ?? 1; ?>" min="1" placeholder="Number of positions">
                    </div>
                </div>
            </div>
            
            <!-- Salary Information -->
            <div class="form-section">
                <h3><i class="fas fa-money-bill-wave"></i> Salary Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Minimum Salary (₱)</label>
                        <input type="number" name="salary_min" value="<?php echo $job['salary_min']; ?>" step="1000" placeholder="₱">
                    </div>
                    <div class="form-group">
                        <label>Maximum Salary (₱)</label>
                        <input type="number" name="salary_max" value="<?php echo $job['salary_max']; ?>" step="1000" placeholder="₱">
                    </div>
                </div>
            </div>
            
            <!-- Requirements & Qualifications -->
            <div class="form-section">
                <h3><i class="fas fa-graduation-cap"></i> Requirements & Qualifications</h3>
                
                <div class="form-row-3">
                    <div class="form-group">
                        <label>Education Level</label>
                        <select name="education_level">
                            <option value="">Select Education Level</option>
                            <option value="high_school" <?php echo ($job['education_level'] ?? '') == 'high_school' ? 'selected' : ''; ?>>High School Graduate</option>
                            <option value="associate" <?php echo ($job['education_level'] ?? '') == 'associate' ? 'selected' : ''; ?>>Associate / Vocational</option>
                            <option value="bachelor" <?php echo ($job['education_level'] ?? '') == 'bachelor' ? 'selected' : ''; ?>>Bachelor's Degree</option>
                            <option value="master" <?php echo ($job['education_level'] ?? '') == 'master' ? 'selected' : ''; ?>>Master's Degree</option>
                            <option value="phd" <?php echo ($job['education_level'] ?? '') == 'phd' ? 'selected' : ''; ?>>Doctorate (PhD)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Years of Experience</label>
                        <select name="years_experience">
                            <option value="">No experience required</option>
                            <option value="1" <?php echo ($job['years_experience'] ?? '') == 1 ? 'selected' : ''; ?>>1+ year</option>
                            <option value="2" <?php echo ($job['years_experience'] ?? '') == 2 ? 'selected' : ''; ?>>2+ years</option>
                            <option value="3" <?php echo ($job['years_experience'] ?? '') == 3 ? 'selected' : ''; ?>>3+ years</option>
                            <option value="5" <?php echo ($job['years_experience'] ?? '') == 5 ? 'selected' : ''; ?>>5+ years</option>
                            <option value="7" <?php echo ($job['years_experience'] ?? '') == 7 ? 'selected' : ''; ?>>7+ years</option>
                            <option value="10" <?php echo ($job['years_experience'] ?? '') == 10 ? 'selected' : ''; ?>>10+ years</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Work Schedule</label>
                        <select name="work_schedule">
                            <option value="">Select Schedule</option>
                            <option value="monday_friday" <?php echo ($job['work_schedule'] ?? '') == 'monday_friday' ? 'selected' : ''; ?>>Monday - Friday</option>
                            <option value="monday_saturday" <?php echo ($job['work_schedule'] ?? '') == 'monday_saturday' ? 'selected' : ''; ?>>Monday - Saturday</option>
                            <option value="shifting" <?php echo ($job['work_schedule'] ?? '') == 'shifting' ? 'selected' : ''; ?>>Shifting Schedule</option>
                            <option value="night_shift" <?php echo ($job['work_schedule'] ?? '') == 'night_shift' ? 'selected' : ''; ?>>Night Shift</option>
                            <option value="flexible" <?php echo ($job['work_schedule'] ?? '') == 'flexible' ? 'selected' : ''; ?>>Flexible Hours</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Qualifications / Requirements</label>
                    <textarea name="requirements" rows="4" placeholder="List the qualifications and requirements for this position..."><?php echo htmlspecialchars($job['requirements'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Job Details -->
            <div class="form-section">
                <h3><i class="fas fa-file-alt"></i> Job Details</h3>
                
                <div class="form-group">
                    <label>Job Description *</label>
                    <textarea name="description" rows="6" required placeholder="Describe the role, responsibilities, and what the job entails..."><?php echo htmlspecialchars($job['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Key Responsibilities</label>
                    <textarea name="responsibilities" rows="5" placeholder="List the main responsibilities for this role (one per line or bullet points)..."><?php echo htmlspecialchars($job['responsibilities'] ?? ''); ?></textarea>
                    <p class="text-dim" style="font-size: 12px; margin-top: 5px;">e.g., • Develop and maintain web applications<br>• Collaborate with cross-functional teams</p>
                </div>
                
                <div class="form-group">
                    <label>Benefits & Perks</label>
                    <textarea name="benefits" rows="4" placeholder="List the benefits offered for this position..."><?php echo htmlspecialchars($job['benefits'] ?? ''); ?></textarea>
                    <p class="text-dim" style="font-size: 12px; margin-top: 5px;">e.g., Health insurance, 13th month pay, Paid leaves, Retirement plan</p>
                </div>
            </div>
            
            <!-- Required Skills -->
            <div class="form-section">
                <h3><i class="fas fa-code"></i> Required Skills</h3>
                <p class="text-muted-light" style="margin-bottom: 16px;">Click on skills to select them. Selected skills will be highlighted.</p>
                <div class="skills-container">
                    <?php foreach ($all_skills as $skill): ?>
                        <span class="skill-tag <?php echo in_array($skill['name'], $job_skills) ? 'selected' : ''; ?>" data-skill="<?php echo htmlspecialchars($skill['name']); ?>">
                            <?php echo htmlspecialchars($skill['name']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="skills[]" id="selectedSkills" value="<?php echo htmlspecialchars($job['required_skills'] ?? ''); ?>">
            </div>
            
            <button type="submit" class="btn-save">
                <i class="fas fa-save"></i> Save Changes
            </button>
            
            <div style="text-align: center; margin-top: 16px;">
                <a href="manage-jobs.php" class="btn-outline-light">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    // Skill selection
    const skillTags = document.querySelectorAll('.skill-tag');
    const selectedSkillsInput = document.getElementById('selectedSkills');
    let selectedSkills = selectedSkillsInput.value ? selectedSkillsInput.value.split(',') : [];
    
    skillTags.forEach(tag => {
        if (selectedSkills.includes(tag.dataset.skill)) {
            tag.classList.add('selected');
        }
        
        tag.addEventListener('click', function() {
            this.classList.toggle('selected');
            
            selectedSkills = [];
            document.querySelectorAll('.skill-tag.selected').forEach(selected => {
                selectedSkills.push(selected.dataset.skill);
            });
            selectedSkillsInput.value = selectedSkills.join(',');
        });
    });
</script>

<?php require_once '../../includes/footer-dashboard.php'; ?>