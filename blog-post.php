<?php
$page_title = "Blog";
require_once 'includes/config.php';
require_once 'includes/header.php';
?>

<style>
    .blog-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px; margin: 40px 0; }
    .blog-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 24px;
        overflow: hidden;
        transition: all 0.3s;
    }
    .blog-card:hover { transform: translateY(-5px); background: rgba(37,99,235,0.05); }
    .blog-image { height: 200px; background: linear-gradient(135deg, #1e293b, #0f172a); display: flex; align-items: center; justify-content: center; }
    .blog-image i { font-size: 48px; color: var(--azure-light); }
    .blog-content { padding: 24px; }
    .blog-category { display: inline-block; background: rgba(6,182,212,0.15); padding: 4px 12px; border-radius: 20px; font-size: 11px; margin-bottom: 12px; }
</style>

<div class="container-premium" style="padding: 60px 0;">
    <div class="text-center">
        <div class="section-tag" style="display: inline-block;">Latest Insights</div>
        <h1>Career Insights & Resources</h1>
    </div>
    
    <div class="blog-grid">
        <div class="blog-card"><div class="blog-image"><i class="fas fa-rocket"></i></div><div class="blog-content"><span class="blog-category">Featured</span><h3>10 Tips for Landing Your Dream Job</h3><p class="text-muted-light">Proven strategies to stand out in your job search.</p><a href="blog-post.php?id=1" class="btn-outline-light" style="margin-top: 16px; display: inline-block;">Read More →</a></div></div>
        <div class="blog-card"><div class="blog-image"><i class="fas fa-file-alt"></i></div><div class="blog-content"><span class="blog-category">Resume Tips</span><h3>How to Write a Resume That Gets Noticed</h3><p class="text-muted-light">Key elements of a standout resume.</p><a href="blog-post.php?id=2" class="btn-outline-light" style="margin-top: 16px; display: inline-block;">Read More →</a></div></div>
        <div class="blog-card"><div class="blog-image"><i class="fas fa-chart-line"></i></div><div class="blog-content"><span class="blog-category">Career Advice</span><h3>Top 5 In-Demand Skills for 2026</h3><p class="text-muted-light">Stay ahead with the most sought-after skills.</p><a href="blog-post.php?id=3" class="btn-outline-light" style="margin-top: 16px; display: inline-block;">Read More →</a></div></div>
        <div class="blog-card"><div class="blog-image"><i class="fas fa-handshake"></i></div><div class="blog-content"><span class="blog-category">Interview Tips</span><h3>Mastering the Virtual Interview</h3><p class="text-muted-light">Ace your next remote job interview.</p><a href="blog-post.php?id=4" class="btn-outline-light" style="margin-top: 16px; display: inline-block;">Read More →</a></div></div>
        <div class="blog-card"><div class="blog-image"><i class="fas fa-graduation-cap"></i></div><div class="blog-content"><span class="blog-category">Career Growth</span><h3>From Entry-Level to Executive</h3><p class="text-muted-light">Map out your career progression.</p><a href="blog-post.php?id=5" class="btn-outline-light" style="margin-top: 16px; display: inline-block;">Read More →</a></div></div>
        <div class="blog-card"><div class="blog-image"><i class="fas fa-balance-scale"></i></div><div class="blog-content"><span class="blog-category">Work-Life Balance</span><h3>Finding Balance: Remote Work Best Practices</h3><p class="text-muted-light">Maintain productivity and well-being.</p><a href="blog-post.php?id=6" class="btn-outline-light" style="margin-top: 16px; display: inline-block;">Read More →</a></div></div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>