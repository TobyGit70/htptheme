<?php
/**
 * Title: Hero Section
 * Slug: happyturtle-fse/hero-section
 * Categories: happyturtle, featured
 * Keywords: hero, landing, intro
 * Block Types: core/group
 * Description: Clean, modern hero section for Happy Turtle Processing
 */

$video_url = get_template_directory_uri() . '/assets/images/atomgreen.mp4';
?>
<!-- wp:group {"align":"full","className":"htb-hero-section","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull htb-hero-section">
    <!-- wp:html -->
    <div class="htb-hero-modern">
        <!-- Centered Content Container -->
        <div class="htb-hero-content">
            <!-- Animated Logo/Badge -->
            <div class="htb-hero-badge">
                <div class="htb-hero-video-container">
                    <video autoplay loop muted playsinline>
                        <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                    </video>
                    <div class="htb-hero-logo-text">HTP</div>
                </div>
            </div>

            <!-- Main Headline -->
            <h1 class="htb-hero-title">
                <span class="htb-hero-title-main">Happy Turtle</span>
                <span class="htb-hero-title-accent">Processing</span>
            </h1>

            <!-- Tagline -->
            <p class="htb-hero-tagline">
                Where Chill Meets Craft <span class="htb-hero-divider">|</span> Turning Bud Into Better
            </p>

            <!-- Description -->
            <p class="htb-hero-description">
                Arkansas's premier licensed cannabis processor — crafting premium concentrates
                for dispensaries statewide, in full compliance with state regulations.
            </p>

            <!-- CTA Buttons -->
            <div class="htb-hero-buttons">
                <a href="#products" class="htb-btn htb-btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    Our Products
                </a>
                <a href="/contact/" class="htb-btn htb-btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    Get In Touch
                </a>
            </div>

            <!-- Trust Badges -->
            <div class="htb-hero-badges">
                <div class="htb-trust-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
                    <span>Licensed & Compliant</span>
                </div>
                <div class="htb-trust-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>
                    <span>Premium Quality</span>
                </div>
                <div class="htb-trust-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span>Arkansas Based</span>
                </div>
            </div>
        </div>
    </div>
    <!-- /wp:html -->
</div>
<!-- /wp:group -->
