/**
 * Shopping Cart JavaScript
 * Handles cart updates, removals, and calculations
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {

        // ============================================================
        // UPDATE QUANTITY
        // ============================================================

        // Quantity minus buttons
        const minusButtons = document.querySelectorAll('.htb-cart-items .htb-qty-minus');
        minusButtons.forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const input = document.querySelector(`.htb-cart-quantity-input[data-product-id="${productId}"]`);

                if (input) {
                    const min = parseInt(input.getAttribute('min')) || 1;
                    let currentValue = parseInt(input.value) || min;

                    if (currentValue > min) {
                        input.value = currentValue - 1;
                        updateCartQuantity(productId, currentValue - 1);
                    }
                }
            });
        });

        // Quantity plus buttons
        const plusButtons = document.querySelectorAll('.htb-cart-items .htb-qty-plus');
        plusButtons.forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const input = document.querySelector(`.htb-cart-quantity-input[data-product-id="${productId}"]`);

                if (input) {
                    const max = parseInt(input.getAttribute('max')) || 9999;
                    let currentValue = parseInt(input.value) || 1;

                    if (currentValue < max) {
                        input.value = currentValue + 1;
                        updateCartQuantity(productId, currentValue + 1);
                    }
                }
            });
        });

        // Manual quantity input
        const quantityInputs = document.querySelectorAll('.htb-cart-quantity-input');
        quantityInputs.forEach(input => {
            input.addEventListener('change', function() {
                const productId = this.getAttribute('data-product-id');
                const min = parseInt(this.getAttribute('min')) || 1;
                const max = parseInt(this.getAttribute('max')) || 9999;
                let value = parseInt(this.value) || min;

                // Validate
                if (value < min) value = min;
                if (value > max) value = max;

                this.value = value;
                updateCartQuantity(productId, value);
            });
        });


        // ============================================================
        // REMOVE FROM CART
        // ============================================================

        const removeButtons = document.querySelectorAll('.htb-cart-remove-btn');
        removeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');

                if (confirm('Are you sure you want to remove this item from your cart?')) {
                    removeFromCart(productId);
                }
            });
        });


        // ============================================================
        // CLEAR CART
        // ============================================================

        const clearCartBtn = document.querySelector('.htb-clear-cart-btn');
        if (clearCartBtn) {
            clearCartBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to clear your entire cart? This cannot be undone.')) {
                    clearCart();
                }
            });
        }


        // ============================================================
        // AJAX FUNCTIONS
        // ============================================================

        /**
         * Update cart item quantity
         */
        function updateCartQuantity(productId, quantity) {
            // Disable inputs during update
            const cartItem = document.querySelector(`.htb-cart-item[data-product-id="${productId}"]`);
            if (cartItem) {
                cartItem.classList.add('updating');
            }

            fetch(htbCart.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'htb_update_cart_quantity',
                    product_id: productId,
                    quantity: quantity,
                    nonce: htbCart.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update line total
                    const lineTotal = cartItem.querySelector('.htb-cart-line-total');
                    if (lineTotal) {
                        lineTotal.textContent = '$' + parseFloat(data.data.line_total).toFixed(2);
                    }

                    // Update cart totals
                    updateCartTotals(data.data);

                    // Show success notification
                    showNotification('Cart updated', 'success');

                } else {
                    showNotification(data.data.message || 'Failed to update cart', 'error');
                }

                // Remove updating class
                if (cartItem) {
                    cartItem.classList.remove('updating');
                }
            })
            .catch(error => {
                console.error('Update cart error:', error);
                showNotification('An error occurred', 'error');

                if (cartItem) {
                    cartItem.classList.remove('updating');
                }
            });
        }


        /**
         * Remove item from cart
         */
        function removeFromCart(productId) {
            const cartItem = document.querySelector(`.htb-cart-item[data-product-id="${productId}"]`);

            // Add removing animation
            if (cartItem) {
                cartItem.classList.add('removing');
            }

            fetch(htbCart.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'htb_remove_from_cart',
                    product_id: productId,
                    nonce: htbCart.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Animate out and remove
                    if (cartItem) {
                        setTimeout(() => {
                            cartItem.remove();

                            // Check if cart is empty
                            const remainingItems = document.querySelectorAll('.htb-cart-item');
                            if (remainingItems.length === 0) {
                                // Reload page to show empty cart state
                                window.location.reload();
                            } else {
                                // Update totals
                                updateCartTotals(data.data);
                                updateCartCount(data.data.cart_count);
                            }
                        }, 300);
                    }

                    showNotification('Item removed from cart', 'success');

                } else {
                    showNotification(data.data.message || 'Failed to remove item', 'error');

                    if (cartItem) {
                        cartItem.classList.remove('removing');
                    }
                }
            })
            .catch(error => {
                console.error('Remove from cart error:', error);
                showNotification('An error occurred', 'error');

                if (cartItem) {
                    cartItem.classList.remove('removing');
                }
            });
        }


        /**
         * Clear entire cart
         */
        function clearCart() {
            const clearBtn = document.querySelector('.htb-clear-cart-btn');

            // Disable button
            if (clearBtn) {
                clearBtn.disabled = true;
                clearBtn.textContent = 'Clearing...';
            }

            fetch(htbCart.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'htb_clear_cart',
                    nonce: htbCart.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Cart cleared', 'success');

                    // Reload to show empty cart state
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);

                } else {
                    showNotification(data.data.message || 'Failed to clear cart', 'error');

                    if (clearBtn) {
                        clearBtn.disabled = false;
                        clearBtn.textContent = 'Clear Cart';
                    }
                }
            })
            .catch(error => {
                console.error('Clear cart error:', error);
                showNotification('An error occurred', 'error');

                if (clearBtn) {
                    clearBtn.disabled = false;
                    clearBtn.textContent = 'Clear Cart';
                }
            });
        }


        /**
         * Update cart totals in sidebar
         */
        function updateCartTotals(data) {
            // Update subtotal
            const subtotalElement = document.querySelector('.htb-summary-subtotal');
            if (subtotalElement && data.subtotal !== undefined) {
                subtotalElement.textContent = '$' + parseFloat(data.subtotal).toFixed(2);
            }

            // Update transport fee
            const transportElement = document.querySelector('.htb-summary-transport');
            if (transportElement && data.transport_fee !== undefined) {
                transportElement.textContent = '$' + parseFloat(data.transport_fee).toFixed(2);
            }

            // Update total
            const totalElement = document.querySelector('.htb-summary-total');
            if (totalElement && data.total !== undefined) {
                totalElement.textContent = '$' + parseFloat(data.total).toFixed(2);
            }

            // Update cart count in header
            if (data.cart_count !== undefined) {
                updateCartCount(data.cart_count);
            }
        }


        /**
         * Update cart count badge in header
         */
        function updateCartCount(count) {
            const cartCountElement = document.querySelector('.htb-cart-count-badge');
            if (cartCountElement) {
                if (count > 0) {
                    cartCountElement.textContent = count;
                    cartCountElement.style.display = 'inline-block';
                } else {
                    cartCountElement.style.display = 'none';
                }
            }

            // Update page header count
            const pageCountElement = document.querySelector('.htb-cart-header .htb-cart-count');
            if (pageCountElement) {
                pageCountElement.textContent = count + ' ' + (count === 1 ? 'item' : 'items') + ' in your cart';
            }
        }


        /**
         * Show notification toast
         */
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
