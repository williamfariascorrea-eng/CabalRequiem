document.addEventListener('DOMContentLoaded', function() {
    const splash = document.getElementById('splash');
    const mobileToggle = document.getElementById('mobileToggle');
    const navGroup = document.querySelector('.site-header__nav-group');
    const loginForm = document.getElementById('loginForm');

    if (splash) {
        setTimeout(function() {
            splash.classList.add('site-splash--hidden');
        }, 1500);
    }

    if (mobileToggle && navGroup) {
        mobileToggle.addEventListener('click', function() {
            this.classList.toggle('is-active');
            navGroup.classList.toggle('is-open');
        });

        document.addEventListener('click', function(e) {
            if (!navGroup.contains(e.target) && !mobileToggle.contains(e.target)) {
                mobileToggle.classList.remove('is-active');
                navGroup.classList.remove('is-open');
            }
        });
    }

    const dropdownGroups = document.querySelectorAll('.primary-nav__group');
    dropdownGroups.forEach(function(group) {
        const link = group.querySelector('.primary-nav__link');
        if (link) {
            link.addEventListener('click', function(e) {
                if (window.innerWidth <= 960) {
                    e.preventDefault();
                    group.classList.toggle('is-open');
                }
            });
        }
    });

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            
            if (!username.value || !password.value) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos.');
                return false;
            }
        });
    }
    
    const formInputs = document.querySelectorAll('.form-input');
    formInputs.forEach(function(input) {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('is-focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('is-focused');
        });
    });
});