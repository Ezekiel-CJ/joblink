        </div>
    </div>
    
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true
        });
        
        // =====================================================
        // PASSWORD SHOW/HIDE TOGGLE - SIMPLE WORKING VERSION
        // =====================================================
        
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded - initializing password toggles');
            
            // Get elements
            const toggleBtn = document.getElementById('togglePassword');
            const passwordField = document.getElementById('password');
            
            if (toggleBtn && passwordField) {
                console.log('Password toggle found');
                toggleBtn.onclick = function() {
                    if (passwordField.type === 'password') {
                        passwordField.type = 'text';
                        this.classList.remove('fa-eye');
                        this.classList.add('fa-eye-slash');
                    } else {
                        passwordField.type = 'password';
                        this.classList.remove('fa-eye-slash');
                        this.classList.add('fa-eye');
                    }
                    return false;
                };
            } else {
                console.log('Password toggle NOT found - check IDs');
            }
            
            // Confirm password toggle
            const toggleConfirmBtn = document.getElementById('toggleConfirmPassword');
            const confirmField = document.getElementById('confirm_password');
            
            if (toggleConfirmBtn && confirmField) {
                console.log('Confirm password toggle found');
                toggleConfirmBtn.onclick = function() {
                    if (confirmField.type === 'password') {
                        confirmField.type = 'text';
                        this.classList.remove('fa-eye');
                        this.classList.add('fa-eye-slash');
                    } else {
                        confirmField.type = 'password';
                        this.classList.remove('fa-eye-slash');
                        this.classList.add('fa-eye');
                    }
                    return false;
                };
            }
        });
    </script>
</body>
</html>