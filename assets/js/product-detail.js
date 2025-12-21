/**
 * Product Detail Page JavaScript
 * Handles quantity controls, tabs, add to cart, and favorites
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {

        // ============================================================
        // QUANTITY CONTROLS
        // ============================================================

        const quantityInput = document.querySelector('.htb-quantity-input');
        const minusBtn = document.querySelector('.htb-qty-minus');
        const plusBtn = document.querySelector('.htb-qty-plus');

        if (quantityInput && minusBtn && plusBtn) {
            const min = parseInt(quantityInput.getAttribute('min')) || 1;
            const max = parseInt(quantityInput.getAttribute('max')) || 9999;

            // Decrease quantity
            minusBtn.addEventListener('click', function() {
                let currentValue = parseInt(quantityInput.value) || min;
                if (currentValue > min) {
                    quantityInput.value = currentValue - 1;
                    updateQuantityButtons();
                }
            });

            // Increase quantity
            plusBtn.addEventListener('click', function() {
                let currentValue = parseInt(quantityInput.value) || min;
                if (currentValue < max) {
                    quantityInput.value = currentValue + 1;
                    updateQuantityButtons();
                }
            });

            // Manual input validation
            quantityInput.addEventListener('change', function() {
                let value = parseInt(this.value) || min;
                if (value < min) value = min;
                if (value > max) value = max;
                this.value = value;
                updateQuantityButtons();
            });

            // Update button states
            function updateQuantityButtons() {
                const currentValue = parseInt(quantityInput.value) || min;
                minusBtn.disabled = currentValue <= min;
                plusBtn.disabled = currentValue >= max;
            }

            // Initial state
            updateQuantityButtons();
        }


        // ============================================================
        // TAB SWITCHING
        // ============================================================

        const tabButtons = document.querySelectorAll('.htb-tab-btn');
        const tabPanels = document.querySelectorAll('.htb-tab-panel');

        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');

                // Remove active class from all buttons and panels
                tabButtons.forEach(btn => {
                    btn.classList.remove('active');
                    btn.setAttribute('aria-selected', 'false');
                });
                tabPanels.forEach(panel => {
                    panel.classList.remove('active');
                });

                // Add active class to clicked button and corresponding panel
                this.classList.add('active');
                this.setAttribute('aria-selected', 'true');

                const targetPanel = document.getElementById('tab-' + targetTab);
                if (targetPanel) {
                    targetPanel.classList.add('active');
                }
            });
        });


        // ============================================================
        // ADD TO CART / ORDER REQUEST
        // ============================================================

        const addToCartBtn = document.querySelector('.htb-add-to-cart-btn');

        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const quantity = quantityInput ? parseInt(quantityInput.value) : 1;

                // Check if user is logged in
                if (typeof htbProductDetail === 'undefined' || !htbProductDetail.isLoggedIn) {
                    window.location.href = htbProductDetail.loginUrl || '/partner-login';
                    return;
                }

                // Disable button and show loading state
                this.disabled = true;
                this.classList.add('loading');
                const originalText = this.innerHTML;
                this.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" class="htb-spinner"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="4" fill="none" stroke-linecap="round"></path></svg> Adding...';

                // Send AJAX request to add to cart
                fetch(htbProductDetail.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'htb_add_to_cart',
                        product_id: productId,
                        quantity: quantity,
                        nonce: htbProductDetail.nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        showNotification('Product added to order request!', 'success');

                        // Update cart count if element exists
                        const cartCount = document.querySelector('.htb-cart-count');
                        if (cartCount && data.data.cart_count) {
                            cartCount.textContent = data.data.cart_count;
                            cartCount.style.display = 'inline-block';
                        }

                        // Reset button after delay
                        setTimeout(() => {
                            addToCartBtn.innerHTML = originalText;
                            addToCartBtn.classList.remove('loading');
                            addToCartBtn.disabled = false;
                        }, 1500);

                    } else {
                        showNotification(data.data.message || 'Failed to add product', 'error');
                        addToCartBtn.innerHTML = originalText;
                        addToCartBtn.classList.remove('loading');
                        addToCartBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Add to cart error:', error);
                    showNotification('An error occurred. Please try again.', 'error');
                    addToCartBtn.innerHTML = originalText;
                    addToCartBtn.classList.remove('loading');
                    addToCartBtn.disabled = false;
                });
            });
        }


        // ============================================================
        // FAVORITE TOGGLE
        // ============================================================

        const favoriteBtn = document.querySelector('.htb-favorite-btn');

        if (favoriteBtn) {
            favoriteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.getAttribute('data-product-id');

                // Check if user is logged in
                if (typeof htbProductDetail === 'undefined' || !htbProductDetail.isLoggedIn) {
                    window.location.href = htbProductDetail.loginUrl || '/partner-login';
                    return;
                }

                // Toggle visual state immediately
                this.classList.toggle('active');
                const isActive = this.classList.contains('active');
                this.setAttribute('title', isActive ? 'Remove from favorites' : 'Add to favorites');

                // Send AJAX request
                fetch(htbProductDetail.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'htb_toggle_favorite',
                        product_id: productId,
                        nonce: htbProductDetail.nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const message = data.data.action === 'added' ? 'Added to favorites' : 'Removed from favorites';
                        showNotification(message, 'success');
                    } else {
                        // Revert on error
                        this.classList.toggle('active');
                        showNotification('Failed to update favorites', 'error');
                    }
                })
                .catch(error => {
                    console.error('Favorite toggle error:', error);
                    // Revert on error
                    this.classList.toggle('active');
                    showNotification('An error occurred', 'error');
                });
            });
        }


        // ============================================================
        // NOTIFY WHEN AVAILABLE
        // ============================================================

        const notifyBtn = document.querySelector('.htb-notify-btn');

        if (notifyBtn) {
            notifyBtn.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');

                // Check if user is logged in
                if (typeof htbProductDetail === 'undefined' || !htbProductDetail.isLoggedIn) {
                    window.location.href = htbProductDetail.loginUrl || '/partner-login';
                    return;
                }

                // Disable button
                this.disabled = true;
                const originalText = this.innerHTML;
                this.innerHTML = 'Subscribing...';

                // Send AJAX request
                fetch(htbProductDetail.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'htb_notify_when_available',
                        product_id: productId,
                        nonce: htbProductDetail.nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.innerHTML = '✓ Subscribed';
                        this.classList.add('subscribed');
                        showNotification('You will be notified when this product is back in stock', 'success');
                    } else {
                        this.innerHTML = originalText;
                        this.disabled = false;
                        showNotification(data.data.message || 'Failed to subscribe', 'error');
                    }
                })
                .catch(error => {
                    console.error('Notify subscription error:', error);
                    this.innerHTML = originalText;
                    this.disabled = false;
                    showNotification('An error occurred', 'error');
                });
            });
        }


        // ============================================================
        // NOTIFICATION SYSTEM
        // ============================================================

        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = 'htb-notification htb-notification-' + type;
            notification.setAttribute('role', 'alert');

            // Icon based on type
            let icon = '';
            if (type === 'success') {
                icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>';
            } else if (type === 'error') {
                icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
            } else {
                icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
            }

            notification.innerHTML = icon + '<span>' + message + '</span>';

            // Add to page
            document.body.appendChild(notification);

            // Animate in
            setTimeout(() => notification.classList.add('show'), 10);

            // Auto-remove after 4 seconds
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }

    });

})();
