/**
 * YoCo Takeaway System - Frontend JavaScript
 * 
 * Handles menu functionality, search, filters, cart, and popups
 * 
 * @package YoCo_Takeaway_System
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * YoCo Frontend Object
     */
    const YoCoFrontend = {
        
        // Configuration
        config: {
            searchDelay: 300,
            notificationDuration: 4000,
            animationSpeed: 300
        },
        
        // State
        state: {
            selectedCategories: [],
            selectedTags: [],
            searchTerm: '',
            isLoading: false
        },

        /**
         * Initialize frontend functionality
         */
        init: function() {
            this.initSearch();
            this.initFilters();
            this.initQuantityControls();
            this.initOrderButtons();
            this.initAllergenPopups();
            this.bindEvents();
            
            console.log('YoCo Frontend initialized');
        },

        /**
         * Initialize search functionality
         */
        initSearch: function() {
            const searchInput = $('#yoco-search-input');
            const searchBtn = $('#yoco-search-btn');

            if (!searchInput.length) return;

            // Direct real-time search - no debouncing
            searchInput.on('input', function() {
                YoCoFrontend.performSearch();
            });

            // Search button click
            searchBtn.on('click', function(e) {
                e.preventDefault();
                YoCoFrontend.performSearch();
            });

            // Enter key search
            searchInput.on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    YoCoFrontend.performSearch();
                }
            });
        },

        /**
         * Perform search
         */
        performSearch: function() {
            this.state.searchTerm = $('#yoco-search-input').val().toLowerCase().trim();
            this.filterItems();
        },

        /**
         * Initialize filter functionality
         */
        initFilters: function() {
            const filtersToggle = $('#yoco-filters-toggle');
            const filtersContainer = $('#yoco-filters');
            const categoryBtns = $('.category-btn');
            const tagBtns = $('.tag-btn');
            const resetBtn = $('#yoco-filter-reset');

            // Toggle filters
            filtersToggle.on('click', function(e) {
                e.preventDefault();
                $(this).toggleClass('active');
                filtersContainer.toggleClass('active');
            });

            // Category filters
            categoryBtns.on('click', function() {
                const value = $(this).data('value');
                const button = $(this);

                if (button.hasClass('active')) {
                    button.removeClass('active');
                    YoCoFrontend.state.selectedCategories = YoCoFrontend.state.selectedCategories.filter(cat => cat !== value);
                } else {
                    button.addClass('active');
                    YoCoFrontend.state.selectedCategories.push(value);
                }

                YoCoFrontend.filterItems();
            });

            // Tag filters
            tagBtns.on('click', function() {
                const value = $(this).data('value');
                const button = $(this);

                if (button.hasClass('active')) {
                    button.removeClass('active');
                    YoCoFrontend.state.selectedTags = YoCoFrontend.state.selectedTags.filter(tag => tag !== value);
                } else {
                    button.addClass('active');
                    YoCoFrontend.state.selectedTags.push(value);
                }

                YoCoFrontend.filterItems();
            });

            // Reset filters
            resetBtn.on('click', function() {
                YoCoFrontend.resetFilters();
            });
        },

        /**
         * Filter items based on search and filters
         */
        filterItems: function() {
            const items = $('.yoco-item');
            let visibleCount = 0;

            // Track which categories have visible items
            const visibleCategories = new Set();

            items.each(function() {
                const item = $(this);
                const itemCategories = (item.data('categories') || '').toString().split(',').filter(Boolean);
                const itemTags = (item.data('tags') || '').toString().split(',').filter(Boolean);
                const itemTitle = item.find('.yoco-title').text().toLowerCase();
                const itemDescription = item.find('.yoco-description').text().toLowerCase();
                
                // Get diet badges text
                let dietText = '';
                item.find('.yoco-icon-item span').each(function() {
                    dietText += $(this).text().toLowerCase() + ' ';
                });

                let showItem = true;

                // Search term filter
                if (YoCoFrontend.state.searchTerm) {
                    const categoryNames = itemCategories.map(cat => cat.replace(/-/g, ' ')).join(' ');
                    const tagNames = itemTags.map(tag => tag.replace(/-/g, ' ')).join(' ');
                    const searchableText = (itemTitle + ' ' + itemDescription + ' ' + categoryNames + ' ' + tagNames + ' ' + dietText).toLowerCase();
                    
                    if (!searchableText.includes(YoCoFrontend.state.searchTerm)) {
                        showItem = false;
                    }
                }

                // Category filter
                if (YoCoFrontend.state.selectedCategories.length > 0 && showItem) {
                    showItem = itemCategories.some(cat => YoCoFrontend.state.selectedCategories.includes(cat));
                }

                // Tag filter
                if (YoCoFrontend.state.selectedTags.length > 0 && showItem) {
                    showItem = itemTags.some(tag => YoCoFrontend.state.selectedTags.includes(tag));
                }

                // Simple show/hide like original
                if (showItem) {
                    item.removeClass('hidden');
                    visibleCount++;
                    
                    // Track which categories have visible items
                    itemCategories.forEach(cat => {
                        if (cat) visibleCategories.add(cat);
                    });
                } else {
                    item.addClass('hidden');
                }
            });

            // Show/hide category sections based on whether they have visible items
            $('.yoco-category-section').each(function() {
                const categorySection = $(this);
                const categorySlug = categorySection.data('category');
                
                if (visibleCategories.has(categorySlug)) {
                    categorySection.removeClass('hidden');
                } else {
                    categorySection.addClass('hidden');
                }
            });

            // Show no results message
            YoCoFrontend.toggleNoResultsMessage(visibleCount === 0);
            
            console.log('Visible items:', visibleCount);
            console.log('Visible categories:', Array.from(visibleCategories));
        },

        /**
         * Reset all filters
         */
        resetFilters: function() {
            this.state.selectedCategories = [];
            this.state.selectedTags = [];
            this.state.searchTerm = '';
            
            $('#yoco-search-input').val('');
            $('.category-btn, .tag-btn').removeClass('active');
            $('.yoco-item').removeClass('hidden');
            
            this.toggleNoResultsMessage(false);
            
            // Update URL
            if (history.pushState) {
                const url = new URL(window.location);
                url.searchParams.delete('search');
                history.pushState(null, '', url);
            }
        },

        /**
         * Toggle no results message
         */
        toggleNoResultsMessage: function(show) {
            const container = $('#yoco-items-container');
            const message = container.find('.yoco-no-results');
            
            if (show && message.length === 0) {
                container.append('<div class="yoco-no-results"><p>Geen producten gevonden die voldoen aan je zoekcriteria. Probeer andere filters of zoektermen.</p></div>');
            } else if (!show && message.length > 0) {
                message.remove();
            }
        },

        /**
         * Initialize quantity controls
         */
        initQuantityControls: function() {
            $(document).on('click', '.yoco-qty-minus', function() {
                const input = $(this).siblings('.yoco-qty-input');
                let val = parseInt(input.val()) || 1;
                if (val > 1) {
                    input.val(val - 1);
                }
            });

            $(document).on('click', '.yoco-qty-plus', function() {
                const input = $(this).siblings('.yoco-qty-input');
                let val = parseInt(input.val()) || 1;
                if (val < 99) {
                    input.val(val + 1);
                }
            });

            // Direct input validation
            $(document).on('input', '.yoco-qty-input', function() {
                let val = parseInt($(this).val());
                if (isNaN(val) || val < 1) {
                    $(this).val(1);
                } else if (val > 99) {
                    $(this).val(99);
                }
            });
        },

        /**
         * Initialize order buttons
         */
        initOrderButtons: function() {
            $(document).on('click', '.yoco-order-btn', function() {
                if (YoCoFrontend.state.isLoading) return;
                
                const button = $(this);
                const item = button.closest('.yoco-item');
                const foodId = item.data('food-id');
                const quantity = item.find('.yoco-qty-input').val();
                const foodTitle = item.find('.yoco-title').text();

                YoCoFrontend.addToCart(foodId, quantity, foodTitle, button);
            });
        },

        /**
         * Add item to cart via AJAX
         */
        addToCart: function(foodId, quantity, foodTitle, button) {
            this.state.isLoading = true;
            
            const originalText = button.text();
            button.prop('disabled', true).text(yoco_ajax.i18n.adding);

            $.ajax({
                url: yoco_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'yoco_add_to_cart',
                    food_id: foodId,
                    quantity: quantity,
                    nonce: yoco_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        YoCoFrontend.showNotification(
                            yoco_ajax.i18n.added_title,
                            yoco_ajax.i18n.added_message.replace('%d', quantity).replace('%s', foodTitle),
                            'success'
                        );
                        
                        // Update cart count if element exists
                        if (response.data.cart_count) {
                            $('.cart-count, .cart-contents-count').text(response.data.cart_count);
                        }
                        
                        // Trigger WooCommerce cart update
                        if (typeof jQuery !== 'undefined') {
                            jQuery(document.body).trigger('wc_fragment_refresh');
                        }
                        
                        // Reset quantity to 1
                        button.closest('.yoco-item').find('.yoco-qty-input').val(1);
                        
                    } else {
                        YoCoFrontend.showNotification(
                            yoco_ajax.i18n.error_title,
                            response.data || yoco_ajax.i18n.error_message,
                            'error'
                        );
                    }
                },
                error: function() {
                    YoCoFrontend.showNotification(
                        yoco_ajax.i18n.error_title,
                        yoco_ajax.i18n.network_error,
                        'error'
                    );
                },
                complete: function() {
                    YoCoFrontend.state.isLoading = false;
                    button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Show notification
         */
        showNotification: function(title, message, type = 'success') {
            // Remove existing notification
            $('.yoco-notification').remove();
            
            const notification = $(`
                <div class="yoco-notification ${type}">
                    <div class="yoco-notification-icon">${type === 'success' ? '✓' : '✕'}</div>
                    <div class="yoco-notification-content">
                        <div class="yoco-notification-title">${title}</div>
                        <div class="yoco-notification-message">${message}</div>
                    </div>
                    <button class="yoco-notification-close">×</button>
                </div>
            `);
            
            $('body').append(notification);
            
            // Auto-remove notification
            setTimeout(function() {
                notification.addClass('hiding');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }, this.config.notificationDuration);
            
            // Manual close
            notification.find('.yoco-notification-close').on('click', function() {
                notification.addClass('hiding');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            });
        },

        /**
         * Initialize allergen popups
         */
        initAllergenPopups: function() {
            $(document).on('click', '.yoco-allergens-btn', function() {
                const foodId = $(this).data('food-id');
                YoCoFrontend.showAllergenPopup(foodId);
            });
        },

        /**
         * Show allergen popup
         */
        showAllergenPopup: function(foodId) {
            const allergens = window.yocoAllergens && window.yocoAllergens[foodId];
            
            if (!allergens || allergens.length === 0) {
                this.showNotification(
                    'Geen allergenen',
                    'Voor dit product zijn geen allergenen geregistreerd.',
                    'info'
                );
                return;
            }
            
            const overlay = $(`
                <div class="yoco-allergen-popup-overlay">
                    <div class="yoco-allergen-popup">
                        <div class="yoco-allergen-popup-header">
                            <h3 class="yoco-allergen-popup-title">⚠️ Allergenen Informatie</h3>
                            <button class="yoco-allergen-popup-close">×</button>
                        </div>
                        <ul class="yoco-allergen-list">
                            ${allergens.map(allergen => `<li class="yoco-allergen-item">${allergen}</li>`).join('')}
                        </ul>
                        <div class="yoco-allergen-popup-footer">
                            Dit product bevat bovenstaande allergenen. Neem bij twijfel contact met ons op.
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(overlay);
            
            // Close handlers
            overlay.find('.yoco-allergen-popup-close').on('click', function() {
                overlay.remove();
            });
            
            overlay.on('click', function(e) {
                if (e.target === this) {
                    overlay.remove();
                }
            });
            
            // ESC key close
            $(document).on('keydown.yoco-popup', function(e) {
                if (e.key === 'Escape') {
                    overlay.remove();
                    $(document).off('keydown.yoco-popup');
                }
            });
        },

        /**
         * Bind additional events
         */
        bindEvents: function() {
            // Handle browser back/forward
            $(window).on('popstate', function() {
                const urlParams = new URLSearchParams(window.location.search);
                const searchTerm = urlParams.get('search');
                
                if (searchTerm) {
                    $('#yoco-search-input').val(searchTerm);
                    YoCoFrontend.state.searchTerm = searchTerm.toLowerCase();
                    YoCoFrontend.filterItems();
                } else {
                    YoCoFrontend.resetFilters();
                }
            });
            
            // Accessibility: keyboard navigation for filters
            $('.yoco-filter-btn').on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).click();
                }
            });
            
            // Touch device optimization
            if ('ontouchstart' in window) {
                $('.yoco-item').addClass('touch-device');
            }
        },

        /**
         * Utility function to get URL parameters
         */
        getUrlParameter: function(name) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        },

        /**
         * Initialize from URL parameters
         */
        initFromUrl: function() {
            const searchTerm = this.getUrlParameter('search');
            if (searchTerm) {
                $('#yoco-search-input').val(searchTerm);
                this.state.searchTerm = searchTerm.toLowerCase();
                this.filterItems();
            }
        }
    };

    /**
     * Enhanced Shopping Cart Integration
     */
    const YoCoCart = {
        
        /**
         * Initialize cart functionality
         */
        init: function() {
            this.bindCartEvents();
        },

        /**
         * Bind cart-related events
         */
        bindCartEvents: function() {
            // Update cart fragments
            $(document.body).on('updated_wc_div', function() {
                // Cart was updated, refresh any cart displays
                console.log('Cart updated');
            });
            
            // Handle add to cart button state
            $(document.body).on('adding_to_cart', function(event, $button, data) {
                $button.addClass('loading');
            });
            
            $(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button) {
                if ($button) {
                    $button.removeClass('loading');
                }
            });
        }
    };

    /**
     * Performance Optimizations
     */
    const YoCoPerformance = {
        
        /**
         * Initialize performance optimizations
         */
        init: function() {
            this.lazyLoadImages();
            this.optimizeScrolling();
        },

        /**
         * Lazy load images
         */
        lazyLoadImages: function() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                $('.yoco-image[data-src]').each(function() {
                    imageObserver.observe(this);
                });
            }
        },

        /**
         * Optimize scrolling performance
         */
        optimizeScrolling: function() {
            let ticking = false;
            
            function updateScrollPosition() {
                // Add scroll-based animations or effects here
                ticking = false;
            }
            
            $(window).on('scroll', function() {
                if (!ticking) {
                    requestAnimationFrame(updateScrollPosition);
                    ticking = true;
                }
            });
        }
    };

    /**
     * Initialize everything when document is ready
     */
    $(document).ready(function() {
        // Only initialize if YoCo menu exists
        if ($('#yoco-menu').length) {
            YoCoFrontend.init();
            YoCoFrontend.initFromUrl();
            YoCoCart.init();
            YoCoPerformance.init();
        }
    });

    // Expose YoCoFrontend globally for debugging
    window.YoCoFrontend = YoCoFrontend;

})(jQuery);