<div class="settings-tab-content">
    <h2><?php _e('Plugin Settings', 'acf-php-json-converter'); ?></h2>
    <p><?php _e('Configure plugin behavior and preferences.', 'acf-php-json-converter'); ?></p>
    
    <form id="settings-form">
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
        </table>
        
        <p class="submit">
            <button type="submit" class="button button-primary"><?php _e('Save Settings', 'acf-php-json-converter'); ?></button>
        </p>
    </form>
    
    <div class="settings-section">
        <h3><?php _e('Error Log', 'acf-php-json-converter'); ?></h3>
        <div id="error-log-container">
            <p><?php _e('No errors logged yet.', 'acf-php-json-converter'); ?></p>
        </div>
        <button type="button" id="clear-log-btn" class="button"><?php _e('Clear Log', 'acf-php-json-converter'); ?></button>
    </div>
</div>