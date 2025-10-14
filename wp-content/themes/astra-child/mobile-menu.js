/**
 * Mobile Hamburger Menu Handler
 */
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const navWrapper = document.querySelector('.custom-header .nav-wrapper');

    if (hamburger && navWrapper) {
        hamburger.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('active');
            navWrapper.classList.toggle('active');

            // Prevent body scroll when menu is open
            if (navWrapper.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!navWrapper.contains(e.target) && !hamburger.contains(e.target)) {
                hamburger.classList.remove('active');
                navWrapper.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // Close menu when clicking nav links
        const navLinks = navWrapper.querySelectorAll('nav a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                hamburger.classList.remove('active');
                navWrapper.classList.remove('active');
                document.body.style.overflow = '';
            });
        });
    }
});
