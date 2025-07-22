/**
 * ACF PHP-to-JSON Converter Admin Scripts
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
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

    /**
     * Initialize scanner functionality
     */
    function initScanner() {
        // Scan theme button click handler
        $('#scan-theme-btn').on('click', function() {
            performScan(false);
        });
        
        // Force refresh button click handler
        $('#force-refresh-btn').on('click', function() {
            performScan(true);
        });
        
        // Select all results checkbox handler
        $(document).on('change', '#select-all-results, #select-all-field-groups-table', function() {
            var isChecked = $(this).is(':checked');
            $('.field-group-result-checkbox').prop('checked', isChecked);
            updateBatchConvertResultsButton();
        });
        
        // Individual result checkbox handler
        $(document).on('change', '.field-group-result-checkbox', function() {
            updateBatchConvertResultsButton();
            
            // Update select all checkbox state
            var totalCheckboxes = $('.field-group-result-checkbox').length;
            var checkedCheckboxes = $('.field-group-result-checkbox:checked').length;
            
            $('#select-all-results, #select-all-field-groups-table').prop('checked', totalCheckboxes === checkedCheckboxes);
        });
        
        // Batch convert results button handler
        $('#batch-convert-results').on('click', function() {
            var selectedGroups = [];
            $('.field-group-result-checkbox:checked').each(function() {
                selectedGroups.push($(this).val());
            });
            
            if (selectedGroups.length === 0) {
                showNotice('Please select at least one field group to convert.', 'warning');
                return;
            }
            
            batchConvertFieldGroups(selectedGroups);
        });
        
        // Preview field group handler
        $(document).on('click', '.preview-field-group', function() {
            var fieldGroupKey = $(this).data('key');
            previewFieldGroup(fieldGroupKey);
        });
        
        // Download field group handler
        $(document).on('click', '.download-field-group', function() {
            var fieldGroupKey = $(this).data('key');
            downloadFieldGroup(fieldGroupKey);
        });
        
        // Convert field group handler
        $(document).on('click', '.convert-field-group', function() {
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
            success: function(response) {
                // Complete progress animation
                animateProgress($progress.find('.progress-fill'), 90, 100, 500);
                
                setTimeout(function() {
                    if (response.success) {
                        displayScanResults(response.data);
                        
                        if (response.data.warnings && response.data.warnings.length > 0) {
                            displayScanWarnings(response.data.warnings);
                        }
                        
                        showNotice(response.data.message, 'success');
                    } else {
                        showNotice(response.data.message || 'Scan failed. Please try again.', 'error');
                        
                        if (response.data.errors && response.data.errors.length > 0) {
                            displayScanWarnings(response.data.errors, 'error');
                        }
                    }
                }, 500);
            },
            error: function(xhr, status, error) {
                showNotice('An error occurred while scanning: ' + error, 'error');
            },
            complete: function() {
                setTimeout(function() {
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
        $('input[name="conversion_direction"]').on('change', function() {
            var direction = $(this).val();
            
            if (direction === 'php_to_json') {
                $('#php-to-json-section').show();
                $('#json-to-php-section').hide();
            } else {
                $('#php-to-json-section').hide();
                $('#json-to-php-section').show();
            }
        });
        
        // Batch convert button handler
        $('#batch-convert-btn').on('click', function() {
            var selectedGroups = [];
            $('.field-group-checkbox:checked').each(function() {
                selectedGroups.push($(this).val());
            });
            
            if (selectedGroups.length === 0) {
                showNotice('Please select at least one field group to convert.', 'warning');
                return;
            }
            
            batchConvertFieldGroups(selectedGroups);
        });
        
        // Convert JSON to PHP handler
        $('#convert-json-btn').on('click', function() {
            var jsonInput = $('#json-input').val().trim();
            
            if (!jsonInput) {
                showNotice('Please enter JSON data to convert.', 'warning');
                return;
            }
            
            convertJsonToPhp(jsonInput);
        });
        
        // Select all checkbox handler
        $(document).on('change', '#select-all-field-groups', function() {
            $('.field-group-checkbox').prop('checked', $(this).is(':checked'));
            updateBatchConvertButton();
        });
        
        // Individual checkbox handler
        $(document).on('change', '.field-group-checkbox', function() {
            updateBatchConvertButton();
        });
    }
    
    /**
     * Initialize settings functionality
     */
    function initSettings() {
        // Settings form submit handler
        $('#settings-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            
            $.ajax({
                url: acfPhpJsonConverter.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acf_php_json_save_settings',
                    nonce: acfPhpJsonConverter.nonce,
                    settings: formData
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('Settings saved successfully!', 'success');
                    } else {
                        showNotice(response.data.message || 'Failed to save settings.', 'error');
                    }
                },
                error: function() {
                    showNotice('An error occurred while saving settings.', 'error');
                }
            });
        });
        
        // Clear log button handler
        $('#clear-log-btn').on('click', function() {
            if (confirm('Are you sure you want to clear the error log?')) {
                clearErrorLog();
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
        
        $summary.show();
        $tbody.empty();
        
        if (data.field_groups && data.field_groups.length > 0) {
            data.field_groups.forEach(function(group) {
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
                
                // Source file column
                row.append('<td class="column-source">' +
                    '<span title="' + escapeHtml(group.source_file_full || group.source_file) + '">' +
                    escapeHtml(group.source_file) +
                    '</span>' +
                '</td>');
                
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
        
        warnings.forEach(function(warning) {
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
            
            fieldGroups.forEach(function(group) {
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
            success: function(response) {
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
            error: function() {
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
            success: function(response) {
                if (response.success) {
                    showNotice('Field group converted successfully!', 'success');
                } else {
                    showNotice(response.data.message || 'Failed to convert field group.', 'error');
                }
            },
            error: function() {
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
            success: function(response) {
                if (response.success) {
                    showNotice('Batch conversion completed successfully!', 'success');
                } else {
                    showNotice(response.data.message || 'Batch conversion failed.', 'error');
                }
            },
            error: function() {
                showNotice('An error occurred during batch conversion.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text('Convert Selected');
            }
        });
    }
    
    /**
     * Convert JSON to PHP
     */
    function convertJsonToPhp(jsonInput) {
        var $button = $('#convert-json-btn');
        $button.prop('disabled', true).text('Converting...');
        
        $.ajax({
            url: acfPhpJsonConverter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'acf_php_json_convert_json_to_php',
                nonce: acfPhpJsonConverter.nonce,
                json_input: jsonInput
            },
            success: function(response) {
                if (response.success) {
                    $('#php-code-output').text(response.data.php_code);
                    $('#php-output').show();
                    showNotice('JSON converted to PHP successfully!', 'success');
                } else {
                    showNotice(response.data.message || 'Failed to convert JSON to PHP.', 'error');
                }
            },
            error: function() {
                showNotice('An error occurred while converting JSON to PHP.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text('Convert to PHP');
            }
        });
    }
    
    /**
     * Clear error log
     */
    function clearErrorLog() {
        $.ajax({
            url: acfPhpJsonConverter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'acf_php_json_clear_log',
                nonce: acfPhpJsonConverter.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#error-log-container').html('<p>Error log cleared.</p>');
                    showNotice('Error log cleared successfully!', 'success');
                } else {
                    showNotice(response.data.message || 'Failed to clear error log.', 'error');
                }
            },
            error: function() {
                showNotice('An error occurred while clearing error log.', 'error');
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
            navigator.clipboard.writeText(jsonContent).then(function() {
                showNotice('JSON copied to clipboard!', 'success');
            }).catch(function(err) {
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
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
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