// Happy Turtle Splash Screen
(function() {
    'use strict';

    // Typewriter effect function
    function typeWriter(element, text, speed, callback) {
        let i = 0;
        element.textContent = '';
        element.style.visibility = 'visible';

        function type() {
            if (i < text.length) {
                element.textContent += text.charAt(i);
                i++;
                setTimeout(type, speed);
            } else if (callback) {
                callback();
            }
        }
        type();
    }

    // Function to show splash screen
    function showSplashScreen() {
        // Remove any existing splash
        const existingSplash = document.getElementById('htb-splash');
        if (existingSplash) {
            existingSplash.remove();
        }

        // Create splash screen with ATOM video and typewriter text
        const splashHTML = `
            <div class="htb-splash-screen" id="htb-splash">
                <div class="htb-splash-content">
                    <div class="htb-splash-video-container">
                        <video class="htb-splash-video" autoplay loop muted playsinline>
                            <source src="${htbData.themeUrl}/assets/images/ATOM.mp4" type="video/mp4">
                        </video>
                    </div>
                    <h1 class="htb-splash-title" id="htb-splash-title"></h1>
                    <p class="htb-splash-tagline" id="htb-splash-tagline"></p>
                </div>
            </div>
        `;

        // Insert splash screen at beginning of body
        document.body.insertAdjacentHTML('afterbegin', splashHTML);

        // Start typewriter effect after a short delay for video to load
        setTimeout(function() {
            const titleEl = document.getElementById('htb-splash-title');
            const taglineEl = document.getElementById('htb-splash-tagline');

            if (titleEl && taglineEl) {
                typeWriter(titleEl, 'Happy Turtle Processing', 80, function() {
                    setTimeout(function() {
                        typeWriter(taglineEl, 'Where Chill Meets Craft — Turning Bud Into Better', 50);
                    }, 300);
                });
            }
        }, 500);

        // Prevent clicks on splash from bubbling
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
        }, 7000);
    }

    // Expose function globally for button click
    window.htbShowSplash = showSplashScreen;

    // Flag to prevent double-firing
    let ageGateSplashTriggered = false;

    // Show splash ONLY after age gate verification
    function onAgeGatePassed() {
        if (ageGateSplashTriggered) return;
        ageGateSplashTriggered = true;
        showSplashScreen();
    }

    // Listen for age gate events
    window.addEventListener('age_gate_passed', onAgeGatePassed);
    window.addEventListener('agegatepassed', onAgeGatePassed);

    // Show on login page only
    document.addEventListener('DOMContentLoaded', function() {
        if (document.body.classList.contains('login')) {
            showSplashScreen();
        }
    });
})();
