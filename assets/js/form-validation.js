/**
 * PPDB SMP - Form Validation & UI Helpers
 * Version: 1.0
 */

// ================================
// REAL-TIME FORM VALIDATION
// ================================

const FormValidator = {
    // Validation rules
    rules: {
        required: (value) => value.trim() !== '',
        email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
        phone: (value) => /^(\+62|62|0)[0-9]{9,12}$/.test(value.replace(/[\s-]/g, '')),
        nik: (value) => /^[0-9]{16}$/.test(value),
        nisn: (value) => /^[0-9]{10}$/.test(value),
        minLength: (value, length) => value.length >= length,
        maxLength: (value, length) => value.length <= length,
        match: (value, targetId) => value === document.getElementById(targetId)?.value
    },

    // Error messages
    messages: {
        required: 'Field ini wajib diisi',
        email: 'Format email tidak valid',
        phone: 'Format nomor telepon tidak valid',
        nik: 'NIK harus 16 digit angka',
        nisn: 'NISN harus 10 digit angka',
        minLength: 'Minimal {0} karakter',
        maxLength: 'Maksimal {0} karakter',
        match: 'Tidak cocok dengan {0}'
    },

    // Initialize validation on a form
    init(formSelector, options = {}) {
        const form = document.querySelector(formSelector);
        if (!form) return;

        const inputs = form.querySelectorAll('input, select, textarea');

        inputs.forEach(input => {
            // Real-time validation on blur
            input.addEventListener('blur', () => this.validateField(input));

            // Remove error on focus
            input.addEventListener('focus', () => {
                input.classList.remove('is-invalid');
            });

            // Real-time validation while typing (debounced)
            if (options.validateOnInput) {
                let timeout;
                input.addEventListener('input', () => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => this.validateField(input), 300);
                });
            }
        });

        // Validate all on submit
        form.addEventListener('submit', (e) => {
            let isValid = true;
            inputs.forEach(input => {
                if (!this.validateField(input)) {
                    isValid = false;
                }
            });

            if (!isValid) {
                e.preventDefault();
                // Focus first invalid input
                form.querySelector('.is-invalid')?.focus();
            }
        });
    },

    // Validate a single field
    validateField(input) {
        const rules = input.dataset.validate?.split('|') || [];
        const value = input.value;

        // Check required
        if (input.hasAttribute('required') || rules.includes('required')) {
            if (!this.rules.required(value)) {
                this.showError(input, this.messages.required);
                return false;
            }
        }

        // Skip other validations if empty and not required
        if (!value && !input.hasAttribute('required')) {
            this.showSuccess(input);
            return true;
        }

        // Check other rules
        for (const rule of rules) {
            if (rule === 'required') continue;

            const [ruleName, param] = rule.split(':');

            if (this.rules[ruleName]) {
                if (!this.rules[ruleName](value, param)) {
                    let message = this.messages[ruleName];
                    if (param) message = message.replace('{0}', param);
                    this.showError(input, message);
                    return false;
                }
            }
        }

        this.showSuccess(input);
        return true;
    },

    showError(input, message) {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');

        // Update or create feedback element
        let feedback = input.parentElement.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            input.parentElement.appendChild(feedback);
        }
        feedback.textContent = message;
    },

    showSuccess(input) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
    }
};

// ================================
// PASSWORD STRENGTH INDICATOR
// ================================

const PasswordStrength = {
    init(inputSelector, indicatorSelector) {
        const input = document.querySelector(inputSelector);
        const indicator = document.querySelector(indicatorSelector);

        if (!input || !indicator) return;

        input.addEventListener('input', () => {
            const strength = this.calculate(input.value);
            this.updateIndicator(indicator, strength);
        });
    },

    calculate(password) {
        let score = 0;

        if (password.length >= 8) score++;
        if (password.length >= 12) score++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^a-zA-Z0-9]/.test(password)) score++;

        if (score <= 1) return 'weak';
        if (score <= 2) return 'fair';
        if (score <= 3) return 'good';
        return 'strong';
    },

    updateIndicator(indicator, strength) {
        const bar = indicator.querySelector('.password-strength-bar') || indicator;
        bar.className = 'password-strength-bar ' + strength;
    }
};

// ================================
// TOAST NOTIFICATIONS
// ================================

const Toast = {
    container: null,

    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
    },

    show(message, type = 'info', duration = 4000) {
        this.init();

        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.innerHTML = `
            <i class="fas fa-${this.getIcon(type)}"></i>
            <span>${message}</span>
            <button class="btn-close ms-auto" onclick="this.parentElement.remove()"></button>
        `;

        this.container.appendChild(toast);

        // Auto remove
        setTimeout(() => {
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    getIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'times-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    },

    success(message) { this.show(message, 'success'); },
    error(message) { this.show(message, 'error'); },
    warning(message) { this.show(message, 'warning'); },
    info(message) { this.show(message, 'info'); }
};

// ================================
// LOADING STATES
// ================================

const Loading = {
    // Show loading on button
    button(btn, loading = true) {
        if (loading) {
            btn.classList.add('btn-loading');
            btn.disabled = true;
            btn.dataset.originalText = btn.innerHTML;
            btn.innerHTML = '<span class="me-2">Memproses...</span>';
        } else {
            btn.classList.remove('btn-loading');
            btn.disabled = false;
            btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
        }
    },

    // Show skeleton loading
    skeleton(container, show = true) {
        if (show) {
            container.dataset.originalContent = container.innerHTML;
            container.innerHTML = `
                <div class="skeleton skeleton-text full"></div>
                <div class="skeleton skeleton-text medium"></div>
                <div class="skeleton skeleton-text short"></div>
            `;
        } else {
            container.innerHTML = container.dataset.originalContent || '';
        }
    }
};

// ================================
// SCROLL ANIMATIONS
// ================================

const ScrollAnimations = {
    init() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('[data-animate]').forEach(el => {
            el.style.opacity = '0';
            observer.observe(el);
        });
    }
};

// ================================
// AUTO INIT
// ================================

document.addEventListener('DOMContentLoaded', () => {
    // Init scroll animations
    ScrollAnimations.init();

    // Auto-init form validation for forms with data-validate attribute
    document.querySelectorAll('form[data-validate]').forEach(form => {
        FormValidator.init('#' + form.id, { validateOnInput: true });
    });
});

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { FormValidator, PasswordStrength, Toast, Loading, ScrollAnimations };
}
