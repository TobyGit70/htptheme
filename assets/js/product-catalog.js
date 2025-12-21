/**
 * Product Catalog - Filters, Search, and Sorting
 *
 * @package HappyTurtle_FSE
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        // Only run on product archive pages
        if (!document.querySelector('.htb-products-archive')) {
            return;
        }

        initFilters();
        initSearch();
        initSort();
        initFavorites();
        initMobileFilters();
    }

    /**
     * Initialize filter functionality
     */
    function initFilters() {
        // Category filters
        const categoryFilters = document.querySelectorAll('.htb-category-filter');
        categoryFilters.forEach(filter => {
            filter.addEventListener('change', applyFilters);
        });

        // Availability filter
        const availabilityFilter = document.querySelector('.htb-availability-filter');
        if (availabilityFilter) {
            availabilityFilter.addEventListener('change', applyFilters);
        }

        // Price filter
        const applyPriceBtn = document.getElementById('htb-apply-price');
        if (applyPriceBtn) {
            applyPriceBtn.addEventListener('click', applyPriceFilter);
        }

        // Allow Enter key on price inputs
        const priceInputs = document.querySelectorAll('.htb-price-input');
        priceInputs.forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    applyPriceFilter();
                }
            });
        });

        // Clear all filters
        const clearFiltersBtn = document.getElementById('htb-clear-filters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', clearAllFilters);
        }

        // Reset filters button (no products found)
        const resetFiltersBtn = document.getElementById('htb-reset-filters');
        if (resetFiltersBtn) {
            resetFiltersBtn.addEventListener('click', clearAllFilters);
        }
    }

    /**
     * Apply filters to URL and reload
     */
    function applyFilters() {
        const url = new URL(window.location.href);
        const params = new URLSearchParams(url.search);

        // Get selected categories
        const selectedCategories = Array.from(document.querySelectorAll('.htb-category-filter:checked'))
            .map(cb => cb.value);

        // Update category params
        params.delete('category');
        if (selectedCategories.length > 0) {
            params.set('category', selectedCategories.join(','));
        }

        // Get availability filter
        const inStockOnly = document.querySelector('.htb-availability-filter');
        params.delete('in_stock');
        if (inStockOnly && inStockOnly.checked) {
            params.set('in_stock', '1');
        }

        // Reset to page 1 when filters change
        params.delete('paged');

        // Update URL and reload
        window.location.href = url.pathname + '?' + params.toString();
    }

    /**
     * Apply price filter
     */
    function applyPriceFilter() {
        const url = new URL(window.location.href);
        const params = new URLSearchParams(url.search);

        const minPrice = document.getElementById('htb-price-min').value;
        const maxPrice = document.getElementById('htb-price-max').value;

        params.delete('price_min');
        params.delete('price_max');

        if (minPrice) {
            params.set('price_min', minPrice);
        }

        if (maxPrice) {
            params.set('price_max', maxPrice);
        }

        // Reset to page 1 when filters change
        params.delete('paged');

        // Update URL and reload
        window.location.href = url.pathname + '?' + params.toString();
    }

    /**
     * Clear all filters
     */
    function clearAllFilters() {
        // Get base URL without query params
        const url = new URL(window.location.href);
        window.location.href = url.pathname;
    }

    /**
     * Initialize search functionality
     */
    function initSearch() {
        const searchInput = document.getElementById('htb-product-search');
        const searchBtn = document.getElementById('htb-search-submit');

        if (!searchInput || !searchBtn) return;

        // Click search button
        searchBtn.addEventListener('click', performSearch);

        // Enter key in search box
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }

    /**
     * Perform product search
     */
    function performSearch() {
        const searchInput = document.getElementById('htb-product-search');
        const searchTerm = searchInput.value.trim();

        const url = new URL(window.location.href);
        const params = new URLSearchParams(url.search);

        params.delete('product_search');
        params.delete('paged');

        if (searchTerm) {
            params.set('product_search', searchTerm);
        }

        window.location.href = url.pathname + '?' + params.toString();
    }

    /**
     * Initialize sort functionality
     */
    function initSort() {
        const sortSelect = document.getElementById('htb-sort-select');
        if (!sortSelect) return;

        sortSelect.addEventListener('change', function() {
            const url = new URL(window.location.href);
            const params = new URLSearchParams(url.search);

            params.delete('sort');
            params.delete('paged');

            if (this.value && this.value !== 'default') {
                params.set('sort', this.value);
            }

            window.location.href = url.pathname + '?' + params.toString();
        });
    }

    /**
     * Initialize favorites functionality
     */
    function initFavorites() {
        const favoriteButtons = document.querySelectorAll('.htb-favorite-btn');

        favoriteButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const productId = this.getAttribute('data-product-id');
                toggleFavorite(productId, this);
            });
        });
    }

    /**
     * Toggle product favorite status
     */
    function toggleFavorite(productId, button) {
        // Check if user is logged in (partner)
        if (typeof htbCatalog === 'undefined' || !htbCatalog.isLoggedIn) {
            // Redirect to login
            window.location.href = htbCatalog.loginUrl || '/partner-login';
            return;
        }

        // Toggle visual state immediately
        button.classList.toggle('active');

        // Send AJAX request to save favorite
        fetch(htbCatalog.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'htb_toggle_favorite',
                product_id: productId,
                nonce: htbCatalog.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                // Revert on error
                button.classList.toggle('active');
                console.error('Failed to toggle favorite:', data.message);
            }
        })
        .catch(error => {
            // Revert on error
            button.classList.toggle('active');
            console.error('Error toggling favorite:', error);
        });
    }

    /**
     * Initialize mobile filters toggle
     */
    function initMobileFilters() {
        const filterToggle = document.querySelector('.htb-filters-toggle');
        const filtersPanel = document.querySelector('.htb-filters-panel');

        if (!filterToggle || !filtersPanel) return;

        filterToggle.addEventListener('click', function() {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';

            this.setAttribute('aria-expanded', !isExpanded);
            filtersPanel.classList.toggle('active');

            // Smooth scroll to filters on mobile
            if (!isExpanded && window.innerWidth < 768) {
                filtersPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });

        // Close filters when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 768 &&
                !filterToggle.contains(e.target) &&
                !filtersPanel.contains(e.target) &&
                filtersPanel.classList.contains('active')) {

                filterToggle.setAttribute('aria-expanded', 'false');
                filtersPanel.classList.remove('active');
            }
        });
    }

    /**
     * Smooth scroll to top after filter changes (optional enhancement)
     */
    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

})();
