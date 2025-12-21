// Happy Turtle Splash Screen
(function() {
    'use strict';

    // Function to show splash screen
    function showSplashScreen() {
        // Remove any existing splash
        const existingSplash = document.getElementById('htb-splash');
        if (existingSplash) {
            existingSplash.remove();
        }

        // Create splash screen
        const splashHTML = `
            <div class="htb-splash-screen" id="htb-splash">
                <div class="htb-splash-content">
                    <img src="${htbData.themeUrl}/assets/turtle-logo-transparent.png" alt="Happy Turtle Logo" class="htb-splash-logo">
                    <h1 class="htb-splash-title">Happy Turtle Processing</h1>
                    <p class="htb-splash-tagline">Where Chill Meets Craft — Turning Bud Into Better</p>
                    <div class="htb-splash-dots">
                        <div class="htb-splash-dot"></div>
                        <div class="htb-splash-dot"></div>
                        <div class="htb-splash-dot"></div>
                    </div>
                </div>
            </div>
        `;

        // Insert splash screen at beginning of body
        document.body.insertAdjacentHTML('afterbegin', splashHTML);

        // Prevent any clicks on splash from bubbling
        const splashEl = document.getElementById('htb-splash');
        if (splashEl) {
            splashEl.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
            });
        }

        // Remove splash after animation completes
        setTimeout(function() {
            const splash = document.getElementById('htb-splash');
            if (splash) {
                splash.style.opacity = '0';
                splash.style.pointerEvents = 'none';
                setTimeout(function() {
                    splash.remove();
                }, 1000);
            }
        }, 4500);
    }

    // Expose function globally
    window.htbShowSplash = showSplashScreen;

    // Helper function to check if age gate cookie exists
    function hasAgeGateCookie() {
        const cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
            const cookie = cookies[i].trim();
            // Age Gate plugin uses 'age_gate' or 'age-gate' cookie
            if (cookie.startsWith('age_gate=') || cookie.startsWith('age-gate=')) {
                return true;
            }
        }
        return false;
    }

    // Auto-show splash on page load
    document.addEventListener('DOMContentLoaded', function() {
        const isLoginPage = document.body.classList.contains('login');

        // Always show on login page
        if (isLoginPage) {
            showSplashScreen();
            return;
        }

        // IMPORTANT: Only show splash if age has been verified
        // This ensures age gate appears first
        if (!hasAgeGateCookie()) {
            // Age not verified yet, wait for verification
            // Set up a listener for when age gate is verified
            const checkAgeInterval = setInterval(function() {
                if (hasAgeGateCookie()) {
                    clearInterval(checkAgeInterval);
                    // Small delay to ensure age gate has fully closed
                    setTimeout(function() {
                        const splashShown = sessionStorage.getItem('htb-splash-shown');
                        if (!splashShown) {
                            showSplashScreen();
                            sessionStorage.setItem('htb-splash-shown', 'true');
                        }
                    }, 500);
                }
            }, 100);

            // Clear interval after 10 seconds to prevent indefinite checking
            setTimeout(function() {
                clearInterval(checkAgeInterval);
            }, 10000);
            return;
        }

        // Age verified, proceed with normal splash logic
        const splashShown = sessionStorage.getItem('htb-splash-shown');

        // Check if this is a hard refresh
        const navigation = performance.getEntriesByType('navigation')[0];
        const isHardRefresh = navigation && navigation.type === 'reload';

        if (!splashShown || isHardRefresh) {
            showSplashScreen();
            sessionStorage.setItem('htb-splash-shown', 'true');
        }
    });
})();
