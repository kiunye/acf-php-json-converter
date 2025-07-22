<div class="scanner-tab-content">
    <h2><?php _e('Theme File Scanner', 'acf-php-json-converter'); ?></h2>
    <p><?php _e('Scan your theme files for ACF field groups defined in PHP using acf_add_local_field_group().', 'acf-php-json-converter'); ?></p>
    
    <div class="scanner-controls">
        <button type="button" id="scan-theme-btn" class="button button-primary">
            <span class="dashicons dashicons-search" style="margin-right: 5px;"></span>
            <?php _e('Scan Theme Files', 'acf-php-json-converter'); ?>
        </button>
        <button type="button" id="force-refresh-btn" class="button" style="margin-left: 10px;">
            <span class="dashicons dashicons-update" style="margin-right: 5px;"></span>
            <?php _e('Force Refresh', 'acf-php-json-converter'); ?>
        </button>
        
        <div id="scan-progress" class="scan-progress" style="display: none;">
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <div class="progress-info">
                <span class="progress-text"><?php _e('Scanning theme files...', 'acf-php-json-converter'); ?></span>
                <span class="progress-details"></span>
            </div>
        </div>
    </div>
    
    <div id="scan-summary" class="scan-summary" style="display: none;">
        <div class="scan-stats">
            <div class="stat-item">
                <span class="stat-number" id="field-groups-count">0</span>
                <span class="stat-label"><?php _e('Field Groups Found', 'acf-php-json-converter'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number" id="execution-time">0</span>
                <span class="stat-label"><?php _e('Execution Time (s)', 'acf-php-json-converter'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number" id="warnings-count">0</span>
                <span class="stat-label"><?php _e('Warnings', 'acf-php-json-converter'); ?></span>
            </div>
        </div>
        <div class="scan-timestamp">
            <?php _e('Last scan:', 'acf-php-json-converter'); ?> <span id="scan-timestamp"></span>
        </div>
    </div>
    
    <div id="scan-results" class="scan-results" style="display: none;">
        <div class="results-header">
            <h3><?php _e('Scan Results', 'acf-php-json-converter'); ?></h3>
            <div class="results-actions">
                <button type="button" id="select-all-results" class="button button-small">
                    <?php _e('Select All', 'acf-php-json-converter'); ?>
                </button>
                <button type="button" id="batch-convert-results" class="button button-primary button-small" disabled>
                    <?php _e('Convert Selected', 'acf-php-json-converter'); ?>
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="select-all-field-groups-table">
                        </td>
                        <th class="manage-column column-title"><?php _e('Field Group', 'acf-php-json-converter'); ?></th>
                        <th class="manage-column column-key"><?php _e('Key', 'acf-php-json-converter'); ?></th>
                        <th class="manage-column column-source"><?php _e('Source File', 'acf-php-json-converter'); ?></th>
                        <th class="manage-column column-fields"><?php _e('Fields', 'acf-php-json-converter'); ?></th>
                        <th class="manage-column column-modified"><?php _e('Modified', 'acf-php-json-converter'); ?></th>
                        <th class="manage-column column-actions"><?php _e('Actions', 'acf-php-json-converter'); ?></th>
                    </tr>
                </thead>
                <tbody id="scan-results-tbody">
                    <!-- Results will be populated via JavaScript -->
                </tbody>
            </table>
        </div>
        
        <div id="no-results" class="no-results" style="display: none;">
            <div class="no-results-icon">
                <span class="dashicons dashicons-search"></span>
            </div>
            <h3><?php _e('No Field Groups Found', 'acf-php-json-converter'); ?></h3>
            <p><?php _e('No ACF field groups were found in your theme files. Make sure you have field groups defined using acf_add_local_field_group() in your theme.', 'acf-php-json-converter'); ?></p>
        </div>
    </div>
    
    <div id="scan-warnings" class="scan-warnings" style="display: none;">
        <h4><?php _e('Warnings', 'acf-php-json-converter'); ?></h4>
        <ul id="warnings-list"></ul>
    </div>
</div>