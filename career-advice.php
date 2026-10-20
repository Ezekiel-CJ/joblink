<?php
$page_title = "Career Advice";
require_once 'includes/config.php';
require_once 'includes/header.php';
?>

<div class="container-premium" style="padding: 60px 0;">
    <div class="text-center">
        <div class="section-tag" style="display: inline-block;">Expert Tips</div>
        <h1>Career Advice</h1>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px; margin: 40px 0;">
        <div style="background: rgba(255,255,255,0.03); border-radius: 24px; padding: 28px;">
            <div style="width: 60px; height: 60px; background: rgba(37,99,235,0.15); border-radius: 18px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;"><i class="fas fa-file-alt" style="font-size: 28px; color: var(--azure-light);"></i></div>
            <h3>How to Write a Winning Resume</h3>
            <p class="text-muted-light">Learn the secrets of creating a resume that gets noticed.</p>
            <a href="blog-post.php?id=2" class="btn-outline-light" style="margin-top: 16px; display: inline-block;">Read More →</a>
        </div>
        <div style="background: rgba(255,255,255,0.03); border-radius: 24px; padding: 28px;">
            <div style="width: 60px; height: 60px; background: rgba(37,99,235,0.15); border-radius: 18px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;"><i class="fas fa-handshake" style="font-size: 28px; color: var(--azure-light);"></i></div>
            <h3>Interview Preparation Guide</h3>
            <p class="text-muted-light">Master common interview questions and make a lasting impression.</p>
            <a href="blog-post.php?id=4" class="btn-outline-light" style="margin-top: 16px; display: inline-block;">Read More →</a>
        </div>
        <div style="background: rgba(255,255,255,0.03); border-radius: 24px; padding: 28px;">
            <div style="width: 60px; height: 60px; background: rgba(37,99,235,0.15); border-radius: 18px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;"><i class="fas fa-chart-line" style="font-size: 28px; color: var(--azure-light);"></i></div>
            <h3>Career Growth Strategies</h3>
            <p class="text-muted-light">Advance your career with proven strategies.</p>
            <a href="blog-post.php?id=5" class="btn-outline-light" style="margin-top: 16px; display: inline-block;">Read More →</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>