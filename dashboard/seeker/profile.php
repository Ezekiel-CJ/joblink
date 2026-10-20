<?php
$page_title = "My Profile";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'jobseeker') {
    redirect(SITE_URL . 'login.php', 'Please login to access your profile.', 'error');
}

$user_id = $_SESSION['user_id'];
$db = getDB();
$user = getCurrentUser();

// Check if name can be changed (3 months cooldown - 90 days)
$can_change_name = true;
$days_until_change = 0;
$months_until_change = 0;
$next_change_date = null;

if (!empty($user['last_name_change'])) {
    $last_change = strtotime($user['last_name_change']);
    $next_change = strtotime('+90 days', $last_change); // 90 days = 3 months
    $now = time();
    
    if ($now < $next_change) {
        $can_change_name = false;
        $days_until_change = ceil(($next_change - $now) / (60 * 60 * 24));
        $months_until_change = ceil($days_until_change / 30);
        $next_change_date = date('M d, Y', $next_change);
    }
}

// Get profile
$profile = $db->query("SELECT * FROM jobseeker_profiles WHERE user_id = $user_id")->fetch_assoc();
if (!$profile) {
    $db->query("INSERT INTO jobseeker_profiles (user_id) VALUES ($user_id)");
    $profile = $db->query("SELECT * FROM jobseeker_profiles WHERE user_id = $user_id")->fetch_assoc();
}

// Get all skills
$all_skills = $db->query("SELECT name FROM skills ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$user_skills = $profile['skills'] ? explode(',', $profile['skills']) : [];

$message = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $bio = sanitize($_POST['bio'] ?? '');
    $education = sanitize($_POST['education'] ?? '');
    $experience = sanitize($_POST['experience'] ?? '');
    $skills = isset($_POST['skills']) ? $_POST['skills'] : [];
    $skills_string = implode(',', $skills);
    
    $update = $db->prepare("UPDATE jobseeker_profiles SET bio = ?, education = ?, experience = ?, skills = ? WHERE user_id = ?");
    $update->bind_param("ssssi", $bio, $education, $experience, $skills_string, $user_id);
    
    if ($update->execute()) {
        $message = "Profile updated successfully!";
        $profile['bio'] = $bio;
        $profile['education'] = $education;
        $profile['experience'] = $experience;
        $profile['skills'] = $skills_string;
        $user_skills = $skills;
    } else {
        $error = "Failed to update profile.";
    }
}

// Handle name change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_name'])) {
    $new_name = sanitize($_POST['full_name'] ?? '');
    
    if (empty($new_name)) {
        $error = "Name cannot be empty.";
    } elseif (!$can_change_name) {
        $error = "You can change your name again on " . date('M d, Y', strtotime('+90 days', strtotime($user['last_name_change']))) . ".";
    } else {
        $update = $db->prepare("UPDATE users SET full_name = ?, last_name_change = NOW() WHERE id = ?");
        $update->bind_param("si", $new_name, $user_id);
        
        if ($update->execute()) {
            $_SESSION['user_name'] = $new_name;
            $message = "Name changed successfully! Next change available in 3 months (90 days).";
            $user['full_name'] = $new_name;
            $user['last_name_change'] = date('Y-m-d H:i:s');
            $can_change_name = false;
            $days_until_change = 90;
            $months_until_change = 3;
            $next_change_date = date('M d, Y', strtotime('+90 days'));
        } else {
            $error = "Failed to change name.";
        }
    }
}

// Handle avatar upload
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $upload_result = uploadFile($_FILES['avatar'], 'profiles/');
    if ($upload_result['success']) {
        $avatar_path = $upload_result['path'];
        $db->query("UPDATE users SET avatar_path = '$avatar_path' WHERE id = $user_id");
        $message = "Profile picture updated!";
        $user['avatar_path'] = $avatar_path;
    } else {
        $error = $upload_result['error'];
    }
}

// Handle resume upload
if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
    $upload_result = uploadFile($_FILES['resume'], 'resumes/');
    if ($upload_result['success']) {
        $resume_path = $upload_result['path'];
        $db->query("UPDATE jobseeker_profiles SET resume_path = '$resume_path' WHERE user_id = $user_id");
        $message = "Resume uploaded successfully!";
        $profile['resume_path'] = $resume_path;
    } else {
        $error = $upload_result['error'];
    }
}

require_once '../../includes/header-dashboard.php';
?>

