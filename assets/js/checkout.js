/**
 * Checkout Page JavaScript
 * Handles order submission and approval workflow
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {

        const checkoutForm = document.getElementById('htb-checkout-form');
        const submitBtn = document.querySelector('.htb-submit-order-btn');

        if (!checkoutForm) {
            return;
        }

        // ============================================================
        // FORM VALIDATION
        // ============================================================

        /**
         * Validate required fields
         */
        function validateForm() {
            const requiredFields = checkoutForm.querySelectorAll('[required]');
            let isValid = true;
            let firstInvalidField = null;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('htb-field-error');

                    if (!firstInvalidField) {
                        firstInvalidField = field;
                    }
                } else {
                    field.classList.remove('htb-field-error');
                }
            });

            // Focus first invalid field
            if (firstInvalidField) {
                firstInvalidField.focus();
                showNotification('Please fill in all required fields', 'error');
            }

            return isValid;
        }

        // Remove error class on input
        const formFields = checkoutForm.querySelectorAll('input, textarea, select');
        formFields.forEach(field => {
            field.addEventListener('input', function() {
                this.classList.remove('htb-field-error');
            });
        });


        // ============================================================
        // FORM SUBMISSION
        // ============================================================

        checkoutForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Validate form
            if (!validateForm()) {
                return;
            }

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<svg class="htb-spinner" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" opacity="0.25"></circle><path d="M12 2 A10 10 0 0 1 22 12" opacity="0.75"></path></svg> Submitting Order...';

            // Get form data
            const formData = new FormData(checkoutForm);
            formData.append('action', 'htb_submit_order');

            // Submit via AJAX
            fetch(htbCheckout.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Order submitted successfully
                    showNotification(data.data.message || 'Order submitted successfully!', 'success');

                    // Redirect to confirmation page
                    setTimeout(() => {
                        window.location.href = data.data.redirect_url;
                    }, 1000);

                } else {
                    // Error submitting order
                    showNotification(data.data.message || 'Failed to submit order. Please try again.', 'error');

                    // Re-enable button
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('loading');
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Order submission error:', error);
                showNotification('An error occurred while submitting your order. Please try again.', 'error');

                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
                submitBtn.innerHTML = originalText;
            });
        });


        // ============================================================
        // PHONE NUMBER FORMATTING
        // ============================================================

        const phoneInput = document.getElementById('delivery_phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');

                if (value.length > 0) {
                    if (value.length <= 3) {
                        value = '(' + value;
                    } else if (value.length <= 6) {
                        value = '(' + value.slice(0, 3) + ') ' + value.slice(3);
                    } else {
                        value = '(' + value.slice(0, 3) + ') ' + value.slice(3, 6) + '-' + value.slice(6, 10);
                    }
                }

                e.target.value = value;
            });
        }


        // ============================================================
        // ZIP CODE VALIDATION
        // ============================================================

        const zipInput = document.getElementById('delivery_zip');
        if (zipInput) {
            zipInput.addEventListener('input', function(e) {
                // Allow only numbers and hyphen
                let value = e.target.value.replace(/[^\d-]/g, '');

                // Limit to 10 characters (5 digit or 9 digit+hyphen)
                if (value.length > 10) {
                    value = value.slice(0, 10);
                }

                e.target.value = value;
            });

            zipInput.addEventListener('blur', function(e) {
                const value = e.target.value.replace(/\D/g, '');

                // Format as 12345 or 12345-6789
                if (value.length === 5) {
                    e.target.value = value;
                } else if (value.length === 9) {
                    e.target.value = value.slice(0, 5) + '-' + value.slice(5);
                }
            });
        }


        // ============================================================
        // STATE INPUT (AUTO-UPPERCASE)
        // ============================================================

        const stateInput = document.getElementById('delivery_state');
        if (stateInput) {
            stateInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.toUpperCase();
            });
        }


        // ============================================================
        // NOTIFICATION SYSTEM
        // ============================================================

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

            // Auto-remove after 5 seconds
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }


        // ============================================================
        // PREVENT DOUBLE SUBMISSION
        // ============================================================

        let isSubmitting = false;

        checkoutForm.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
        });

    });

})();
