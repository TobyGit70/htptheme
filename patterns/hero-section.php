<?php
/**
 * Title: Hero Section
 * Slug: happyturtle-fse/hero-section
 * Categories: happyturtle, featured
 * Keywords: hero, landing, intro, 3d, turtle
 * Block Types: core/group
 * Description: A hero section with 3D animated turtle, gradient text, and call to action buttons
 */
?>
<!-- wp:group {"align":"full","className":"htb-hero-section","gradient":"hero-background","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull htb-hero-section has-hero-background-gradient-background has-background">
    <!-- wp:html -->
    <div class="htb-hero">
        <!-- ROW 1: Two Columns (Atom Animation + Title/Tagline) -->
        <div class="htb-hero-row-1">
            <!-- Left Column: Atom Animation with HTP Text -->
            <div class="htb-hero-left">
                <div id="atom-hero-container" style="position: relative; width: 100%; height: 400px; display: flex; align-items: center; justify-content: center; border-radius: 20px; overflow: hidden; background: linear-gradient(135deg, rgba(27, 67, 50, 0.05), rgba(45, 106, 79, 0.05));">
                    <video autoplay loop muted playsinline style="position: absolute; width: 100%; height: 100%; object-fit: contain; opacity: 0.6;">
                        <source src="<?php echo get_template_directory_uri(); ?>/assets/images/atom.mp4" type="video/mp4">
                    </video>
                    <div style="position: relative; z-index: 10; font-size: 6rem; font-weight: 900; background: linear-gradient(135deg, #1B4332, #2D6A4F, #D4A574); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; text-shadow: 0 4px 12px rgba(27, 67, 50, 0.3);">
                        HTP
                    </div>
                </div>
            </div>

            <!-- Right Column: Title & Tagline -->
            <div class="htb-hero-right">
                <h1 class="htb-gradient-text htb-scroll-zoom">Happy Turtle Processing</h1>
                <p class="tagline">Where Chill Meets Craft —<br><span style="padding-left: 4.5em;">Turning Bud Into Better</span></p>
            </div>
        </div>

        <!-- ROW 2: Description + Buttons (Centered) -->
        <div class="htb-hero-row-2">
            <p class="description">We're Arkansas's licensed cannabis processor — crafting premium concentrates for dispensaries statewide, always in full compliance with Arkansas regulations.</p>

            <div class="buttons">
                <a href="#products" class="htb-hero-btn htb-hero-btn-primary">
                    <svg class="htb-btn-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    Learn About Our Products
                </a>
                <a href="#contact" class="htb-hero-btn htb-hero-btn-secondary">
                    <svg class="htb-btn-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    Contact Us
                </a>
            </div>
        </div>
    </div>
    <!-- /wp:html -->
</div>
<!-- /wp:group -->
