/**
 * ACF PHP-to-JSON Converter Admin Scripts
 */

(function ($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function () {
        // Initialize tabs
        initTabs();

        // Initialize scanner functionality
        initScanner();

        // Initialize converter functionality
        initConverter();

        // Initialize settings functionality
        initSettings();
    });

    /**
     * Initialize tabs functionality
     */
    function initTabs() {
        $('.acf-php-json-converter-tabs .nav-tab').on('click', function (e) {
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
     * Show notice with enhanced error handling
     * 
     * @param {string|object} message - Message to display or error object
     * @param {string} type - Notice type (success, error, warning, info)
     * @param {object} options - Additional options
     */
    function showNotice(message, type, options) {
        options = options || {};
        
        // Handle error objects
        if (typeof message === 'object' && message !== null) {
            return showErrorNotice(message, options);
        }

        // Remove existing notices if not persistent
        if (!options.persistent) {
            $('.acf-php-json-converter-notice').remove();
        }

        // Create notice HTML
        var noticeHtml = '<div class="acf-php-json-converter-notice acf-php-json-converter-notice-' + type + '">';
        
        // Add dismiss button if dismissible
        if (options.dismissible !== false) {
            noticeHtml += '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>';
        }
        
        // Add icon based on type
        var icon = getNoticeIcon(type);
        if (icon) {
            noticeHtml += '<span class="notice-icon">' + icon + '</span>';
        }
        
        noticeHtml += '<div class="notice-content">' + message + '</div>';
        noticeHtml += '</div>';

        var notice = $(noticeHtml);

        // Add notice to the page
        $('.acf-php-json-converter-wrap').prepend(notice);

        // Add dismiss functionality
        notice.find('.notice-dismiss').on('click', function() {
            notice.fadeOut(300, function() {
                notice.remove();
            });
        });

        // Auto-dismiss after delay if specified
        if (options.autoDismiss && options.autoDismiss > 0) {
            setTimeout(function() {
                notice.fadeOut(300, function() {
                    notice.remove();
                });
            }, options.autoDismiss);
        }

        // Scroll to notice
        $('html, body').animate({
            scrollTop: notice.offset().top - 50
        }, 500);

        return notice;
    }

    /**
     * Show enhanced error notice with recovery options
     * 
     * @param {object} errorData - Error data object
     * @param {object} options - Display options
     */
    function showErrorNotice(errorData, options) {
        options = options || {};
        
        var noticeHtml = '<div class="acf-php-json-converter-notice acf-php-json-converter-notice-error enhanced-error">';
        
        // Add dismiss button
        if (options.dismissible !== false) {
            noticeHtml += '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>';
        }
        
        // Error icon
        noticeHtml += '<span class="notice-icon"><span class="dashicons dashicons-warning" style="color: #dc3232;"></span></span>';
        
        noticeHtml += '<div class="notice-content">';
        noticeHtml += '<div class="error-message">' + errorData.message + '</div>';
        
        // Add error code if available
        if (errorData.error_code) {
            noticeHtml += '<div class="error-code">Error Code: <code>' + errorData.error_code + '</code></div>';
        }
        
        // Add recovery options if available
        if (errorData.recovery_options && errorData.recovery_options.length > 0) {
            noticeHtml += '<div class="recovery-options">';
            noticeHtml += '<h4>Suggested Solutions:</h4>';
            noticeHtml += '<ul>';
            errorData.recovery_options.forEach(function(option) {
                noticeHtml += '<li>';
                noticeHtml += '<strong>' + option.label + '</strong>';
                if (option.description) {
                    noticeHtml += '<br><span class="description">' + option.description + '</span>';
                }
                if (option.action) {
                    noticeHtml += '<br><button type="button" class="button button-small recovery-action" data-action="' + option.action + '">' + option.label + '</button>';
                }
                noticeHtml += '</li>';
            });
            noticeHtml += '</ul>';
            noticeHtml += '</div>';
        }
        
        // Add support info toggle
        if (errorData.support_info) {
            noticeHtml += '<div class="support-info-toggle">';
            noticeHtml += '<button type="button" class="button button-link toggle-support-info">Show Technical Details</button>';
            noticeHtml += '<div class="support-info" style="display: none;">';
            noticeHtml += '<h4>Technical Information:</h4>';
            noticeHtml += '<pre>' + JSON.stringify(errorData.support_info, null, 2) + '</pre>';
            noticeHtml += '</div>';
            noticeHtml += '</div>';
        }
        
        noticeHtml += '</div>';
        noticeHtml += '</div>';

        var notice = $(noticeHtml);

        // Add to page
        $('.acf-php-json-converter-wrap').prepend(notice);

        // Add event handlers
        notice.find('.notice-dismiss').on('click', function() {
            notice.fadeOut(300, function() {
                notice.remove();
            });
        });

        notice.find('.toggle-support-info').on('click', function() {
            var $this = $(this);
            var $supportInfo = notice.find('.support-info');
            
            if ($supportInfo.is(':visible')) {
                $supportInfo.slideUp();
                $this.text('Show Technical Details');
            } else {
                $supportInfo.slideDown();
                $this.text('Hide Technical Details');
            }
        });

        notice.find('.recovery-action').on('click', function() {
            var action = $(this).data('action');
            handleRecoveryAction(action, errorData);
        });

        // Scroll to notice
        $('html, body').animate({
            scrollTop: notice.offset().top - 50
        }, 500);

        return notice;
    }

    /**
     * Get icon for notice type
     * 
     * @param {string} type - Notice type
     * @return {string} Icon HTML
     */
    function getNoticeIcon(type) {
        var icons = {
            'success': '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>',
            'error': '<span class="dashicons dashicons-warning" style="color: #dc3232;"></span>',
            'warning': '<span class="dashicons dashicons-info" style="color: #ffb900;"></span>',
            'info': '<span class="dashicons dashicons-info-outline" style="color: #0073aa;"></span>'
        };
        
        return icons[type] || '';
    }

    /**
     * Handle recovery actions
     * 
     * @param {string} action - Recovery action
     * @param {object} errorData - Original error data
     */
    function handleRecoveryAction(action, errorData) {
        switch (action) {
            case 'retry':
                // Retry the last operation
                showNotice('Retrying operation...', 'info', { autoDismiss: 3000 });
                // Implementation depends on context
                break;
                
            case 'refresh_scan':
                // Trigger a fresh scan
                $('#force-refresh-btn').click();
                break;
                
            case 'individual_conversion':
                // Switch to individual conversion mode
                showNotice('Try converting field groups individually to identify the problematic item.', 'info');
                break;
                
            case 'download_file':
                // Offer download alternative
                showNotice('Use the download button to save files manually.', 'info');
                break;
                
            case 'reduce_batch_size':
                // Suggest reducing batch size
                showNotice('Try selecting fewer items for batch processing.', 'info');
                break;
                
            case 'contact_support':
                // Show support contact information
                showSupportModal(errorData);
                break;
                
            default:
                showNotice('Recovery action not implemented: ' + action, 'warning');
        }
    }

    /**
     * Show support modal with error details
     * 
     * @param {object} errorData - Error data for support
     */
    function showSupportModal(errorData) {
        var supportContent = '<div class="support-modal-content">';
        supportContent += '<p>If you continue to experience issues, please provide the following information when contacting support:</p>';
        supportContent += '<div class="support-details">';
        supportContent += '<h4>Error Details:</h4>';
        supportContent += '<textarea readonly rows="10" style="width: 100%; font-family: monospace; font-size: 12px;">';
        supportContent += JSON.stringify(errorData, null, 2);
        supportContent += '</textarea>';
        supportContent += '</div>';
        supportContent += '<div class="support-actions" style="margin-top: 15px;">';
        supportContent += '<button type="button" class="button button-primary copy-support-info">Copy to Clipboard</button>';
        supportContent += '</div>';
        supportContent += '</div>';

        showModal('Support Information', supportContent, function() {
            // Modal closed
        });

        // Add copy functionality
        $('.copy-support-info').on('click', function() {
            var textarea = $('.support-details textarea')[0];
            textarea.select();
            document.execCommand('copy');
            showNotice('Support information copied to clipboard!', 'success', { autoDismiss: 3000 });
        });
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
        $('.acf-php-json-converter-close, .acf-php-json-converter-modal-close').on('click', function () {
            modal.hide();
            modal.remove();

            if (typeof onClose === 'function') {
                onClose();
            }
        });

        // Close modal on escape key
        $(document).on('keyup', function (e) {
            if (e.key === 'Escape') {
                modal.hide();
                modal.remove();

                if (typeof onClose === 'function') {
                    onClose();
                }
            }
        });
    }

    /**
     * Initialize scanner functionality
     */
    function initScanner() {
        // Scan theme button click handler
        $('#scan-theme-btn').on('click', function () {
            performScan(false);
        });

        // Force refresh button click handler
        $('#force-refresh-btn').on('click', function () {
            performScan(true);
        });

        // Select all results checkbox handler
        $(document).on('change', '#select-all-results, #select-all-field-groups-table', function () {
            var isChecked = $(this).is(':checked');
            $('.field-group-result-checkbox').prop('checked', isChecked);
            updateBatchConvertResultsButton();
        });

        // Individual result checkbox handler
        $(document).on('change', '.field-group-result-checkbox', function () {
            updateBatchConvertResultsButton();

            // Update select all checkbox state
            var totalCheckboxes = $('.field-group-result-checkbox').length;
            var checkedCheckboxes = $('.field-group-result-checkbox:checked').length;

            $('#select-all-results, #select-all-field-groups-table').prop('checked', totalCheckboxes === checkedCheckboxes);
        });

        // Batch convert results button handler
        $('#batch-convert-results').on('click', function () {
            var selectedGroups = [];
            $('.field-group-result-checkbox:checked').each(function () {
                selectedGroups.push($(this).val());
            });

            if (selectedGroups.length === 0) {
                showNotice('Please select at least one field group to convert.', 'warning');
                return;
            }

            batchConvertFieldGroups(selectedGroups);
        });

        // Preview field group handler
        $(document).on('click', '.preview-field-group', function () {
            var fieldGroupKey = $(this).data('key');
            previewFieldGroup(fieldGroupKey);
        });

        // Download field group handler
        $(document).on('click', '.download-field-group', function () {
            var fieldGroupKey = $(this).data('key');
            downloadFieldGroup(fieldGroupKey);
        });

        // Convert field group handler
        $(document).on('click', '.convert-field-group', function () {
            var fieldGroupKey = $(this).data('key');
            convertFieldGroup(fieldGroupKey);
        });
    }

    /**
     * Perform theme scan
     */
    function performScan(forceRefresh) {
        var $scanButton = $('#scan-theme-btn');
        var $refreshButton = $('#force-refresh-btn');
        var $progress = $('#scan-progress');
        var $results = $('#scan-results');
        var $summary = $('#scan-summary');
        var $warnings = $('#scan-warnings');

        // Show progress and disable buttons
        $scanButton.prop('disabled', true);
        $refreshButton.prop('disabled', true);
        $progress.show();
        $results.hide();
        $summary.hide();
        $warnings.hide();

        // Update progress text
        $('.progress-text').text('Scanning theme files...');
        $('.progress-details').text('');

        // Start progress animation
        animateProgress($progress.find('.progress-fill'), 0, 90, 5000);

        // Make AJAX request
        $.ajax({
            url: acfPhpJsonConverter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'acf_php_json_scan_theme',
                nonce: acfPhpJsonConverter.nonce,
                force_refresh: forceRefresh ? 'true' : 'false'
            },
            success: function (response) {
                // Complete progress animation
                animateProgress($progress.find('.progress-fill'), 90, 100, 500);

                setTimeout(function () {
                    if (response.success) {
                        displayScanResults(response.data);

                        if (response.data.warnings && response.data.warnings.length > 0) {
                            displayScanWarnings(response.data.warnings);
                        }

                        showNotice(response.data.message, 'success', { autoDismiss: 5000 });
                    } else {
                        // Use enhanced error display
                        if (response.data && typeof response.data === 'object' && response.data.error_code) {
                            showNotice(response.data, 'error');
                        } else {
                            showNotice(response.data.message || 'Scan failed. Please try again.', 'error');
                        }

                        if (response.data.errors && response.data.errors.length > 0) {
                            displayScanWarnings(response.data.errors, 'error');
                        }
                    }
                }, 500);
            },
            error: function (xhr, status, error) {
                var errorData = {
                    error_code: 'network_error',
                    message: 'A network error occurred while scanning theme files: ' + error,
                    recovery_options: [
                        {
                            label: 'Retry Scan',
                            action: 'retry',
                            description: 'Try the scan operation again.'
                        },
                        {
                            label: 'Check Connection',
                            action: 'check_connection',
                            description: 'Verify your internet connection and try again.'
                        }
                    ]
                };
                showNotice(errorData, 'error');
            },
            complete: function () {
                setTimeout(function () {
                    $scanButton.prop('disabled', false);
                    $refreshButton.prop('disabled', false);
                    $progress.hide();
                }, 500);
            }
        });
    }

    /**
     * Initialize converter functionality
     */
    function initConverter() {
        // Conversion direction change handler
        $('input[name="conversion_direction"]').on('change', function () {
            var direction = $(this).val();

            if (direction === 'php_to_json') {
                $('#php-to-json-section').show();
                $('#json-to-php-section').hide();
            } else {
                $('#php-to-json-section').hide();
                $('#json-to-php-section').show();
            }
        });

        // Refresh field groups button handler
        $('#refresh-field-groups').on('click', function () {
            refreshFieldGroupsList();
        });

        // Switch to scanner tab handler
        $('.switch-to-scanner').on('click', function (e) {
            e.preventDefault();
            $('.acf-php-json-converter-tabs .nav-tab[href="#scanner-tab"]').click();
        });

        // Batch selection handlers
        $('#select-all-converter').on('click', function () {
            $('.field-group-checkbox').prop('checked', true);
            updateBatchControls();
        });

        $('#deselect-all-converter').on('click', function () {
            $('.field-group-checkbox').prop('checked', false);
            updateBatchControls();
        });

        // Individual checkbox handler
        $(document).on('change', '.field-group-checkbox', function () {
            updateBatchControls();
        });

        // Batch convert button handler
        $('#batch-convert-btn').on('click', function () {
            var selectedGroups = [];
            $('.field-group-checkbox:checked').each(function () {
                selectedGroups.push($(this).val());
            });

            if (selectedGroups.length === 0) {
                showNotice('Please select at least one field group to convert.', 'warning', { autoDismiss: 5000 });
                return;
            }

            startBatchConversion(selectedGroups);
        });

        // Export selected button handler
        $('#export-selected-btn').on('click', function () {
            var selectedGroups = [];
            $('.field-group-checkbox:checked').each(function () {
                selectedGroups.push($(this).val());
            });

            if (selectedGroups.length === 0) {
                showNotice('Please select at least one field group to export.', 'warning', { autoDismiss: 5000 });
                return;
            }

            exportSelectedFieldGroups(selectedGroups);
        });

        // Copy JSON handlers
        $(document).on('click', '.copy-json-btn', function () {
            var fieldGroupKey = $(this).data('key');
            copyFieldGroupJson(fieldGroupKey);
        });

        // Cancel batch button handler
        $('#cancel-batch').on('click', function () {
            cancelBatchConversion();
        });

        // Close results button handler
        $('#close-results').on('click', function () {
            closeBatchResults();
        });

        // JSON to PHP section handlers
        initJsonToPhpSection();
    }

    /**
     * Initialize JSON to PHP section
     */
    function initJsonToPhpSection() {
        // Input tab switching
        $('.input-tab').on('click', function () {
            var tabType = $(this).data('tab');

            $('.input-tab').removeClass('active');
            $('.input-content').removeClass('active');

            $(this).addClass('active');
            $('#' + tabType + '-input').addClass('active');
        });

        // JSON input validation
        $('#json-input').on('input', function () {
            validateJsonInput();
        });

        // File upload handlers
        $('#json-file-drop').on('click', function () {
            $('#json-file-input').click();
        });

        $('#json-file-input').on('change', function () {
            handleFileUpload(this.files[0]);
        });

        // Drag and drop handlers
        $('#json-file-drop').on('dragover', function (e) {
            e.preventDefault();
            $(this).addClass('dragover');
        });

        $('#json-file-drop').on('dragleave', function (e) {
            e.preventDefault();
            $(this).removeClass('dragover');
        });

        $('#json-file-drop').on('drop', function (e) {
            e.preventDefault();
            $(this).removeClass('dragover');

            var files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                handleFileUpload(files[0]);
            }
        });

        // Remove file handler
        $('#remove-file').on('click', function () {
            removeUploadedFile();
        });

        // Clear JSON input handler
        $('#clear-json-input').on('click', function () {
            clearJsonInput();
        });

        // Convert JSON to PHP handler
        $('#convert-json-btn').on('click', function () {
            convertJsonToPhp();
        });

        // Copy PHP code handler
        $('#copy-php-code').on('click', function () {
            copyPhpCodeToClipboard();
        });

        // Download PHP code handler
        $('#download-php-code').on('click', function () {
            downloadPhpCode();
        });
    }

    /**
     * Initialize settings functionality
     */
    function initSettings() {
        // Load settings on page load
        loadSettings();
        
        // Load error log on page load
        loadErrorLog();

        // Settings form submit handler
        $('#settings-form').on('submit', function (e) {
            e.preventDefault();
            saveSettings();
        });

        // Reset settings button handler
        $('#reset-settings-btn').on('click', function () {
            if (confirm('Are you sure you want to reset all settings to their default values?')) {
                resetSettings();
            }
        });

        // Load settings button handler
        $('#load-settings-btn').on('click', function () {
            loadSettings();
        });

        // Clear log button handler
        $('#clear-log-btn').on('click', function () {
            if (confirm('Are you sure you want to clear the error log?')) {
                clearErrorLog();
            }
        });

        // Refresh log button handler
        $('#refresh-log-btn').on('click', function () {
            loadErrorLog();
        });

        // Cleanup logs button handler
        $('#cleanup-logs-btn').on('click', function () {
            if (confirm('Are you sure you want to clean up old log entries? This will remove logs older than the retention period.')) {
                cleanupOldLogs();
            }
        });

        // Log level filter handler
        $('#log-level-filter').on('change', function () {
            filterLogEntries($(this).val());
        });
    }

    /**
     * Load plugin settings
     */
    function loadSettings() {
        var $loadButton = $('#load-settings-btn');
        
        $loadButton.prop('disabled', true);
        showSpinner($loadButton);

        $.ajax({
            url: acfPhpJsonConverter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'acf_php_json_load_settings',
                nonce: acfPhpJsonConverter.nonce
            },
            success: function (response) {
                if (response.success) {
                    populateSettingsForm(response.data.settings);
                    showNotice('Settings loaded successfully!', 'success');
                } else {
                    showNotice(response.data.message || 'Failed to load settings.', 'error');
                }
            },
            error: function () {
                showNotice('An error occurred while loading settings.', 'error');
            },
            complete: function () {
                $loadButton.prop('disabled', false);
                hideSpinner($loadButton);
            }
        });
    }

    /**
     * Save plugin settings
     */
    function saveSettings() {
        var $saveButton = $('#save-settings-btn');
        var $form = $('#settings-form');
        
        $saveButton.prop('disabled', true);
        showSpinner($saveButton);

        // Get form data
        var formData = {
            action: 'acf_php_json_save_settings',
            nonce: acfPhpJsonConverter.nonce,
            auto_create_json_folder: $('#auto_create_json_folder').is(':checked') ? '1' : '0',
            default_export_location: $('#default_export_location').val(),
            logging_level: $('#logging_level').val(),
            log_retention_days: $('#log_retention_days').val(),
            enable_debug_mode: $('#enable_debug_mode').is(':checked') ? '1' : '0',
            error_display_mode: $('#error_display_mode').val(),
            max_error_notices: $('#max_error_notices').val(),
            enable_error_recovery: $('#enable_error_recovery').is(':checked') ? '1' : '0',
            log_user_actions: $('#log_user_actions').is(':checked') ? '1' : '0'
        };

        $.ajax({
            url: acfPhpJsonConverter.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function (response) {
                if (response.success) {
                    showNotice('Settings saved successfully!', 'success');
                    
                    // Update form with returned settings
                    if (response.data.settings) {
                        populateSettingsForm(response.data.settings);
                    }
                } else {
                    var errorMessage = response.data.message || 'Failed to save settings.';
                    if (response.data.errors && response.data.errors.length > 0) {
                        errorMessage += '<br><ul>';
                        response.data.errors.forEach(function(error) {
                            errorMessage += '<li>' + error + '</li>';
                        });
                        errorMessage += '</ul>';
                    }
                    showNotice(errorMessage, 'error');
                }
            },
            error: function () {
                showNotice('An error occurred while saving settings.', 'error');
            },
            complete: function () {
                $saveButton.prop('disabled', false);
                hideSpinner($saveButton);
            }
        });
    }

    /**
     * Reset settings to defaults
     */
    function resetSettings() {
        var $resetButton = $('#reset-settings-btn');
        
        $resetButton.prop('disabled', true);
        showSpinner($resetButton);

        $.ajax({
            url: acfPhpJsonConverter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'acf_php_json_reset_settings',
                nonce: acfPhpJsonConverter.nonce
            },
            success: function (response) {
                if (response.success) {
                    populateSettingsForm(response.data.settings);
                    showNotice('Settings reset to defaults successfully!', 'success');
                } else {
                    showNotice(response.data.message || 'Failed to reset settings.', 'error');
                }
            },
            error: function () {
                showNotice('An error occurred while resetting settings.', 'error');
            },
            complete: function () {
                $resetButton.prop('disabled', false);
                hideSpinner($resetButton);
            }
        });
    }

    /**
     * Populate settings form with data
     */
    function populateSettingsForm(settings) {
        $('#auto_create_json_folder').prop('checked', settings.auto_create_json_folder);
        $('#default_export_location').val(settings.default_export_location);
        $('#logging_level').val(settings.logging_level);
        $('#log_retention_days').val(settings.log_retention_days);
        $('#enable_debug_mode').prop('checked', settings.enable_debug_mode);
        $('#error_display_mode').val(settings.error_display_mode);
        $('#max_error_notices').val(settings.max_error_notices);
        $('#enable_error_recovery').prop('checked', settings.enable_error_recovery);
        $('#log_user_actions').prop('checked', settings.log_user_actions);
    }

    /**
     * Load error log
     */
    function loadErrorLog() {
        var $refreshButton = $('#refresh-log-btn');
        var $logContainer = $('#error-log-container');
        
        $refreshButton.prop('disabled', true);
        showSpinner($refreshButton);
        
        // Show loading state
        $logContainer.html('<div class="log-loading"><span class="spinner is-active"></span><p>Loading error log...</p></div>');

        $.ajax({
            url: acfPhpJsonConverter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'acf_php_json_get_error_log',
                nonce: acfPhpJsonConverter.nonce
            },
            success: function (response) {
                if (response.success) {
                    displayErrorLog(response.data.log_entries);
                    updateLogStats(response.data.log_entries);
                } else {
                    $logContainer.html('<p class="error">Failed to load error log: ' + (response.data.message || 'Unknown error') + '</p>');
                }
            },
            error: function () {
                $logContainer.html('<p class="error">An error occurred while loading the error log.</p>');
            },
            complete: function () {
                $refreshButton.prop('disabled', false);
                hideSpinner($refreshButton);
            }
        });
    }

    /**
     * Display error log entries
     */
    function displayErrorLog(logEntries) {
        var $logContainer = $('#error-log-container');
        
        if (!logEntries || logEntries.length === 0) {
            $logContainer.html('<div class="no-log-entries"><p>No log entries found.</p></div>');
            return;
        }

        var logHtml = '<div class="log-entries">';
        
        logEntries.forEach(function(entry) {
            var levelClass = 'log-level-' + entry.level;
            var levelIcon = getLogLevelIcon(entry.level);
            
            logHtml += '<div class="log-entry ' + levelClass + '" data-level="' + entry.level + '">';
            logHtml += '<div class="log-header">';
            logHtml += '<span class="log-level-icon">' + levelIcon + '</span>';
            logHtml += '<span class="log-level">' + entry.level.toUpperCase() + '</span>';
            logHtml += '<span class="log-timestamp">' + entry.timestamp + '</span>';
            logHtml += '</div>';
            logHtml += '<div class="log-message">' + escapeHtml(entry.message) + '</div>';
            
            if (entry.context && Object.keys(entry.context).length > 0) {
                logHtml += '<div class="log-context">';
                logHtml += '<strong>Context:</strong> ';
                logHtml += '<pre>' + escapeHtml(JSON.stringify(entry.context, null, 2)) + '</pre>';
                logHtml += '</div>';
            }
            
            logHtml += '</div>';
        });
        
        logHtml += '</div>';
        
        $logContainer.html(logHtml);
    }

    /**
     * Get icon for log level
     */
    function getLogLevelIcon(level) {
        switch (level) {
            case 'error':
                return '<span class="dashicons dashicons-warning" style="color: #dc3232;"></span>';
            case 'warning':
                return '<span class="dashicons dashicons-info" style="color: #ffb900;"></span>';
            case 'info':
                return '<span class="dashicons dashicons-info-outline" style="color: #0073aa;"></span>';
            case 'debug':
                return '<span class="dashicons dashicons-admin-tools" style="color: #666;"></span>';
            default:
                return '<span class="dashicons dashicons-marker"></span>';
        }
    }

    /**
     * Update log statistics
     */
    function updateLogStats(logEntries) {
        var stats = {
            total: logEntries.length,
            error: 0,
            warning: 0,
            info: 0,
            debug: 0
        };

        logEntries.forEach(function(entry) {
            if (stats.hasOwnProperty(entry.level)) {
                stats[entry.level]++;
            }
        });

        $('#total-entries').text(stats.total);
        $('#error-count').text(stats.error);
        $('#warning-count').text(stats.warning);
        
        $('#log-stats').show();
    }

    /**
     * Filter log entries by level
     */
    function filterLogEntries(level) {
        var $entries = $('.log-entry');
        
        if (!level) {
            $entries.show();
        } else {
            $entries.hide();
            $('.log-entry[data-level="' + level + '"]').show();
        }
    }

    /**
     * Cleanup old logs
     */
    function cleanupOldLogs() {
        var $cleanupButton = $('#cleanup-logs-btn');
        
        $cleanupButton.prop('disabled', true);
        showSpinner($cleanupButton);

        $.ajax({
            url: acfPhpJsonConverter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'acf_php_json_cleanup_logs',
                nonce: acfPhpJsonConverter.nonce
            },
            success: function (response) {
                if (response.success) {
                    showNotice('Old logs cleaned up successfully!', 'success');
                    // Refresh the log display
                    loadErrorLog();
                } else {
                    showNotice(response.data.message || 'Failed to cleanup old logs.', 'error');
                }
            },
            error: function () {
                showNotice('An error occurred while cleaning up old logs.', 'error');
            },
            complete: function () {
                $cleanupButton.prop('disabled', false);
                hideSpinner($cleanupButton);
            }
        });
    }

    /**
     * Animate progress bar
     */
    function animateProgress($element, start, end, duration) {
        var startTime = Date.now();

        function updateProgress() {
            var elapsed = Date.now() - startTime;
            var progress = Math.min(elapsed / duration, 1);
            var currentValue = start + (end - start) * progress;

            $element.css('width', currentValue + '%');

            if (progress < 1) {
                requestAnimationFrame(updateProgress);
            }
        }

        updateProgress();
    }

    /**
     * Enhanced progress tracker for long-running operations
     * 
     * @param {string} operationId - Unique operation identifier
     * @param {object} options - Progress tracking options
     */
    function createProgressTracker(operationId, options) {
        options = $.extend({
            container: null,
            title: 'Processing...',
            showDetails: true,
            showEstimatedTime: true,
            updateInterval: 1000,
            onUpdate: null,
            onComplete: null,
            onError: null
        }, options);

        var tracker = {
            operationId: operationId,
            options: options,
            isRunning: false,
            updateTimer: null,
            container: null,

            start: function() {
                this.isRunning = true;
                this.createProgressUI();
                this.startPolling();
            },

            stop: function() {
                this.isRunning = false;
                if (this.updateTimer) {
                    clearInterval(this.updateTimer);
                    this.updateTimer = null;
                }
            },

            createProgressUI: function() {
                var progressHtml = '<div class="enhanced-progress-tracker" id="progress-' + this.operationId + '">';
                progressHtml += '<div class="progress-header">';
                progressHtml += '<h4 class="progress-title">' + this.options.title + '</h4>';
                progressHtml += '<button type="button" class="button button-secondary cancel-operation">Cancel</button>';
                progressHtml += '</div>';
                
                progressHtml += '<div class="progress-bar-container">';
                progressHtml += '<div class="progress-bar">';
                progressHtml += '<div class="progress-fill" style="width: 0%;"></div>';
                progressHtml += '</div>';
                progressHtml += '<div class="progress-percentage">0%</div>';
                progressHtml += '</div>';
                
                progressHtml += '<div class="progress-info">';
                progressHtml += '<div class="progress-status">Initializing...</div>';
                progressHtml += '<div class="progress-stats">';
                progressHtml += '<span class="current-item">0</span> / <span class="total-items">0</span>';
                if (this.options.showEstimatedTime) {
                    progressHtml += ' - <span class="estimated-time">Calculating...</span>';
                }
                progressHtml += '</div>';
                progressHtml += '</div>';
                
                if (this.options.showDetails) {
                    progressHtml += '<div class="progress-details">';
                    progressHtml += '<div class="progress-messages">';
                    progressHtml += '<ul class="message-list"></ul>';
                    progressHtml += '</div>';
                    progressHtml += '</div>';
                }
                
                progressHtml += '</div>';

                this.container = $(progressHtml);
                
                if (this.options.container) {
                    $(this.options.container).append(this.container);
                } else {
                    $('.acf-php-json-converter-wrap').append(this.container);
                }

                // Add cancel functionality
                this.container.find('.cancel-operation').on('click', function() {
                    tracker.cancel();
                });
            },

            startPolling: function() {
                var self = this;
                this.updateTimer = setInterval(function() {
                    self.updateProgress();
                }, this.options.updateInterval);
            },

            updateProgress: function() {
                var self = this;
                
                $.ajax({
                    url: acfPhpJsonConverter.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'acf_php_json_get_progress',
                        nonce: acfPhpJsonConverter.nonce,
                        operation_id: this.operationId
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            self.updateProgressUI(response.data);
                            
                            if (self.options.onUpdate) {
                                self.options.onUpdate(response.data);
                            }
                            
                            // Check if operation is complete
                            if (response.data.status === 'completed' || response.data.status === 'failed' || response.data.status === 'cancelled') {
                                self.stop();
                                self.handleCompletion(response.data);
                            }
                        }
                    },
                    error: function() {
                        if (self.options.onError) {
                            self.options.onError('Failed to get progress update');
                        }
                    }
                });
            },

            updateProgressUI: function(progressData) {
                if (!this.container) return;

                // Update progress bar
                var percentage = progressData.percentage || 0;
                this.container.find('.progress-fill').css('width', percentage + '%');
                this.container.find('.progress-percentage').text(Math.round(percentage) + '%');

                // Update status
                this.container.find('.progress-status').text(progressData.current_message || 'Processing...');

                // Update stats
                this.container.find('.current-item').text(progressData.current_progress || 0);
                this.container.find('.total-items').text(progressData.total_items || 0);

                // Update estimated time
                if (this.options.showEstimatedTime && progressData.estimated_completion) {
                    var estimatedTime = new Date(progressData.estimated_completion * 1000);
                    var now = new Date();
                    var remainingMs = estimatedTime.getTime() - now.getTime();
                    
                    if (remainingMs > 0) {
                        var remainingSeconds = Math.round(remainingMs / 1000);
                        var timeText = this.formatTime(remainingSeconds);
                        this.container.find('.estimated-time').text('ETA: ' + timeText);
                    }
                }

                // Update messages
                if (this.options.showDetails && progressData.messages) {
                    var messageList = this.container.find('.message-list');
                    
                    // Add new messages
                    progressData.messages.slice(-10).forEach(function(message) {
                        var messageClass = 'message-' + message.type;
                        var messageHtml = '<li class="' + messageClass + '">';
                        messageHtml += '<span class="message-time">' + message.timestamp + '</span>';
                        messageHtml += '<span class="message-text">' + message.message + '</span>';
                        messageHtml += '</li>';
                        
                        // Check if message already exists
                        var existingMessage = messageList.find('li:contains("' + message.message + '")');
                        if (existingMessage.length === 0) {
                            messageList.append(messageHtml);
                        }
                    });

                    // Keep only last 20 messages
                    var messages = messageList.find('li');
                    if (messages.length > 20) {
                        messages.slice(0, messages.length - 20).remove();
                    }

                    // Scroll to bottom
                    var messagesContainer = this.container.find('.progress-messages');
                    messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
                }
            },

            handleCompletion: function(progressData) {
                if (this.options.onComplete) {
                    this.options.onComplete(progressData);
                }

                // Update UI to show completion
                this.container.find('.cancel-operation').text('Close').off('click').on('click', function() {
                    tracker.container.fadeOut(300, function() {
                        tracker.container.remove();
                    });
                });

                // Show completion message
                var completionMessage = '';
                var messageType = 'success';

                if (progressData.status === 'completed') {
                    completionMessage = 'Operation completed successfully!';
                    messageType = 'success';
                } else if (progressData.status === 'failed') {
                    completionMessage = 'Operation failed. Please check the error log for details.';
                    messageType = 'error';
                } else if (progressData.status === 'cancelled') {
                    completionMessage = 'Operation was cancelled.';
                    messageType = 'warning';
                }

                if (completionMessage) {
                    showNotice(completionMessage, messageType, { autoDismiss: 5000 });
                }
            },

            cancel: function() {
                var self = this;
                
                $.ajax({
                    url: acfPhpJsonConverter.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'acf_php_json_cancel_operation',
                        nonce: acfPhpJsonConverter.nonce,
                        operation_id: this.operationId
                    },
                    success: function(response) {
                        if (response.success) {
                            self.stop();
                            showNotice('Operation cancelled successfully.', 'info');
                        } else {
                            showNotice('Failed to cancel operation: ' + (response.data.message || 'Unknown error'), 'error');
                        }
                    },
                    error: function() {
                        showNotice('Failed to cancel operation due to network error.', 'error');
                    }
                });
            },

            formatTime: function(seconds) {
                if (seconds < 60) {
                    return seconds + 's';
                } else if (seconds < 3600) {
                    var minutes = Math.floor(seconds / 60);
                    var remainingSeconds = seconds % 60;
                    return minutes + 'm ' + remainingSeconds + 's';
                } else {
                    var hours = Math.floor(seconds / 3600);
                    var remainingMinutes = Math.floor((seconds % 3600) / 60);
                    return hours + 'h ' + remainingMinutes + 'm';
                }
            }
        };

        return tracker;
    }

    /**
     * Display scan results
     */
    function displayScanResults(data) {
        var $results = $('#scan-results');
        var $tbody = $('#scan-results-tbody');
        var $summary = $('#scan-summary');
        var $noResults = $('#no-results');

        // Update summary statistics
        $('#field-groups-count').text(data.count || 0);
        $('#execution-time').text(data.execution_time || 0);
        $('#warnings-count').text((data.warnings && data.warnings.length) || 0);
        $('#scan-timestamp').text(data.timestamp || 'Unknown');
        
        // Count functions.php field groups
        var functionsPhpCount = 0;
        if (data.field_groups && data.field_groups.length > 0) {
            functionsPhpCount = data.field_groups.filter(function(group) {
                return group._acf_php_json_converter && 
                       group._acf_php_json_converter.source_type === 'functions_php';
            }).length;
        }
        
        $('#functions-php-count').text(functionsPhpCount);
        
        // Show functions.php info if any groups found
        if (functionsPhpCount > 0) {
            $('#functions-php-info').show();
        } else {
            $('#functions-php-info').hide();
        }

        $summary.show();
        $tbody.empty();

        if (data.field_groups && data.field_groups.length > 0) {
            data.field_groups.forEach(function (group) {
                var row = $('<tr>');

                // Checkbox column
                row.append('<td class="check-column">' +
                    '<input type="checkbox" class="field-group-result-checkbox" value="' + escapeHtml(group.key) + '">' +
                    '</td>');

                // Title column
                row.append('<td class="column-title">' +
                    '<strong>' + escapeHtml(group.title) + '</strong>' +
                    (group.has_location ? '<span class="dashicons dashicons-location" title="Has location rules" style="margin-left: 5px; color: #46b450;"></span>' : '') +
                    '</td>');

                // Key column
                row.append('<td class="column-key"><code>' + escapeHtml(group.key) + '</code></td>');

                // Source file column with special handling for functions.php
                var sourceFileHtml = '<span title="' + escapeHtml(group.source_file_full || group.source_file) + '">' +
                    escapeHtml(group.source_file);
                
                // Add special indicator for functions.php
                if (group._acf_php_json_converter && group._acf_php_json_converter.source_type === 'functions_php') {
                    sourceFileHtml += ' <span class="functions-php-badge" title="Defined in functions.php">functions.php</span>';
                }
                
                sourceFileHtml += '</span>';
                
                row.append('<td class="column-source">' + sourceFileHtml + '</td>');

                // Fields count column
                row.append('<td class="column-fields">' +
                    '<span class="field-count-badge">' + group.field_count + '</span>' +
                    '</td>');

                // Modified date column
                row.append('<td class="column-modified">' + escapeHtml(group.modified_date) + '</td>');

                // Actions column
                row.append('<td class="column-actions">' +
                    '<div class="row-actions">' +
                    '<button class="button button-small preview-field-group" data-key="' + escapeHtml(group.key) + '" title="Preview JSON output">' +
                    '<span class="dashicons dashicons-visibility"></span> Preview' +
                    '</button> ' +
                    '<button class="button button-small copy-json-btn" data-key="' + escapeHtml(group.key) + '" title="Copy JSON to clipboard">' +
                    '<span class="dashicons dashicons-clipboard"></span> Copy' +
                    '</button> ' +
                    '<button class="button button-small download-field-group" data-key="' + escapeHtml(group.key) + '" title="Download JSON file">' +
                    '<span class="dashicons dashicons-download"></span> Download' +
                    '</button> ' +
                    '<button class="button button-primary button-small convert-field-group" data-key="' + escapeHtml(group.key) + '" title="Convert and save to acf-json">' +
                    '<span class="dashicons dashicons-migrate"></span> Convert' +
                    '</button>' +
                    '</div>' +
                    '</td>');

                $tbody.append(row);
            });

            $noResults.hide();
            $results.show();

            // Update converter tab with field groups
            updateConverterFieldGroups(data.field_groups);
        } else {
            $noResults.show();
            $results.hide();
        }

        // Update batch convert button state
        updateBatchConvertResultsButton();
    }

    /**
     * Display scan warnings
     */
    function displayScanWarnings(warnings, type) {
        var $warnings = $('#scan-warnings');
        var $warningsList = $('#warnings-list');

        if (!warnings || warnings.length === 0) {
            $warnings.hide();
            return;
        }

        $warningsList.empty();

        warnings.forEach(function (warning) {
            var listItem = $('<li>');

            if (type === 'error') {
                listItem.addClass('error-item');
                listItem.html('<span class="dashicons dashicons-warning"></span> ' + escapeHtml(warning));
            } else {
                listItem.addClass('warning-item');
                listItem.html('<span class="dashicons dashicons-info"></span> ' + escapeHtml(warning));
            }

            $warningsList.append(listItem);
        });

        $warnings.show();
    }

    /**
     * Update batch convert results button state
     */
    function updateBatchConvertResultsButton() {
        var checkedCount = $('.field-group-result-checkbox:checked').length;
        var $button = $('#batch-convert-results');

        $button.prop('disabled', checkedCount === 0);

        if (checkedCount > 0) {
            $button.text('Convert Selected (' + checkedCount + ')');
        } else {
            $button.text('Convert Selected');
        }
    }

    /**
     * Update converter tab with field groups
     */
    function updateConverterFieldGroups(fieldGroups) {
        var $list = $('#field-groups-list');
        $list.empty();

        if (fieldGroups.length > 0) {
            var selectAllHtml = '<div class="field-group-item">' +
                '<input type="checkbox" id="select-all-field-groups"> ' +
                '<label for="select-all-field-groups"><strong>Select All</strong></label>' +
                '</div>';
            $list.append(selectAllHtml);

            fieldGroups.forEach(function (group) {
                var itemHtml = '<div class="field-group-item">' +
                    '<input type="checkbox" class="field-group-checkbox" value="' + group.key + '" id="fg-' + group.key + '"> ' +
                    '<div class="field-group-info">' +
                    '<div class="field-group-title">' + escapeHtml(group.title) + '</div>' +
                    '<div class="field-group-meta">Key: ' + escapeHtml(group.key) + ' | Fields: ' + group.field_count + '</div>' +
                    '</div>' +
                    '<div class="field-group-actions">' +
                    '<button class="button button-small preview-field-group" data-key="' + group.key + '">Preview</button>' +
                    '</div>' +
                    '</div>';
                $list.append(itemHtml);
            });
        } else {
            $list.html('<p>No field groups available. Please scan theme files first.</p>');
        }

        updateBatchConvertButton();
    }

    /**
     * Update batch convert button state
     */
    function updateBatchConvertButton() {
        var checkedCount = $('.field-group-checkbox:checked').length;
        $('#batch-convert-btn').prop('disabled', checkedCount === 0);
    }

    /**
     * Preview field group
     */
    function previewFieldGroup(fieldGroupKey) {
        $.ajax({
            url: acfPhpJsonConverter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'acf_php_json_preview_field_group',
                nonce: acfPhpJsonConverter.nonce,
                field_group_key: fieldGroupKey
            },
            success: function (response) {
                if (response.success) {
                    var content = '<div class="preview-content">' +
                        '<div class="preview-actions" style="margin-bottom: 15px; text-align: right;">' +
                        '<button class="button button-small" onclick="copyToClipboard(\'' + fieldGroupKey + '\')" title="Copy JSON to clipboard">' +
                        '<span class="dashicons dashicons-clipboard"></span> Copy' +
                        '</button> ' +
                        '<button class="button button-small" onclick="downloadFieldGroup(\'' + fieldGroupKey + '\')" title="Download JSON file">' +
                        '<span class="dashicons dashicons-download"></span> Download' +
                        '</button> ' +
                        '<button class="button button-primary button-small" onclick="convertFromPreview(\'' + fieldGroupKey + '\')" title="Convert and save to acf-json">' +
                        '<span class="dashicons dashicons-migrate"></span> Convert & Save' +
                        '</button>' +
                        '</div>' +
                        '<pre class="acf-php-json-converter-code" id="preview-json-content">' +
                        escapeHtml(JSON.stringify(response.data.json, null, 2)) +
                        '</pre>' +
                        '</div>';

                    showModal('Field Group Preview: ' + response.data.title, content);
                } else {
                    showNotice(response.data.message || 'Failed to preview field group.', 'error');
                }
            },
            error: function () {
                showNotice('An error occurred while previewing field group.', 'error');
            }
        });
    }

    /**
     * Download field group
     */
    function downloadFieldGroup(fieldGroupKey) {
        // Create a temporary form to trigger download
        var form = $('<form>', {
            method: 'POST',
            action: acfPhpJsonConverter.ajaxUrl,
            target: '_blank'
        });

        form.append($('<input>', {
            type: 'hidden',
            name: 'action',
            value: 'acf_php_json_download_field_group'
        }));

        form.append($('<input>', {
            type: 'hidden',
            name: 'nonce',
            value: acfPhpJsonConverter.nonce
        }));

        form.append($('<input>', {
            type: 'hidden',
            name: 'field_group_key',
            value: fieldGroupKey
        }));

        $('body').append(form);
        form.submit();
        form.remove();
    }

    /**
     * Convert field group
     */
    function convertFieldGroup(fieldGroupKey) {
        $.ajax({
            url: acfPhpJsonConverter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'acf_php_json_convert_php_to_json',
                nonce: acfPhpJsonConverter.nonce,
                field_group_key: fieldGroupKey
            },
            success: function (response) {
                if (response.success) {
                    showNotice('Field group converted successfully!', 'success');
                } else {
                    showNotice(response.data.message || 'Failed to convert field group.', 'error');
                }
            },
            error: function () {
                showNotice('An error occurred while converting field group.', 'error');
            }
        });
    }

    /**
     * Batch convert field groups
     */
    function batchConvertFieldGroups(fieldGroupKeys) {
        var $button = $('#batch-convert-btn');
        $button.prop('disabled', true).text('Converting...');

        $.ajax({
            url: acfPhpJsonConverter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'acf_php_json_batch_process',
                nonce: acfPhpJsonConverter.nonce,
                field_group_keys: fieldGroupKeys,
                direction: 'php_to_json'
            },
            success: function (response) {
                if (response.success) {
                    showNotice('Batch conversion completed successfully!', 'success');
                } else {
                    showNotice(response.data.message || 'Batch conversion failed.', 'error');
                }
            },
            error: function () {
                showNotice('An error occurred during batch conversion.', 'error');
            },
            complete: function () {
                $button.prop('disabled', false).text('Convert Selected');
            }
        });
    }

    /**
     * Refresh field groups list
     */
    function refreshFieldGroupsList() {
        var $button = $('#refresh-field-groups');
        var $list = $('#field-groups-list');

        $button.prop('disabled', true);
        showSpinner($button);

        // Get cached scan results
        $.ajax({
            url: acfPhpJsonConverter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'acf_php_json_scan_theme',
                nonce: acfPhpJsonConverter.nonce,
                force_refresh: 'false'
            },
            success: function (response) {
                if (response.success && response.data.field_groups) {
                    updateConverterFieldGroups(response.data.field_groups);
                    showNotice('Field groups list refreshed successfully!', 'success');
                } else {
                    $list.html('<div class="no-field-groups">' +
                        '<div class="no-results-icon"><span class="dashicons dashicons-search"></span></div>' +
                        '<h4>No Field Groups Available</h4>' +
                        '<p>Please scan theme files first to see available field groups for conversion.</p>' +
                        '<a href="#scanner-tab" class="button button-primary switch-to-scanner">Go to Scanner</a>' +
                        '</div>');
                }
            },
            error: function () {
                showNotice('Failed to refresh field groups list.', 'error');
            },
            complete: function () {
                $button.prop('disabled', false);
                hideSpinner($button);
            }
        });
    }

    /**
     * Update batch controls
     */
    function updateBatchControls() {
        var totalCheckboxes = $('.field-group-checkbox').length;
        var checkedCheckboxes = $('.field-group-checkbox:checked').length;
        var $batchControls = $('#batch-controls');
        var $selectedCount = $('#selected-count');
        var $batchButton = $('#batch-convert-btn');

        // Update selected count
        $selectedCount.text(checkedCheckboxes);

        // Show/hide batch controls
        if (totalCheckboxes > 0) {
            $batchControls.show();
        } else {
            $batchControls.hide();
        }

        // Enable/disable batch convert button
        $batchButton.prop('disabled', checkedCheckboxes === 0);

        if (checkedCheckboxes > 0) {
            $batchButton.html('<span class="dashicons dashicons-migrate"></span> Convert Selected (' + checkedCheckboxes + ')');
        } else {
            $batchButton.html('<span class="dashicons dashicons-migrate"></span> Convert Selected');
        }
    }

    /**
     * Start batch conversion
     */
    function startBatchConversion(fieldGroupKeys) {
        var $batchProgress = $('#batch-progress');
        var $batchResults = $('#batch-results');
        var $progressFill = $('.progress-fill');
        var $progressText = $('.progress-text');
        var $progressCurrent = $('#progress-current');
        var $progressTotal = $('#progress-total');
        var $progressLog = $('#progress-log');

        // Reset and show progress
        $batchProgress.show();
        $batchResults.hide();
        $progressFill.css('width', '0%');
        $progressCurrent.text('0');
        $progressTotal.text(fieldGroupKeys.length);
        $progressLog.empty();

        // Disable batch controls
        $('#batch-controls').hide();

        // Start conversion process
        var currentIndex = 0;
        var results = {
            success: [],
            errors: []
        };

        function processNext() {
            if (currentIndex >= fieldGroupKeys.length) {
                // All done, show results
                showBatchResults(results);
                return;
            }

            var fieldGroupKey = fieldGroupKeys[currentIndex];
            var progress = ((currentIndex + 1) / fieldGroupKeys.length) * 100;

            // Update progress
            $progressFill.css('width', progress + '%');
            $progressCurrent.text(currentIndex + 1);
            $progressText.text('Converting field group: ' + fieldGroupKey);

            // Add to progress log
            $progressLog.append('<li class="info">Converting: ' + escapeHtml(fieldGroupKey) + '</li>');
            $progressLog.scrollTop($progressLog[0].scrollHeight);

            // Convert field group
            $.ajax({
                url: acfPhpJsonConverter.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acf_php_json_convert_php_to_json',
                    nonce: acfPhpJsonConverter.nonce,
                    field_group_key: fieldGroupKey
                },
                success: function (response) {
                    if (response.success) {
                        results.success.push({
                            key: fieldGroupKey,
                            title: response.data.field_group_title || fieldGroupKey,
                            message: 'Converted successfully'
                        });
                        $progressLog.append('<li class="success">✓ ' + escapeHtml(fieldGroupKey) + ' - Success</li>');
                    } else {
                        results.errors.push({
                            key: fieldGroupKey,
                            title: fieldGroupKey,
                            message: response.data.message || 'Conversion failed'
                        });
                        $progressLog.append('<li class="error">✗ ' + escapeHtml(fieldGroupKey) + ' - Error: ' + escapeHtml(response.data.message || 'Unknown error') + '</li>');
                    }
                },
                error: function () {
                    results.errors.push({
                        key: fieldGroupKey,
                        title: fieldGroupKey,
                        message: 'Network error during conversion'
                    });
                    $progressLog.append('<li class="error">✗ ' + escapeHtml(fieldGroupKey) + ' - Network error</li>');
                },
                complete: function () {
                    $progressLog.scrollTop($progressLog[0].scrollHeight);
                    currentIndex++;

                    // Process next after a short delay
                    setTimeout(processNext, 500);
                }
            });
        }

        // Start processing
        processNext();
    }

    /**
     * Cancel batch conversion
     */
    function cancelBatchConversion() {
        if (confirm('Are you sure you want to cancel the batch conversion?')) {
            $('#batch-progress').hide();
            $('#batch-controls').show();
            showNotice('Batch conversion cancelled.', 'info');
        }
    }

    /**
     * Show batch results
     */
    function showBatchResults(results) {
        var $batchProgress = $('#batch-progress');
        var $batchResults = $('#batch-results');
        var $successCount = $('#success-count');
        var $errorCount = $('#error-count');
        var $totalProcessed = $('#total-processed');
        var $resultsLog = $('#results-log');

        // Hide progress, show results
        $batchProgress.hide();
        $batchResults.show();

        // Update summary stats
        $successCount.text(results.success.length);
        $errorCount.text(results.errors.length);
        $totalProcessed.text(results.success.length + results.errors.length);

        // Clear and populate results log
        $resultsLog.empty();

        // Add successful conversions
        results.success.forEach(function (item) {
            $resultsLog.append('<li class="success">' + escapeHtml(item.title) + ' - ' + escapeHtml(item.message) + '</li>');
        });

        // Add failed conversions
        results.errors.forEach(function (item) {
            $resultsLog.append('<li class="error">' + escapeHtml(item.title) + ' - ' + escapeHtml(item.message) + '</li>');
        });

        // Show overall result notice
        if (results.errors.length === 0) {
            showNotice('All field groups converted successfully!', 'success');
        } else if (results.success.length === 0) {
            showNotice('All conversions failed. Please check the error details.', 'error');
        } else {
            showNotice('Batch conversion completed with some errors. Check the results for details.', 'warning');
        }
    }

    /**
     * Close batch results
     */
    function closeBatchResults() {
        $('#batch-results').hide();
        $('#batch-controls').show();

        // Refresh the field groups list to reflect any changes
        refreshFieldGroupsList();
    }

    /**
     * Validate JSON input
     */
    function validateJsonInput() {
        var jsonText = $('#json-input').val().trim();
        var $validation = $('#json-validation');
        var $convertButton = $('#convert-json-btn');

        if (!jsonText) {
            $validation.hide();
            $convertButton.prop('disabled', true);
            return;
        }

        try {
            var parsed = JSON.parse(jsonText);

            // Enhanced ACF field group validation
            if (typeof parsed === 'object' && parsed !== null) {
                var validationErrors = [];

                // Check required fields
                if (!parsed.key) {
                    validationErrors.push('Missing required "key" field');
                }
                if (!parsed.title) {
                    validationErrors.push('Missing required "title" field');
                }

                // Check field group structure
                if (parsed.fields && !Array.isArray(parsed.fields)) {
                    validationErrors.push('"fields" must be an array');
                }

                // Check location rules if present
                if (parsed.location && !Array.isArray(parsed.location)) {
                    validationErrors.push('"location" must be an array');
                }

                if (validationErrors.length === 0) {
                    $validation.removeClass('invalid').addClass('valid').show();
                    var fieldCount = parsed.fields ? parsed.fields.length : 0;
                    $validation.find('.validation-message').text(
                        'Valid ACF field group detected (' + fieldCount + ' fields)'
                    );
                    $convertButton.prop('disabled', false);
                } else {
                    $validation.removeClass('valid').addClass('invalid').show();
                    $validation.find('.validation-message').text(
                        'Invalid ACF field group: ' + validationErrors.join(', ')
                    );
                    $convertButton.prop('disabled', true);
                }
            } else {
                $validation.removeClass('valid').addClass('invalid').show();
                $validation.find('.validation-message').text('JSON must be an object');
                $convertButton.prop('disabled', true);
            }
        } catch (e) {
            $validation.removeClass('valid').addClass('invalid').show();
            var errorMsg = e.message;
            // Make JSON error messages more user-friendly
            if (errorMsg.includes('Unexpected token')) {
                errorMsg = 'Syntax error in JSON - check for missing commas, quotes, or brackets';
            } else if (errorMsg.includes('Unexpected end')) {
                errorMsg = 'Incomplete JSON - missing closing brackets or quotes';
            }
            $validation.find('.validation-message').text('Invalid JSON: ' + errorMsg);
            $convertButton.prop('disabled', true);
        }
    }

    /**
     * Handle file upload
     */
    function handleFileUpload(file) {
        if (!file) return;

        // Validate file type
        if (file.type !== 'application/json' && !file.name.endsWith('.json')) {
            showNotice('Please select a valid JSON file.', 'error');
            return;
        }

        // Validate file size (2MB limit)
        if (file.size > 2 * 1024 * 1024) {
            showNotice('File size exceeds 2MB limit.', 'error');
            return;
        }

        var reader = new FileReader();
        reader.onload = function (e) {
            var content = e.target.result;

            // Set the content in the textarea
            $('#json-input').val(content);

            // Show file info
            $('#file-name').text(file.name);
            $('#file-size').text(formatFileSize(file.size));
            $('#file-info').show();
            $('#json-file-drop').hide();

            // Validate the JSON
            validateJsonInput();

            showNotice('File uploaded successfully!', 'success');
        };

        reader.onerror = function () {
            showNotice('Error reading file.', 'error');
        };

        reader.readAsText(file);
    }

    /**
     * Remove uploaded file
     */
    function removeUploadedFile() {
        $('#json-file-input').val('');
        $('#file-info').hide();
        $('#json-file-drop').show();
        $('#json-input').val('');
        $('#json-validation').hide();
        $('#convert-json-btn').prop('disabled', true);
    }

    /**
     * Clear JSON input
     */
    function clearJsonInput() {
        $('#json-input').val('');
        $('#json-validation').hide();
        $('#convert-json-btn').prop('disabled', true);
        $('#php-output').hide();
        removeUploadedFile();
    }

    /**
     * Convert JSON to PHP
     */
    function convertJsonToPhp() {
        var jsonInput = $('#json-input').val().trim();
        var $button = $('#convert-json-btn');

        if (!jsonInput) {
            showNotice('Please enter JSON data to convert.', 'warning');
            return;
        }

        $button.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Converting...');

        $.ajax({
            url: acfPhpJsonConverter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'acf_php_json_convert_json_to_php',
                nonce: acfPhpJsonConverter.nonce,
                json_input: jsonInput
            },
            success: function (response) {
                if (response.success) {
                    var phpCode = response.data.php_code;
                    $('#php-code-output').html(highlightPhpCode(phpCode));
                    $('#php-output').show();
                    showNotice('JSON converted to PHP successfully!', 'success');
                } else {
                    showNotice(response.data.message || 'Failed to convert JSON to PHP.', 'error');
                }
            },
            error: function () {
                showNotice('An error occurred while converting JSON to PHP.', 'error');
            },
            complete: function () {
                $button.prop('disabled', false).html('<span class="dashicons dashicons-editor-code"></span> Convert to PHP');
            }
        });
    }

    /**
     * Highlight PHP code with basic syntax highlighting
     * 
     * @param {string} code - PHP code to highlight
     * @return {string} - Highlighted HTML
     */
    function highlightPhpCode(code) {
        // Store original code for copying
        $('#php-code-output').data('original-code', code);

        // Escape HTML first
        var escaped = $('<div>').text(code).html();

        // Basic PHP syntax highlighting
        var highlighted = escaped
            // PHP tags
            .replace(/(&lt;\?php|&lt;\?|\?&gt;)/g, '<span class="php-tag">$1</span>')
            // Keywords
            .replace(/\b(function|class|public|private|protected|static|const|var|if|else|elseif|endif|while|for|foreach|endforeach|switch|case|default|break|continue|return|try|catch|finally|throw|new|extends|implements|interface|abstract|final|namespace|use|as|array|true|false|null)\b/g, '<span class="php-keyword">$1</span>')
            // Strings
            .replace(/(["'])((?:\\.|(?!\1)[^\\])*?)\1/g, '<span class="php-string">$1$2$1</span>')
            // Comments
            .replace(/(\/\/.*$|\/\*[\s\S]*?\*\/|#.*$)/gm, '<span class="php-comment">$1</span>')
            // Variables
            .replace(/(\$[a-zA-Z_][a-zA-Z0-9_]*)/g, '<span class="php-variable">$1</span>')
            // Numbers
            .replace(/\b(\d+\.?\d*)\b/g, '<span class="php-number">$1</span>')
            // Functions
            .replace(/\b([a-zA-Z_][a-zA-Z0-9_]*)\s*(?=\()/g, '<span class="php-function">$1</span>');

        return highlighted;
    }

    /**
     * Copy PHP code to clipboard
     */
    function copyPhpCodeToClipboard() {
        // Get original unformatted code
        var phpCode = $('#php-code-output').data('original-code') || $('#php-code-output').text();

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(phpCode).then(function () {
                showNotice('PHP code copied to clipboard!', 'success');
                // Add visual feedback
                $('#copy-php-code').addClass('copy-success');
                setTimeout(function () {
                    $('#copy-php-code').removeClass('copy-success');
                }, 300);
            }).catch(function (err) {
                console.error('Failed to copy to clipboard:', err);
                fallbackCopyToClipboard(phpCode);
            });
        } else {
            fallbackCopyToClipboard(phpCode);
        }
    }

    /**
     * Download PHP code
     */
    function downloadPhpCode() {
        // Get original unformatted code
        var phpCode = $('#php-code-output').data('original-code') || $('#php-code-output').text();
        var filename = 'acf-field-group.php';

        // Create blob and download
        var blob = new Blob([phpCode], { type: 'text/plain' });
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);

        showNotice('PHP code downloaded successfully!', 'success');
    }

    /**
     * Clear error log
     */
    function clearErrorLog() {
        var $clearButton = $('#clear-log-btn');
        
        $clearButton.prop('disabled', true);
        showSpinner($clearButton);

        $.ajax({
            url: acfPhpJsonConverter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'acf_php_json_clear_log',
                nonce: acfPhpJsonConverter.nonce
            },
            success: function (response) {
                if (response.success) {
                    $('#error-log-container').html('<div class="no-log-entries"><p>Error log cleared.</p></div>');
                    $('#log-stats').hide();
                    showNotice('Error log cleared successfully!', 'success');
                } else {
                    showNotice(response.data.message || 'Failed to clear error log.', 'error');
                }
            },
            error: function () {
                showNotice('An error occurred while clearing error log.', 'error');
            },
            complete: function () {
                $clearButton.prop('disabled', false);
                hideSpinner($clearButton);
            }
        });
    }

    /**
     * Copy JSON to clipboard
     */
    function copyToClipboard(fieldGroupKey) {
        var jsonContent = $('#preview-json-content').text();

        if (navigator.clipboard && window.isSecureContext) {
            // Use modern clipboard API
            navigator.clipboard.writeText(jsonContent).then(function () {
                showNotice('JSON copied to clipboard!', 'success');
            }).catch(function (err) {
                console.error('Failed to copy to clipboard:', err);
                fallbackCopyToClipboard(jsonContent);
            });
        } else {
            // Fallback for older browsers
            fallbackCopyToClipboard(jsonContent);
        }
    }

    /**
     * Fallback copy to clipboard method
     */
    function fallbackCopyToClipboard(text) {
        var textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            var successful = document.execCommand('copy');
            if (successful) {
                showNotice('JSON copied to clipboard!', 'success');
            } else {
                showNotice('Failed to copy to clipboard. Please copy manually.', 'warning');
            }
        } catch (err) {
            console.error('Fallback copy failed:', err);
            showNotice('Failed to copy to clipboard. Please copy manually.', 'warning');
        }

        document.body.removeChild(textArea);
    }

    /**
     * Convert field group from preview modal
     */
    function convertFromPreview(fieldGroupKey) {
        // Close the modal first
        $('.acf-php-json-converter-modal').hide().remove();

        // Call the convert function
        convertFieldGroup(fieldGroupKey);
    }

    /**
     * Format file size
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';

        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function (m) { return map[m]; });
    }

    // Expose functions to global scope
    window.acfPhpJsonConverterAdmin = {
        showNotice: showNotice,
        showSpinner: showSpinner,
        hideSpinner: hideSpinner,
        showModal: showModal,
        displayScanResults: displayScanResults,
        previewFieldGroup: previewFieldGroup,
        downloadFieldGroup: downloadFieldGroup,
        convertFieldGroup: convertFieldGroup,
        copyToClipboard: copyToClipboard,
        convertFromPreview: convertFromPreview
    };

    // Also expose functions directly to global scope for modal button onclick handlers
    window.copyToClipboard = copyToClipboard;
    window.downloadFieldGroup = downloadFieldGroup;
    window.convertFromPreview = convertFromPreview;

})(jQuery);
