// ============================================================
// validate.js - Validim i formave (frontend)
// ============================================================

function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim());
}

function validatePassword(pass) {
    return pass.length >= 8 && /[A-Z]/.test(pass) && /[0-9]/.test(pass);
}

function validatePhone(phone) {
    return /^(\+3556|06\d)\d{6,7}$/.test(phone.replace(/[\s\-]/g, ''));
}

function showFieldError(inputEl, message) {
    clearFieldError(inputEl);
    var err = document.createElement('div');
    err.className = 'form-error';
    err.textContent = message;
    inputEl.classList.add('is-invalid');
    inputEl.parentNode.appendChild(err);
}

function clearFieldError(inputEl) {
    inputEl.classList.remove('is-invalid');
    var existing = inputEl.parentNode.querySelector('.form-error');
    if (existing) existing.remove();
}

function clearAllErrors(formEl) {
    formEl.querySelectorAll('.form-error').forEach(function(el) { el.remove(); });
    formEl.querySelectorAll('.is-invalid').forEach(function(el) { el.classList.remove('is-invalid'); });
}

document.addEventListener('DOMContentLoaded', function () {

    // ── Login form ─────────────────────────────────────────────
    var loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            clearAllErrors(this);
            var ok    = true;
            var email = document.getElementById('email');
            var pass  = document.getElementById('password');

            if (!email || !validateEmail(email.value)) {
                if (email) showFieldError(email, 'Ju lutemi shkruani një email të vlefshëm.');
                ok = false;
            }
            if (!pass || !pass.value.trim()) {
                if (pass) showFieldError(pass, 'Fjalëkalimi është i detyrueshëm.');
                ok = false;
            }
            if (!ok) e.preventDefault();
        });
    }

    // ── Register form ───────────────────────────────────────────
    var registerForm = document.getElementById('registerForm');
    if (registerForm) {

        // Real-time email uniqueness check
        var emailInput = registerForm.querySelector('#email');
        var emailTimer = null;
        if (emailInput) {
            emailInput.addEventListener('input', function () {
                clearTimeout(emailTimer);
                clearFieldError(this);
                var val = this.value;
                if (!validateEmail(val)) return;
                var el  = this;
                emailTimer = setTimeout(function () {
                    fetch(BASE_URL + '/api/check_email.php?email=' + encodeURIComponent(val), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.data && data.data.exists) {
                            showFieldError(el, 'Ky email është tashmë i regjistruar.');
                        }
                    })
                    .catch(function() {});
                }, 500);
            });
        }

        // Real-time password strength hint
        var passInput = registerForm.querySelector('#password');
        if (passInput) {
            passInput.addEventListener('input', function () {
                clearFieldError(this);
                if (this.value && !validatePassword(this.value)) {
                    showFieldError(this, 'Min. 8 karaktere, 1 shkronjë e madhe, 1 numër.');
                }
            });
        }

        registerForm.addEventListener('submit', function (e) {
            clearAllErrors(this);
            var ok      = true;
            var name    = this.querySelector('#name');
            var email   = this.querySelector('#email');
            var pass    = this.querySelector('#password');
            var confirm = this.querySelector('#confirm_password');
            var phone   = this.querySelector('#phone');

            if (!name || !name.value.trim()) {
                if (name) showFieldError(name, 'Emri i plotë është i detyrueshëm.');
                ok = false;
            }
            if (!email || !validateEmail(email.value)) {
                if (email) showFieldError(email, 'Ju lutemi shkruani një email të vlefshëm.');
                ok = false;
            }
            if (!pass || !validatePassword(pass.value)) {
                if (pass) showFieldError(pass, 'Min. 8 karaktere, 1 shkronjë e madhe, 1 numër.');
                ok = false;
            }
            if (confirm && pass && confirm.value !== pass.value) {
                showFieldError(confirm, 'Fjalëkalimet nuk përputhen.');
                ok = false;
            }
            if (phone && phone.value.trim() && !validatePhone(phone.value)) {
                showFieldError(phone, 'Format telefoni i pavlefshëm (p.sh. +383 44 000 000).');
                ok = false;
            }
            if (!ok) e.preventDefault();
        });
    }
});
