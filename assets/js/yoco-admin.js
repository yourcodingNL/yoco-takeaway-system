/**
 * YoCo Takeaway System - Admin JavaScript
 * 
 * Handles admin functionality like media uploader
 * 
 * @package YoCo_Takeaway_System
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * YoCo Admin Object
     */
    const YoCoAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.initMediaUploader();
            this.initFormValidation();
            this.initTooltips();
            this.initMetaBoxes();
        },

        /**
         * Initialize media uploader for icon fields
         */
        initMediaUploader: function() {
            let mediaUploader;
            
            $('.yoco-upload-icon-button').on('click', function(e) {
                e.preventDefault();
                
                const button = $(this);
                const targetInput = button.data('target');
                
                // If the media frame already exists, reopen it
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                // Create the media frame
                mediaUploader = wp.media({
                    title: yoco_admin.media_title || 'Kies een icoon',
                    button: { 
                        text: yoco_admin.media_button || 'Gebruik dit icoon'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });
                
                // When an image is selected, run a callback
                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    
                    // Update the input field
                    $('#' + targetInput).val(attachment.url);
                    
                    // Remove existing preview
                    button.siblings('br, img').remove();
                    
                    // Add new preview
                    button.after('<br><img src="' + attachment.url + '" class="yoco-icon-preview" alt="Icon Preview">');
                    
                    // Mark as changed for save indication
                    button.closest('form').addClass('yoco-form-changed');
                });
                
                // Open the modal
                mediaUploader.open();
            });
            
            // Handle remove icon functionality
            $(document).on('click', '.yoco-remove-icon', function(e) {
                e.preventDefault();
                
                const button = $(this);
                const targetInput = button.data('target');
                
                // Clear the input field
                $('#' + targetInput).val('');
                
                // Remove preview elements
                button.siblings('br, img').remove();
                button.remove();
                
                // Mark as changed
                button.closest('form').addClass('yoco-form-changed');
            });
        },

        /**
         * Initialize form validation
         */
        initFormValidation: function() {
            // Price field validation
            $('#yoco_food_price').on('input', function() {
                const value = parseFloat($(this).val());
                const field = $(this);
                
                // Remove existing error states
                field.removeClass('yoco-field-error yoco-field-success');
                field.siblings('.yoco-error-message').remove();
                
                if (isNaN(value) || value < 0) {
                    field.addClass('yoco-field-error');
                    field.after('<span class="yoco-error-message">Voer een geldige prijs in</span>');
                } else if (value > 0) {
                    field.addClass('yoco-field-success');
                }
            });
            
            // Form submission validation
            $('form[name="post"]').on('submit', function(e) {
                let hasErrors = false;
                
                // Check required price field
                const priceField = $('#yoco_food_price');
                if (priceField.length && !priceField.val()) {
                    e.preventDefault();
                    hasErrors = true;
                    
                    priceField.addClass('yoco-field-error');
                    priceField.focus();
                    
                    // Show error notice
                    YoCoAdmin.showNotice('Fout: Prijs is verplicht', 'error');
                }
                
                return !hasErrors;
            });
        },

        /**
         * Initialize tooltips for form fields
         */
        initTooltips: function() {
            // Add tooltips to spicy rating
            $('.yoco-spicy-rating label').each(function() {
                const level = $(this).find('input').val();
                let tooltip = '';
                
                switch(level) {
                    case '0':
                        tooltip = 'Geen pittigheid';
                        break;
                    case '1':
                        tooltip = 'Licht pittig, geschikt voor de meeste mensen';
                        break;
                    case '2':
                        tooltip = 'Matig pittig, voor liefhebbers';
                        break;
                    case '3':
                        tooltip = 'Pittig, alleen voor ervaren eters';
                        break;
                    case '4':
                        tooltip = 'Zeer pittig, waarschuwing vereist';
                        break;
                }
                
                if (tooltip) {
                    $(this).attr('title', tooltip);
                }
            });
        },

        /**
         * Initialize meta box enhancements
         */
        initMetaBoxes: function() {
            // Auto-save functionality
            let saveTimeout;
            
            $('#yoco_food_details input, #yoco_food_details select, #yoco_food_details textarea').on('change input', function() {
                clearTimeout(saveTimeout);
                
                // Show saving indicator
                const indicator = $('.yoco-save-indicator');
                if (indicator.length === 0) {
                    $('#yoco_food_details .hndle').append('<span class="yoco-save-indicator"> (wijzigingen...)</span>');
                }
                
                // Clear indicator after 2 seconds
                saveTimeout = setTimeout(function() {
                    $('.yoco-save-indicator').remove();
                }, 2000);
            });
            
            // Collapsible sections
            $('.yoco-section-toggle').on('click', function() {
                const section = $(this).next('.yoco-collapsible-section');
                section.slideToggle();
                $(this).toggleClass('collapsed');
            });
        },

        /**
         * Show admin notice
         */
        showNotice: function(message, type = 'info') {
            const noticeClass = type === 'error' ? 'notice-error' : type === 'success' ? 'notice-success' : 'notice-info';
            
            const notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.wrap h1').after(notice);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Utility function to format price
         */
        formatPrice: function(price) {
            return parseFloat(price).toLocaleString('nl-NL', {
                style: 'currency',
                currency: 'EUR'
            });
        },

        /**
         * Handle bulk actions
         */
        initBulkActions: function() {
            // Add custom bulk actions
            $('<option>').val('export_yoco').text('Exporteer naar CSV').appendTo('select[name="action"]');
            $('<option>').val('export_yoco').text('Exporteer naar CSV').appendTo('select[name="action2"]');
            
            // Handle bulk action
            $('#doaction, #doaction2').on('click', function(e) {
                const action = $(this).siblings('select').val();
                
                if (action === 'export_yoco') {
                    e.preventDefault();
                    
                    const selectedItems = $('input[name="post[]"]:checked');
                    if (selectedItems.length === 0) {
                        alert('Selecteer eerst items om te exporteren');
                        return;
                    }
                    
                    // Create export URL
                    const ids = selectedItems.map(function() {
                        return $(this).val();
                    }).get();
                    
                    window.location.href = ajaxurl + '?action=yoco_export_csv&ids=' + ids.join(',') + '&_wpnonce=' + $('#_wpnonce').val();
                }
            });
        }
    };

    /**
     * Settings Page Functionality
     */
    const YoCoSettings = {
        
        /**
         * Initialize settings page
         */
        init: function() {
            this.initIconUpload();
            this.initPreview();
            this.initFormChanges();
        },

        /**
         * Enhanced icon upload for settings page
         */
        initIconUpload: function() {
            $('.yoco-upload-icon-button').each(function() {
                const button = $(this);
                const input = button.siblings('input[type="text"]');
                
                // Add remove button if there's already an icon
                if (input.val()) {
                    YoCoSettings.addRemoveButton(button, input);
                }
            });
        },

        /**
         * Add remove button for icons
         */
        addRemoveButton: function(uploadButton, input) {
            if (uploadButton.siblings('.yoco-remove-icon').length === 0) {
                const removeButton = $('<button type="button" class="button yoco-remove-icon" data-target="' + input.attr('id') + '">Verwijderen</button>');
                uploadButton.after(removeButton);
            }
        },

        /**
         * Initialize live preview
         */
        initPreview: function() {
            // Preview order button text changes
            $('input[name="order_button_text"]').on('input', function() {
                const text = $(this).val() || 'Bestellen';
                $('.yoco-preview-button').text(text);
            });
            
            // Preview icon changes
            $('input[type="text"][name^="icon_"]').on('change', function() {
                const iconType = $(this).attr('name').replace('icon_', '');
                const url = $(this).val();
                
                if (url) {
                    $('.yoco-preview-' + iconType).attr('src', url).show();
                } else {
                    $('.yoco-preview-' + iconType).hide();
                }
            });
        },

        /**
         * Track form changes
         */
        initFormChanges: function() {
            let originalData = $('form').serialize();
            
            $('form input, form select, form textarea').on('change input', function() {
                const currentData = $('form').serialize();
                
                if (currentData !== originalData) {
                    $('.yoco-save-notice').remove();
                    $('.form-table').before('<div class="notice notice-warning yoco-save-notice"><p>Je hebt wijzigingen gemaakt. Vergeet niet om op te slaan!</p></div>');
                }
            });
            
            // Remove notice after save
            $('form').on('submit', function() {
                $('.yoco-save-notice').remove();
            });
            
            // Warn before leaving page with unsaved changes
            $(window).on('beforeunload', function(e) {
                const currentData = $('form').serialize();
                
                if (currentData !== originalData) {
                    const message = 'Je hebt niet-opgeslagen wijzigingen. Weet je zeker dat je de pagina wilt verlaten?';
                    e.returnValue = message;
                    return message;
                }
            });
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        YoCoAdmin.init();
        
        // Initialize settings page if we're on it
        if ($('body').hasClass('yoco_food_page_yoco-settings')) {
            YoCoSettings.init();
        }
        
        // Initialize bulk actions on list pages
        if ($('.tablenav-pages').length) {
            YoCoAdmin.initBulkActions();
        }
    });

    /**
     * Additional WordPress admin integration
     */
    
    // Handle WordPress media modal close
    $(document).on('click', '.media-modal-close', function() {
        if (typeof mediaUploader !== 'undefined') {
            mediaUploader = null;
        }
    });
    
    // Improve accessibility
    $('.yoco-upload-icon-button').attr('aria-label', 'Upload icon');
    $('.yoco-remove-icon').attr('aria-label', 'Remove icon');
    
    // Add keyboard navigation
    $('.yoco-spicy-rating input[type="radio"]').on('keydown', function(e) {
        const current = $(this);
        let next;
        
        switch(e.key) {
            case 'ArrowRight':
            case 'ArrowDown':
                e.preventDefault();
                next = current.closest('label').next().find('input[type="radio"]');
                if (next.length) {
                    next.focus().prop('checked', true).trigger('change');
                }
                break;
                
            case 'ArrowLeft':
            case 'ArrowUp':
                e.preventDefault();
                next = current.closest('label').prev().find('input[type="radio"]');
                if (next.length) {
                    next.focus().prop('checked', true).trigger('change');
                }
                break;
        }
    });

})(jQuery);