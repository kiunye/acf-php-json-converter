/**
 * ACF PHP-to-JSON Converter Admin Scripts
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize tabs
        initTabs();
    });

    /**
     * Initialize tabs functionality
     */
    function initTabs() {
        $('.acf-php-json-converter-tabs .nav-tab').on('click', function(e) {
            e.preventDefault();
            
            // Get the tab ID
            var tabId = $(this).attr('href');
            
            // Remove active class from all tabs and tab content
            $('.acf-php-json-converter-tabs .nav-tab').removeClass('nav-tab-active');
            $('.acf-php-json-converter-tab-content').removeClass('active');
            
            // Add active class to current tab and tab content
            $(this).addClass('nav-tab-active');
            $(tabId).addClass('active');
        });
    }

    /**
     * Show notice
     * 
     * @param {string} message - Message to display
     * @param {string} type - Notice type (success, error, warning, info)
     */
    function showNotice(message, type) {
        // Remove existing notices
        $('.acf-php-json-converter-notice').remove();
        
        // Create new notice
        var notice = $('<div class="acf-php-json-converter-notice acf-php-json-converter-notice-' + type + '">' + message + '</div>');
        
        // Add notice to the page
        $('.acf-php-json-converter-wrap').prepend(notice);
        
        // Scroll to notice
        $('html, body').animate({
            scrollTop: notice.offset().top - 50
        }, 500);
    }

    /**
     * Show spinner
     * 
     * @param {jQuery} element - Element to show spinner next to
     */
    function showSpinner(element) {
        // Create spinner if it doesn't exist
        if (!element.next('.acf-php-json-converter-spinner').length) {
            element.after('<span class="acf-php-json-converter-spinner"></span>');
        }
        
        // Show spinner
        element.next('.acf-php-json-converter-spinner').addClass('is-active');
    }

    /**
     * Hide spinner
     * 
     * @param {jQuery} element - Element with spinner
     */
    function hideSpinner(element) {
        element.next('.acf-php-json-converter-spinner').removeClass('is-active');
    }

    /**
     * Show modal
     * 
     * @param {string} title - Modal title
     * @param {string} content - Modal content
     * @param {function} onClose - Callback function when modal is closed
     */
    function showModal(title, content, onClose) {
        // Remove existing modal
        $('.acf-php-json-converter-modal').remove();
        
        // Create modal
        var modal = $(
            '<div class="acf-php-json-converter-modal">' +
                '<div class="acf-php-json-converter-modal-content">' +
                    '<div class="acf-php-json-converter-modal-header">' +
                        '<span class="acf-php-json-converter-close">&times;</span>' +
                        '<h2>' + title + '</h2>' +
                    '</div>' +
                    '<div class="acf-php-json-converter-modal-body">' +
                        content +
                    '</div>' +
                    '<div class="acf-php-json-converter-modal-footer">' +
                        '<button class="button button-primary acf-php-json-converter-modal-close">Close</button>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );
        
        // Add modal to the page
        $('body').append(modal);
        
        // Show modal
        modal.show();
        
        // Close modal on click
        $('.acf-php-json-converter-close, .acf-php-json-converter-modal-close').on('click', function() {
            modal.hide();
            modal.remove();
            
            if (typeof onClose === 'function') {
                onClose();
            }
        });
        
        // Close modal on escape key
        $(document).on('keyup', function(e) {
            if (e.key === 'Escape') {
                modal.hide();
                modal.remove();
                
                if (typeof onClose === 'function') {
                    onClose();
                }
            }
        });
    }

    // Expose functions to global scope
    window.acfPhpJsonConverterAdmin = {
        showNotice: showNotice,
        showSpinner: showSpinner,
        hideSpinner: hideSpinner,
        showModal: showModal
    };

})(jQuery);