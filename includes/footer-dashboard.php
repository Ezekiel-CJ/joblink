</div> <!-- Close dashboard-main -->

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: true });
    
    // Mobile menu toggle
    const menuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('dashboardSidebar');
    
    if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    }
    
    // Logout confirmation
    function confirmLogout() {
        if (confirm('⚠️ Are you sure you want to logout?\n\nYou will be redirected to the login page.')) {
            window.location.href = '<?php echo SITE_URL; ?>logout.php';
        }
        return false;
    }
</script>

    <script>
// =====================================================
// RESPONSIVE SIDEBAR TOGGLE
// =====================================================
(function() {
    const menuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('dashboardSidebar');
    
    // Create overlay element
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay && sidebar) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }
    
    if (menuBtn && sidebar) {
        // Open sidebar
        menuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
        });
        
        // Close sidebar when clicking overlay
        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
        
        // Close sidebar on window resize if open and screen becomes desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 1024) {
                sidebar.classList.remove('open');
                if (overlay) overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
        
        // Close sidebar when clicking escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
                if (overlay) overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }
})();

// Handle touch events for mobile swipe
let touchStartX = 0;
let touchEndX = 0;

document.addEventListener('touchstart', function(e) {
    touchStartX = e.changedTouches[0].screenX;
});

document.addEventListener('touchend', function(e) {
    touchEndX = e.changedTouches[0].screenX;
    const sidebar = document.getElementById('dashboardSidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (window.innerWidth <= 1024 && sidebar) {
        // Swipe right to open
        if (touchEndX - touchStartX > 80 && touchStartX < 50) {
            sidebar.classList.add('open');
            if (overlay) overlay.classList.add('active');
        }
        // Swipe left to close
        if (touchStartX - touchEndX > 80 && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('active');
        }
    }
});

// Navbar scroll effect
window.addEventListener('scroll', function() {
    const nav = document.getElementById('premiumNav');
    if (nav) {
        if (window.scrollY > 50) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
    }
});
</script>
</body>
</html>