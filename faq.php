<?php
$page_title = "FAQ";
require_once 'includes/config.php';
require_once 'includes/header.php';
?>

<style>
    .faq-container { max-width: 800px; margin: 0 auto; }
    .faq-item {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        margin-bottom: 16px;
        overflow: hidden;
    }
    .faq-question {
        padding: 20px 24px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .faq-question:hover { background: rgba(37,99,235,0.05); }
    .faq-question i { transition: transform 0.3s; }
    .faq-answer {
        padding: 0 24px;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }
    .faq-answer.active { padding: 0 24px 20px; max-height: 500px; }
    .category-tabs {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 40px;
        justify-content: center;
    }
    .category-tab {
        padding: 8px 24px;
        border-radius: 40px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.08);
        color: var(--white-muted);
        cursor: pointer;
    }
    .category-tab.active { background: linear-gradient(135deg, #2563eb, #06b6d4); color: white; }
    .faq-category { display: none; }
    .faq-category.active { display: block; }
</style>

<div class="container-premium" style="padding: 60px 0;">
    <div class="faq-container">
        <div class="text-center" style="margin-bottom: 50px;">
            <div class="section-tag" style="display: inline-block;">Got Questions?</div>
            <h1>Frequently Asked Questions</h1>
        </div>
        
        <div class="category-tabs">
            <button class="category-tab active" data-cat="general">General</button>
            <button class="category-tab" data-cat="jobseekers">Job Seekers</button>
            <button class="category-tab" data-cat="employers">Employers</button>
            <button class="category-tab" data-cat="account">Account</button>
        </div>
        
        <div id="general" class="faq-category active">
            <div class="faq-item"><div class="faq-question"><h3>What is JobLinkLocal?</h3><i class="fas fa-chevron-down"></i></div><div class="faq-answer"><p class="text-muted-light">A community-based employment platform built for LGUs, local businesses, and job seekers.</p></div></div>
            <div class="faq-item"><div class="faq-question"><h3>Is JobLinkLocal free?</h3><i class="fas fa-chevron-down"></i></div><div class="faq-answer"><p class="text-muted-light">Yes! Free for job seekers. Employers can post jobs with basic features for free.</p></div></div>
            <div class="faq-item"><div class="faq-question"><h3>How does LGU verification work?</h3><i class="fas fa-chevron-down"></i></div><div class="faq-answer"><p class="text-muted-light">All employers go through verification by LGU officers to ensure legitimacy.</p></div></div>
        </div>
        
        <div id="jobseekers" class="faq-category">
            <div class="faq-item"><div class="faq-question"><h3>How do I create a job seeker account?</h3><i class="fas fa-chevron-down"></i></div><div class="faq-answer"><p class="text-muted-light">Click Register, select "Job Seeker", fill in your details, and verify your email.</p></div></div>
            <div class="faq-item"><div class="faq-question"><h3>How does skill-based matching work?</h3><i class="fas fa-chevron-down"></i></div><div class="faq-answer"><p class="text-muted-light">Our system analyzes your skills to recommend jobs that best match your profile.</p></div></div>
            <div class="faq-item"><div class="faq-question"><h3>Can I save jobs for later?</h3><i class="fas fa-chevron-down"></i></div><div class="faq-answer"><p class="text-muted-light">Yes! Click the "Save" button on any job to save it to your dashboard.</p></div></div>
        </div>
        
        <div id="employers" class="faq-category">
            <div class="faq-item"><div class="faq-question"><h3>How do I post a job?</h3><i class="fas fa-chevron-down"></i></div><div class="faq-answer"><p class="text-muted-light">Register as employer, complete your business profile, then go to "Post a Job".</p></div></div>
            <div class="faq-item"><div class="faq-question"><h3>How long does job approval take?</h3><i class="fas fa-chevron-down"></i></div><div class="faq-answer"><p class="text-muted-light">LGU officers typically review within 24-48 hours.</p></div></div>
            <div class="faq-item"><div class="faq-question"><h3>How do I verify my business?</h3><i class="fas fa-chevron-down"></i></div><div class="faq-answer"><p class="text-muted-light">Submit business permit through the Verification section in your dashboard.</p></div></div>
        </div>
        
        <div id="account" class="faq-category">
            <div class="faq-item"><div class="faq-question"><h3>Forgot password?</h3><i class="fas fa-chevron-down"></i></div><div class="faq-answer"><p class="text-muted-light">Click "Forgot Password" on login page to reset via email.</p></div></div>
            <div class="faq-item"><div class="faq-question"><h3>How do I update my profile?</h3><i class="fas fa-chevron-down"></i></div><div class="faq-answer"><p class="text-muted-light">Log in and go to "My Profile" in your dashboard.</p></div></div>
            <div class="faq-item"><div class="faq-question"><h3>Is my data secure?</h3><i class="fas fa-chevron-down"></i></div><div class="faq-answer"><p class="text-muted-light">Yes, we comply with Data Privacy Act of 2012 (RA 10173).</p></div></div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.faq-question').forEach(q => {
        q.addEventListener('click', () => {
            const answer = q.nextElementSibling;
            const icon = q.querySelector('i');
            answer.classList.toggle('active');
            icon.style.transform = answer.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0)';
        });
    });
    document.querySelectorAll('.category-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            document.querySelectorAll('.faq-category').forEach(c => c.classList.remove('active'));
            document.getElementById(tab.dataset.cat).classList.add('active');
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>