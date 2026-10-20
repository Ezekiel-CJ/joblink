// =====================================================
// JOBLINKLOCAL - MAIN JAVASCRIPT
// Premium Interactions | Production Ready
// =====================================================

// Wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    
    // =====================================================
    // MOBILE MENU TOGGLE
    // =====================================================
    const mobileToggle = document.getElementById('mobileToggle');
    const navLinks = document.getElementById('navLinks');
    const navActions = document.getElementById('navActions');
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            if (navLinks) navLinks.classList.toggle('mobile-open');
            if (navActions) navActions.classList.toggle('mobile-open');
        });
    }
    
    // =====================================================
    // PASSWORD SHOW/HIDE TOGGLE
    // =====================================================
    const togglePassword = document.getElementById('togglePassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (togglePassword && password) {
        togglePassword.addEventListener('click', function() {
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
            this.classList.toggle('fa-eye-slash');
        });
    }
    
    if (toggleConfirmPassword && confirmPassword) {
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPassword.type === 'password' ? 'text' : 'password';
            confirmPassword.type = type;
            this.classList.toggle('fa-eye-slash');
        });
    }
    
    // =====================================================
    // FORM VALIDATION
    // =====================================================
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const password = this.querySelector('#password');
            const confirm = this.querySelector('#confirm_password');
            const terms = this.querySelector('#terms_accepted');
            
            if (password && confirm && password.value !== confirm.value) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (terms && !terms.checked) {
                e.preventDefault();
                alert('Please accept the Terms & Conditions');
                return false;
            }
        });
    });
    
    // =====================================================
    // ROLE SELECTION (REGISTER PAGE)
    // =====================================================
    const roleOptions = document.querySelectorAll('.role-option');
    const selectedRoleInput = document.getElementById('selectedRole');
    
    if (roleOptions.length > 0) {
        roleOptions.forEach(option => {
            option.addEventListener('click', function() {
                roleOptions.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                if (selectedRoleInput) {
                    selectedRoleInput.value = this.dataset.role;
                }
            });
        });
    }
    
    // =====================================================
    // SKILL SELECTION (PROFILE PAGE)
    // =====================================================
    const skillTags = document.querySelectorAll('.skill-tag');
    const selectedSkillsInput = document.getElementById('selectedSkills');
    
    if (skillTags.length > 0 && selectedSkillsInput) {
        skillTags.forEach(tag => {
            tag.addEventListener('click', function() {
                this.classList.toggle('selected');
                const selected = [];
                document.querySelectorAll('.skill-tag.selected').forEach(selectedTag => {
                    selected.push(selectedTag.dataset.skill);
                });
                selectedSkillsInput.value = selected.join(',');
            });
        });
    }
    
    // =====================================================
    // FAQ ACCORDION
    // =====================================================
    const faqQuestions = document.querySelectorAll('.faq-question');
    
    if (faqQuestions.length > 0) {
        faqQuestions.forEach(question => {
            question.addEventListener('click', function() {
                const answer = this.nextElementSibling;
                const icon = this.querySelector('i');
                answer.classList.toggle('active');
                if (icon) {
                    icon.style.transform = answer.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0)';
                }
            });
        });
    }
    
    // =====================================================
    // FAQ CATEGORY TABS
    // =====================================================
    const categoryTabs = document.querySelectorAll('.category-tab');
    
    if (categoryTabs.length > 0) {
        categoryTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                categoryTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                const category = this.dataset.cat;
                document.querySelectorAll('.faq-category').forEach(c => {
                    c.classList.remove('active');
                    if (c.id === category) c.classList.add('active');
                });
            });
        });
    }
    
    // =====================================================
    // JOB FILTER (JOBS PAGE)
    // =====================================================
    const filterForm = document.getElementById('filterForm');
    const clearFilters = document.getElementById('clearFilters');
    
    if (clearFilters) {
        clearFilters.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'jobs.php';
        });
    }
    
    // =====================================================
    // DASHBOARD SIDEBAR MOBILE TOGGLE
    // =====================================================
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.dashboard-sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }
    
    // =====================================================
    // ANIMATED COUNTERS (LANDING PAGE)
    // =====================================================
    const counters = document.querySelectorAll('.stat-number');
    
    if (counters.length > 0) {
        const animateCounter = (counter) => {
            const target = parseInt(counter.innerText);
            let current = 0;
            const increment = target / 50;
            const updateCounter = () => {
                current += increment;
                if (current < target) {
                    counter.innerText = Math.floor(current) + '+';
                    setTimeout(updateCounter, 30);
                } else {
                    counter.innerText = target + '+';
                }
            };
            updateCounter();
        };
        
        // Trigger when in viewport
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        counters.forEach(counter => observer.observe(counter));
    }
    
    // =====================================================
    // SCROLL REVEAL ANIMATIONS
    // =====================================================
    const revealElements = document.querySelectorAll('.fade-in-up');
    
    if (revealElements.length > 0) {
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        revealElements.forEach(el => revealObserver.observe(el));
    }
    
    // =====================================================
    // NAVBAR SCROLL EFFECT
    // =====================================================
    const navbar = document.getElementById('premiumNav');
    
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }
    
    // =====================================================
    // SMOOTH SCROLL FOR ANCHOR LINKS
    // =====================================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
    
    // =====================================================
    // TOOLTIP INITIALIZATION
    // =====================================================
    const tooltips = document.querySelectorAll('[data-tooltip]');
    if (tooltips.length > 0) {
        tooltips.forEach(el => {
            el.addEventListener('mouseenter', function(e) {
                const tooltip = document.createElement('div');
                tooltip.className = 'custom-tooltip';
                tooltip.innerText = this.dataset.tooltip;
                tooltip.style.cssText = `
                    position: absolute;
                    background: #1e293b;
                    color: white;
                    padding: 4px 12px;
                    border-radius: 8px;
                    font-size: 12px;
                    z-index: 1000;
                    white-space: nowrap;
                `;
                document.body.appendChild(tooltip);
                const rect = this.getBoundingClientRect();
                tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
                
                this.addEventListener('mouseleave', function() {
                    tooltip.remove();
                }, { once: true });
            });
        });
    }
});