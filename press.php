<?php
$page_title = "Press";
require_once 'includes/config.php';
require_once 'includes/header.php';
?>

<div class="container-premium" style="padding: 60px 0;">
    <div class="text-center">
        <div class="section-tag" style="display: inline-block;">In the News</div>
        <h1>Press & Media</h1>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px; margin: 50px 0;">
        <div style="background: rgba(255,255,255,0.03); border-radius: 24px; overflow: hidden;">
            <div style="height: 160px; background: linear-gradient(135deg, #1e293b, #0f172a); display: flex; align-items: center; justify-content: center;"><i class="fas fa-newspaper" style="font-size: 48px; color: var(--azure-light);"></i></div>
            <div style="padding: 24px;"><h3>JobLinkLocal Launches Community Platform</h3><p class="text-muted-light">April 15, 2026</p><a href="#" class="btn-outline-light" style="margin-top: 16px; display: inline-block;">Read Press Release →</a></div>
        </div>
        <div style="background: rgba(255,255,255,0.03); border-radius: 24px; overflow: hidden;">
            <div style="height: 160px; background: linear-gradient(135deg, #1e293b, #0f172a); display: flex; align-items: center; justify-content: center;"><i class="fas fa-tv" style="font-size: 48px; color: var(--azure-light);"></i></div>
            <div style="padding: 24px;"><h3>Media Kit</h3><p class="text-muted-light">Download logos and brand assets</p><a href="#" class="btn-outline-light" style="margin-top: 16px; display: inline-block;">Download →</a></div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>