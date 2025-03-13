// Main Application JavaScript

// Toast Notification System
const Toast = {
    show(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type} slide-in`;
        toast.setAttribute('role', 'alert');
        toast.textContent = message;

        const container = document.getElementById('toast-container');
        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.remove('slide-in');
            toast.classList.add('slide-out');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
};

// Form Validation
const FormValidation = {
    validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },

    validatePassword(password) {
        return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/.test(password);
    },

    showError(input, message) {
        const formGroup = input.closest('.form-group');
        const error = formGroup.querySelector('.error-message') || document.createElement('div');
        error.className = 'error-message text-error slide-in';
        error.textContent = message;

        if (!formGroup.querySelector('.error-message')) {
            formGroup.appendChild(error);
        }

        input.classList.add('error');
    },

    clearError(input) {
        const formGroup = input.closest('.form-group');
        const error = formGroup.querySelector('.error-message');

        if (error) {
            error.remove();
        }

        input.classList.remove('error');
    }
};

// Mobile Menu
document.addEventListener('DOMContentLoaded', () => {
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navLinks = document.querySelector('.nav-links');

    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            mobileMenuToggle.classList.toggle('active');
        });
    }

    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.nav-links') && !e.target.closest('.mobile-menu-toggle')) {
            navLinks.classList.remove('active');
            mobileMenuToggle.classList.remove('active');
        }
    });
});

// Form Submissions
document.addEventListener('submit', (e) => {
    if (e.target.classList.contains('needs-validation')) {
        e.preventDefault();

        const form = e.target;
        let isValid = true;

        // Clear previous errors
        form.querySelectorAll('.error-message').forEach(error => error.remove());
        form.querySelectorAll('.error').forEach(input => input.classList.remove('error'));

        // Validate required fields
        form.querySelectorAll('[required]').forEach(input => {
            if (!input.value.trim()) {
                FormValidation.showError(input, 'This field is required');
                isValid = false;
            }
        });

        // Validate email fields
        form.querySelectorAll('[type="email"]').forEach(input => {
            if (input.value && !FormValidation.validateEmail(input.value)) {
                FormValidation.showError(input, 'Please enter a valid email address');
                isValid = false;
            }
        });

        // Validate password fields
        form.querySelectorAll('[type="password"]').forEach(input => {
            if (input.dataset.validatePassword && !FormValidation.validatePassword(input.value)) {
                FormValidation.showError(input, 'Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character');
                isValid = false;
            }
        });

        // Submit form if valid
        if (isValid) {
            const submitButton = form.querySelector('[type="submit"]');
            submitButton.disabled = true;
            submitButton.classList.add('loading');

            // Submit form data
            fetch(form.action, {
                method: form.method,
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Toast.show(data.message, 'success');
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                    } else {
                        Toast.show(data.message || 'An error occurred', 'error');
                    }
                })
                .catch(error => {
                    Toast.show('An error occurred', 'error');
                    console.error('Form submission error:', error);
                })
                .finally(() => {
                    submitButton.disabled = false;
                    submitButton.classList.remove('loading');
                });
        }
    }
});

// Dropdown Menus
document.addEventListener('click', (e) => {
    const dropdownToggle = e.target.closest('.dropdown-toggle');

    if (dropdownToggle) {
        const dropdown = dropdownToggle.nextElementSibling;
        dropdown.classList.toggle('active');

        // Close other dropdowns
        document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
            if (menu !== dropdown) {
                menu.classList.remove('active');
            }
        });
    } else if (!e.target.closest('.dropdown-menu')) {
        // Close all dropdowns when clicking outside
        document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
            menu.classList.remove('active');
        });
    }
});

// Keyboard Navigation
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        // Close dropdowns
        document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
            menu.classList.remove('active');
        });

        // Close mobile menu
        const navLinks = document.querySelector('.nav-links');
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        navLinks.classList.remove('active');
        mobileMenuToggle.classList.remove('active');
    }
}); 