<style>
    .profile-section {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 24px;
        padding: 32px;
        margin-bottom: 30px;
    }
    
    .form-group {
        margin-bottom: 24px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
    }
    
    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 12px 16px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        color: white;
        font-family: inherit;
    }
    
    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #2563eb;
    }
    
    .readonly-field {
        background: rgba(255,255,255,0.03);
        padding: 12px 16px;
        border-radius: 12px;
        color: var(--white-muted);
    }
    
    .skills-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 16px;
    }
    
    .skill-tag {
        background: rgba(6,182,212,0.15);
        padding: 8px 16px;
        border-radius: 30px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .skill-tag:hover {
        background: rgba(6,182,212,0.3);
    }
    
    .skill-tag.selected {
        background: linear-gradient(135deg, #2563eb, #06b6d4);
        color: white;
    }
    
    .avatar-preview {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 16px;
        border: 3px solid var(--azure-light);
    }
    
    .file-input {
        width: 100%;
        padding: 12px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        color: white;
    }
    
    .alert-success {
        background: rgba(16,185,129,0.15);
        border: 1px solid #10b981;
        padding: 14px 20px;
        border-radius: 16px;
        margin-bottom: 20px;
        color: #10b981;
    }
    
    .alert-danger {
        background: rgba(239,68,68,0.15);
        border: 1px solid #ef4444;
        padding: 14px 20px;
        border-radius: 16px;
        margin-bottom: 20px;
        color: #ef4444;
    }
    
    .btn-save {
        width: 100%;
        background: linear-gradient(135deg, #2563eb, #06b6d4);
        border: none;
        padding: 14px;
        border-radius: 40px;
        color: white;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 0 20px rgba(37,99,235,0.4);
    }
    
    .btn-update-resume {
        background: rgba(6,182,212,0.15);
        border: 1px solid rgba(6,182,212,0.3);
        padding: 8px 20px;
        border-radius: 30px;
        color: var(--azure-light);
        cursor: pointer;
        transition: all 0.2s;
        display: inline-block;
    }
    
    .btn-update-resume:hover {
        background: rgba(6,182,212,0.3);
        transform: translateY(-1px);
    }
    
    .name-change-section {
        background: rgba(6,182,212,0.05);
        border: 1px solid rgba(6,182,212,0.15);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 24px;
    }
    
    .cooldown-badge {
        background: rgba(245,158,11,0.15);
        color: #f59e0b;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        display: inline-block;
        margin-left: 12px;
    }
    
    .name-display {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }
    
    .name-form {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .name-form input {
        flex: 1;
        min-width: 200px;
    }
    
    .resume-status-card {
        background: rgba(16,185,129,0.08);
        border-radius: 16px;
        padding: 16px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }
    
    .resume-status-card i {
        font-size: 24px;
        color: #10b981;
    }
    
    .resume-filename {
        font-size: 13px;
        color: var(--white-muted);
        margin-top: 4px;
    }
    
    .upload-card {
        border: 1px dashed rgba(6,182,212,0.3);
        background: rgba(6,182,212,0.03);
        border-radius: 16px;
        padding: 20px;
        text-align: center;
    }
    
    .upload-card i {
        font-size: 36px;
        color: var(--white-dim);
        margin-bottom: 12px;
    }
</style>

<div class="dashboard-main">
    <div style="max-width: 800px; margin: 0 auto;">
        
        <h1 style="margin-bottom: 8px;">My Profile</h1>
        <p class="text-muted-light" style="margin-bottom: 32px;">Update your personal and professional information.</p>
        
        <?php if ($message): ?>
            <div class="alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Avatar Section -->
        <div class="profile-section" style="text-align: center;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-image"></i> Profile Picture</h3>
            <?php 
            $avatar_path = !empty($user['avatar_path']) ? SITE_URL . $user['avatar_path'] : 'https://ui-avatars.com/api/?background=2563eb&color=fff&name=' . urlencode($user['full_name']);
            ?>
            <img src="<?php echo $avatar_path; ?>" id="avatarPreview" class="avatar-preview">
            <form method="POST" action="" enctype="multipart/form-data" id="avatarForm">
                <input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/png,image/webp" style="display: none;">
                <button type="button" class="btn-outline-light" onclick="document.getElementById('avatarInput').click();">
                    <i class="fas fa-camera"></i> Change Photo
                </button>
            </form>
        </div>
        
        <!-- Name Change Section - 3 MONTH COOLDOWN -->
        <div class="name-change-section">
            <h3 style="margin-bottom: 16px;"><i class="fas fa-user-edit"></i> Display Name</h3>
            
            <?php if ($can_change_name): ?>
                <form method="POST" action="" class="name-form">
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    <button type="submit" name="change_name" class="btn-primary-gradient">Save Name</button>
                </form>
                <p class="text-dim" style="font-size: 12px; margin-top: 12px;">
                    <i class="fas fa-info-circle"></i> You can change your name once every 3 months (90 days).
                </p>
            <?php else: ?>
                <div class="name-display">
                    <div>
                        <strong style="font-size: 18px;"><?php echo htmlspecialchars($user['full_name']); ?></strong>
                        <span class="cooldown-badge"><i class="fas fa-clock"></i> Next change available in <?php echo $months_until_change; ?> month<?php echo $months_until_change > 1 ? 's' : ''; ?> (<?php echo $days_until_change; ?> days)</span>
                    </div>
                    <div class="text-dim" style="font-size: 13px;">
                        <i class="fas fa-calendar-alt"></i> Next change on: <?php echo $next_change_date; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="update_profile" value="1">
            
            <!-- Professional Information -->
            <div class="profile-section">
                <h3 style="margin-bottom: 20px;"><i class="fas fa-briefcase"></i> Professional Information</h3>
                
                <div class="form-group">
                    <label>Email Address</label>
                    <div class="readonly-field"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                
                <div class="form-group">
                    <label>Bio / About You</label>
                    <textarea name="bio" rows="3" placeholder="Tell employers about yourself..."><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Education</label>
                    <textarea name="education" rows="3" placeholder="Your educational background..."><?php echo htmlspecialchars($profile['education'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Work Experience</label>
                    <textarea name="experience" rows="4" placeholder="Your work history..."><?php echo htmlspecialchars($profile['experience'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Skills -->
            <div class="profile-section">
                <h3 style="margin-bottom: 20px;"><i class="fas fa-code"></i> Skills</h3>
                <p class="text-muted-light" style="margin-bottom: 16px;">Select your skills to get better job matches.</p>
                <div class="skills-container" id="skillsContainer">
                    <?php foreach ($all_skills as $skill): ?>
                        <span class="skill-tag <?php echo in_array($skill['name'], $user_skills) ? 'selected' : ''; ?>" data-skill="<?php echo htmlspecialchars($skill['name']); ?>">
                            <?php echo htmlspecialchars($skill['name']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="skills[]" id="selectedSkills" value="<?php echo htmlspecialchars($profile['skills'] ?? ''); ?>">
            </div>
            
            <!-- Resume Section -->
            <div class="profile-section">
                <h3 style="margin-bottom: 20px;"><i class="fas fa-file-alt"></i> Resume / CV</h3>
                
                <?php if (!empty($profile['resume_path'])): ?>
                    <div class="resume-status-card">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <i class="fas fa-file-pdf"></i>
                            <div>
                                <strong>Resume Uploaded</strong>
                                <div class="resume-filename">
                                    <?php 
                                    $filename = basename($profile['resume_path']);
                                    echo htmlspecialchars($filename);
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px;">
                            <a href="<?php echo SITE_URL . $profile['resume_path']; ?>" target="_blank" class="btn-outline-light" style="padding: 8px 16px;">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="<?php echo SITE_URL . $profile['resume_path']; ?>" download class="btn-outline-light" style="padding: 8px 16px;">
                                <i class="fas fa-download"></i> Download
                            </a>
                            <button type="button" class="btn-update-resume" id="showResumeUpload">
                                <i class="fas fa-upload"></i> Update Resume
                            </button>
                        </div>
                    </div>
                    
                    <div id="resumeUploadForm" style="display: none; margin-top: 16px;">
                        <div class="upload-card">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p class="text-muted-light" style="margin-bottom: 12px;">Upload a new version to replace your current resume</p>
                            <input type="file" name="resume" id="resumeInput" class="file-input" accept=".pdf,.doc,.docx" style="margin-bottom: 12px;">
                            <button type="button" class="btn-outline-light" id="cancelResumeUpload" style="padding: 8px 20px;">
                                Cancel
                            </button>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <div class="upload-card">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p class="text-muted-light" style="margin-bottom: 16px;">No resume uploaded yet. Upload your resume to start applying for jobs.</p>
                        <input type="file" name="resume" class="file-input" accept=".pdf,.doc,.docx">
                        <p class="text-dim" style="margin-top: 8px; font-size: 12px;">Accepted formats: PDF, DOC, DOCX. Max size: 5MB</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <button type="submit" class="btn-save">
                <i class="fas fa-save"></i> Save Profile Changes
            </button>
        </form>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="index.php" class="btn-outline-light">← Back to Dashboard</a>
        </div>
    </div>
</div>

<script>
    // Avatar upload
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    avatarPreview.src = event.target.result;
                };
                reader.readAsDataURL(this.files[0]);
                this.form.submit();
            }
        });
    }
    
    // Show/Hide Resume Upload Form
    const showResumeBtn = document.getElementById('showResumeUpload');
    const resumeUploadForm = document.getElementById('resumeUploadForm');
    const cancelResumeBtn = document.getElementById('cancelResumeUpload');
    
    if (showResumeBtn) {
        showResumeBtn.addEventListener('click', function() {
            resumeUploadForm.style.display = 'block';
            showResumeBtn.style.display = 'none';
        });
    }
    
    if (cancelResumeBtn) {
        cancelResumeBtn.addEventListener('click', function() {
            resumeUploadForm.style.display = 'none';
            if (showResumeBtn) showResumeBtn.style.display = 'inline-block';
        });
    }
    
    // Auto-submit when file is selected for resume upload
    const resumeInput = document.getElementById('resumeInput');
    if (resumeInput) {
        resumeInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                this.form.submit();
            }
        });
    }
    
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