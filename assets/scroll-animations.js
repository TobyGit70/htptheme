/**
 * Scroll Animations
 * Adds zoom effect to elements when they scroll into view
 */

document.addEventListener('DOMContentLoaded', function() {
    // Create Intersection Observer
    const observerOptions = {
        threshold: 0.2,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                // Optional: unobserve after animation to improve performance
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe all elements with htb-scroll-zoom class
    const zoomElements = document.querySelectorAll('.htb-scroll-zoom');
    zoomElements.forEach(element => {
        observer.observe(element);
    });
});
