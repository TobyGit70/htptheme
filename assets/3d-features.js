// Happy Turtle 3D Features
(function() {
    'use strict';

    // Floating 3D Turtle Background in Services Section
    function addFloatingTurtle() {
        const servicesSection = document.querySelector('.htb-services-section');
        if (!servicesSection) return;

        const floatingTurtle = document.createElement('div');
        floatingTurtle.className = 'htb-floating-turtle';
        floatingTurtle.innerHTML = `
            <model-viewer
                src="${htbData.themeUrl}/assets/images/Tropical_Turtle_Art_0928145704_texture.glb"
                alt="Floating Turtle"
                auto-rotate
                rotation-per-second="20deg"
                class="htb-floating-model"
                style="width:200px;height:200px;opacity:0.3;position:absolute;top:20%;right:5%;pointer-events:none;">
            </model-viewer>
        `;
        servicesSection.style.position = 'relative';
        servicesSection.appendChild(floatingTurtle);
    }

    // Easter Egg: 3D Turtle on © Symbol Click
    function addLoginEasterEgg() {
        // Find the copyright © symbol trigger
        const easterTrigger = document.getElementById('htb-easter-trigger');
        if (easterTrigger) {
            easterTrigger.addEventListener('click', function(e) {
                e.preventDefault();
                showEasterEgg();
            });
        }
    }

    function showEasterEgg() {
        // Remove existing easter egg if any
        const existing = document.getElementById('htb-easter-egg');
        if (existing) {
            existing.remove();
            return; // Toggle off
        }

        // Create easter egg overlay
        const easterEgg = document.createElement('div');
        easterEgg.id = 'htb-easter-egg';
        easterEgg.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: linear-gradient(135deg, rgba(240,244,242,0.95), rgba(232,240,238,0.95));
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            backdrop-filter: blur(10px);
            animation: fadeIn 0.3s ease;
        `;

        easterEgg.innerHTML = `
            <style>
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes bounce {
                    0%, 100% { transform: translateY(0); }
                    50% { transform: translateY(-20px); }
                }
                .htb-easter-egg-model {
                    animation: bounce 2s ease-in-out infinite;
                }
            </style>
            <div class="htb-easter-egg-model">
                <model-viewer
                    src="${htbData.themeUrl}/assets/images/model_Animation_Walking_withSkin.glb"
                    ios-src="${htbData.themeUrl}/assets/images/Animation_Walking.usdz"
                    alt="Happy the Turtle - Easter Egg!"
                    auto-rotate
                    camera-controls
                    ar
                    ar-modes="webxr scene-viewer quick-look"
                    shadow-intensity="1"
                    style="width:600px;height:600px;">
                    <button slot="ar-button" class="htb-btn htb-btn-primary" style="position:absolute;bottom:20px;left:50%;transform:translateX(-50%);border-radius:12px;padding:1rem 2rem;">
                        🎉 View Happy in AR!
                    </button>
                </model-viewer>
            </div>
            <h2 style="color:#1B4332;font-size:2.5rem;margin-top:2rem;text-align:center;">
                🎉 You Found the Easter Egg! 🎉
            </h2>
            <p style="color:#2D6A4F;font-size:1.25rem;margin-top:1rem;text-align:center;max-width:600px;">
                Congratulations! You discovered Happy the Turtle's secret! 🐢✨<br>
                Rotate, zoom, and even view him in AR on your mobile device!
            </p>
            <button
                onclick="document.getElementById('htb-easter-egg').remove()"
                class="htb-btn htb-btn-outline"
                style="margin-top:2rem;border-radius:12px;padding:1rem 2rem;">
                Close & Continue
            </button>
        `;

        document.body.appendChild(easterEgg);

        // Auto-close after 30 seconds
        setTimeout(() => {
            if (document.getElementById('htb-easter-egg')) {
                easterEgg.remove();
            }
        }, 30000);
    }

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            addFloatingTurtle();
            addLoginEasterEgg();
        }, 1000); // Wait for page to fully load
    });
})();
