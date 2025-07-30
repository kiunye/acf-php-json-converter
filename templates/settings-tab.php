<div class="settings-tab-content">
    <h2><?php _e('Plugin Settings', 'acf-php-json-converter'); ?></h2>
    <p><?php _e('Configure plugin behavior and preferences.', 'acf-php-json-converter'); ?></p>
    
    <form id="settings-form">
        <?php wp_nonce_field('acf_php_json_converter_settings', 'settings_nonce'); ?>
        
        <h3><?php _e('General Settings', 'acf-php-json-converter'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="auto_create_json_folder"><?php _e('Auto-create Local JSON Folder', 'acf-php-json-converter'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="auto_create_json_folder" name="auto_create_json_folder" value="1" checked>
                    <p class="description"><?php _e('Automatically create the acf-json folder in your theme when converting to JSON.', 'acf-php-json-converter'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="default_export_location"><?php _e('Default Export Location', 'acf-php-json-converter'); ?></label>
                </th>
                <td>
                    <select id="default_export_location" name="default_export_location">
                        <option value="theme"><?php _e('Theme acf-json folder', 'acf-php-json-converter'); ?></option>
                        <option value="download"><?php _e('Download to browser', 'acf-php-json-converter'); ?></option>
                    </select>
                    <p class="description"><?php _e('Choose where converted JSON files should be saved by default.', 'acf-php-json-converter'); ?></p>
                </td>
            </tr>
        </table>

        <h3><?php _e('Logging Settings', 'acf-php-json-converter'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="logging_level"><?php _e('Logging Level', 'acf-php-json-converter'); ?></label>
                </th>
                <td>
                    <select id="logging_level" name="logging_level">
                        <option value="error"><?php _e('Error', 'acf-php-json-converter'); ?></option>
                        <option value="warning" selected><?php _e('Warning', 'acf-php-json-converter'); ?></option>
                        <option value="info"><?php _e('Info', 'acf-php-json-converter'); ?></option>
                        <option value="debug"><?php _e('Debug', 'acf-php-json-converter'); ?></option>
                    </select>
                    <p class="description"><?php _e('Set the minimum level for logging messages.', 'acf-php-json-converter'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="log_retention_days"><?php _e('Log Retention (Days)', 'acf-php-json-converter'); ?></label>
                </th>
                <td>
                    <input type="number" id="log_retention_days" name="log_retention_days" value="30" min="1" max="365" class="small-text">
                    <p class="description"><?php _e('Number of days to keep log entries (1-365 days).', 'acf-php-json-converter'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="enable_debug_mode"><?php _e('Enable Debug Mode', 'acf-php-json-converter'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="enable_debug_mode" name="enable_debug_mode" value="1">
                    <p class="description"><?php _e('Enable detailed debugging information. Only enable when troubleshooting issues.', 'acf-php-json-converter'); ?></p>
                </td>
            </tr>
        </table>

        <h3><?php _e('Error Handling Settings', 'acf-php-json-converter'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="error_display_mode"><?php _e('Error Display Mode', 'acf-php-json-converter'); ?></label>
                </th>
                <td>
                    <select id="error_display_mode" name="error_display_mode">
                        <option value="admin_notice"><?php _e('Admin Notice', 'acf-php-json-converter'); ?></option>
                        <option value="log_only" selected><?php _e('Log Only', 'acf-php-json-converter'); ?></option>
                        <option value="both"><?php _e('Both Notice and Log', 'acf-php-json-converter'); ?></option>
                    </select>
                    <p class="description"><?php _e('Choose how errors should be displayed to users.', 'acf-php-json-converter'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="max_error_notices"><?php _e('Max Error Notices', 'acf-php-json-converter'); ?></label>
                </th>
                <td>
                    <input type="number" id="max_error_notices" name="max_error_notices" value="3" min="1" max="10" class="small-text">
                    <p class="description"><?php _e('Maximum number of error notices to show per page load (1-10).', 'acf-php-json-converter'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="enable_error_recovery"><?php _e('Enable Error Recovery', 'acf-php-json-converter'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="enable_error_recovery" name="enable_error_recovery" value="1" checked>
                    <p class="description"><?php _e('Attempt to recover from non-critical errors automatically.', 'acf-php-json-converter'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="log_user_actions"><?php _e('Log User Actions', 'acf-php-json-converter'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="log_user_actions" name="log_user_actions" value="1">
                    <p class="description"><?php _e('Log user actions for debugging purposes (may increase log size).', 'acf-php-json-converter'); ?></p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" class="button button-primary" id="save-settings-btn">
                <span class="dashicons dashicons-yes"></span>
                <?php _e('Save Settings', 'acf-php-json-converter'); ?>
            </button>
            <button type="button" class="button" id="reset-settings-btn">
                <span class="dashicons dashicons-undo"></span>
                <?php _e('Reset to Defaults', 'acf-php-json-converter'); ?>
            </button>
            <button type="button" class="button" id="load-settings-btn">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Reload Settings', 'acf-php-json-converter'); ?>
            </button>
        </p>
    </form>
    
    <div class="settings-section">
        <h3><?php _e('Error Log Management', 'acf-php-json-converter'); ?></h3>
        <div class="log-controls">
            <button type="button" id="refresh-log-btn" class="button">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Refresh Log', 'acf-php-json-converter'); ?>
            </button>
            <button type="button" id="clear-log-btn" class="button">
                <span class="dashicons dashicons-trash"></span>
                <?php _e('Clear Log', 'acf-php-json-converter'); ?>
            </button>
            <button type="button" id="cleanup-logs-btn" class="button">
                <span class="dashicons dashicons-admin-tools"></span>
                <?php _e('Cleanup Old Logs', 'acf-php-json-converter'); ?>
            </button>
            <select id="log-level-filter">
                <option value=""><?php _e('All Levels', 'acf-php-json-converter'); ?></option>
                <option value="error"><?php _e('Errors Only', 'acf-php-json-converter'); ?></option>
                <option value="warning"><?php _e('Warnings Only', 'acf-php-json-converter'); ?></option>
                <option value="info"><?php _e('Info Only', 'acf-php-json-converter'); ?></option>
                <option value="debug"><?php _e('Debug Only', 'acf-php-json-converter'); ?></option>
            </select>
        </div>
        
        <div id="error-log-container">
            <div class="log-loading">
                <span class="spinner"></span>
                <p><?php _e('Loading error log...', 'acf-php-json-converter'); ?></p>
            </div>
        </div>
        
        <div id="log-stats" class="log-stats" style="display: none;">
            <p>
                <strong><?php _e('Log Statistics:', 'acf-php-json-converter'); ?></strong>
                <span id="total-entries">0</span> <?php _e('entries', 'acf-php-json-converter'); ?> |
                <span id="error-count">0</span> <?php _e('errors', 'acf-php-json-converter'); ?> |
                <span id="warning-count">0</span> <?php _e('warnings', 'acf-php-json-converter'); ?>
            </p>
        </div>
    </div>
</div>