// Simple login form enhancements with password toggle
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const togglePassword = document.querySelector('.toggle-password');
    const passwordInput = document.querySelector('#password');
    const showText = document.querySelector('.show-text');
    const hideText = document.querySelector('.hide-text');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle show/hide text
            if (type === 'text') {
                showText.style.display = 'none';
                hideText.style.display = 'inline';
            } else {
                showText.style.display = 'inline';
                hideText.style.display = 'none';
            }
        });
    }
    
    // Add basic form validation
    const form = document.querySelector('form');
    const inputs = document.querySelectorAll('.school-input');
    
    if (form) {
        form.addEventListener('submit', function(event) {
            let valid = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    valid = false;
                    input.style.borderColor = '#dc3545';
                } else {
                    input.style.borderColor = '#4CAF50';
                }
            });
            
            if (!valid) {
                event.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    }
    
    // Clear error styles when user starts typing
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.value.trim()) {
                this.style.borderColor = '#4CAF50';
            }
        });
    });
});