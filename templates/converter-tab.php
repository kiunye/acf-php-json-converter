<div class="converter-tab-content">
    <h2><?php _e('Field Group Converter', 'acf-php-json-converter'); ?></h2>
    <p><?php _e('Convert between PHP and JSON formats for ACF field groups.', 'acf-php-json-converter'); ?></p>
    
    <!-- Conversion Direction Selection -->
    <div class="conversion-direction">
        <h3><?php _e('Conversion Direction', 'acf-php-json-converter'); ?></h3>
        <div class="direction-options">
            <label class="direction-option">
                <input type="radio" name="conversion_direction" value="php_to_json" checked>
                <span class="direction-label">
                    <span class="dashicons dashicons-migrate"></span>
                    <strong><?php _e('PHP to JSON', 'acf-php-json-converter'); ?></strong>
                    <small><?php _e('Convert PHP field groups to JSON format for Local JSON sync', 'acf-php-json-converter'); ?></small>
                </span>
            </label>
            <label class="direction-option">
                <input type="radio" name="conversion_direction" value="json_to_php">
                <span class="direction-label">
                    <span class="dashicons dashicons-editor-code"></span>
                    <strong><?php _e('JSON to PHP', 'acf-php-json-converter'); ?></strong>
                    <small><?php _e('Convert JSON field groups to PHP code for theme integration', 'acf-php-json-converter'); ?></small>
                </span>
            </label>
        </div>
    </div>
    
    <!-- PHP to JSON Section -->
    <div id="php-to-json-section" class="conversion-section">
        <div class="section-header">
            <h3><?php _e('Select Field Groups to Convert', 'acf-php-json-converter'); ?></h3>
            <div class="section-actions">
                <button type="button" id="refresh-field-groups" class="button button-secondary">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Refresh List', 'acf-php-json-converter'); ?>
                </button>
            </div>
        </div>
        
        <div id="field-groups-container">
            <div id="field-groups-list" class="field-groups-list">
                <div class="no-field-groups">
                    <div class="no-results-icon">
                        <span class="dashicons dashicons-search"></span>
                    </div>
                    <h4><?php _e('No Field Groups Available', 'acf-php-json-converter'); ?></h4>
                    <p><?php _e('Please scan theme files first to see available field groups for conversion.', 'acf-php-json-converter'); ?></p>
                    <a href="#scanner-tab" class="button button-primary switch-to-scanner">
                        <?php _e('Go to Scanner', 'acf-php-json-converter'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Batch Processing Controls -->
        <div id="batch-controls" class="batch-controls" style="display: none;">
            <div class="batch-actions">
                <div class="selection-info">
                    <span id="selected-count">0</span> <?php _e('field groups selected', 'acf-php-json-converter'); ?>
                </div>
                <div class="batch-buttons">
                    <button type="button" id="select-all-converter" class="button button-secondary">
                        <?php _e('Select All', 'acf-php-json-converter'); ?>
                    </button>
                    <button type="button" id="deselect-all-converter" class="button button-secondary">
                        <?php _e('Deselect All', 'acf-php-json-converter'); ?>
                    </button>
                    <button type="button" id="batch-convert-btn" class="button button-primary" disabled>
                        <span class="dashicons dashicons-migrate"></span>
                        <?php _e('Convert Selected', 'acf-php-json-converter'); ?>
                    </button>
                    <button type="button" id="export-selected-btn" class="button button-secondary" disabled>
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export Selected', 'acf-php-json-converter'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Enhanced Progress Container -->
        <div id="batch-progress-container"></div>
        
        <!-- Batch Progress -->
        <div id="batch-progress" class="batch-progress" style="display: none;">
            <div class="progress-header">
                <h4><?php _e('Converting Field Groups...', 'acf-php-json-converter'); ?></h4>
                <button type="button" id="cancel-batch" class="button button-secondary button-small">
                    <?php _e('Cancel', 'acf-php-json-converter'); ?>
                </button>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: 0%;"></div>
            </div>
            <div class="progress-info">
                <div class="progress-text">
                    <?php _e('Preparing conversion...', 'acf-php-json-converter'); ?>
                </div>
                <div class="progress-stats">
                    <span id="progress-current">0</span> / <span id="progress-total">0</span>
                </div>
            </div>
            <div id="progress-details" class="progress-details">
                <ul id="progress-log"></ul>
            </div>
        </div>
        
        <!-- Batch Results -->
        <div id="batch-results" class="batch-results" style="display: none;">
            <div class="results-header">
                <h4><?php _e('Conversion Results', 'acf-php-json-converter'); ?></h4>
                <button type="button" id="close-results" class="button button-secondary button-small">
                    <?php _e('Close', 'acf-php-json-converter'); ?>
                </button>
            </div>
            <div class="results-summary">
                <div class="summary-stats">
                    <div class="stat-item success">
                        <span class="stat-number" id="success-count">0</span>
                        <span class="stat-label"><?php _e('Successful', 'acf-php-json-converter'); ?></span>
                    </div>
                    <div class="stat-item error">
                        <span class="stat-number" id="error-count">0</span>
                        <span class="stat-label"><?php _e('Failed', 'acf-php-json-converter'); ?></span>
                    </div>
                    <div class="stat-item total">
                        <span class="stat-number" id="total-processed">0</span>
                        <span class="stat-label"><?php _e('Total', 'acf-php-json-converter'); ?></span>
                    </div>
                </div>
            </div>
            <div id="results-details" class="results-details">
                <ul id="results-log"></ul>
            </div>
        </div>
    </div>
    
    <!-- JSON to PHP Section -->
    <div id="json-to-php-section" class="conversion-section" style="display: none;">
        <div class="section-header">
            <h3><?php _e('JSON Input', 'acf-php-json-converter'); ?></h3>
            <div class="section-actions">
                <button type="button" id="clear-json-input" class="button button-secondary">
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e('Clear', 'acf-php-json-converter'); ?>
                </button>
            </div>
        </div>
        
        <!-- JSON Input Options -->
        <div class="input-options">
            <div class="input-tabs">
                <button type="button" class="input-tab active" data-tab="textarea">
                    <span class="dashicons dashicons-edit"></span>
                    <?php _e('Paste JSON', 'acf-php-json-converter'); ?>
                </button>
                <button type="button" class="input-tab" data-tab="file">
                    <span class="dashicons dashicons-upload"></span>
                    <?php _e('Upload File', 'acf-php-json-converter'); ?>
                </button>
            </div>
            
            <div id="textarea-input" class="input-content active">
                <textarea id="json-input" rows="12" placeholder="<?php esc_attr_e('Paste your ACF JSON here...', 'acf-php-json-converter'); ?>"></textarea>
                <div class="input-help">
                    <p><?php _e('Paste the JSON content of an ACF field group. You can copy this from the ACF admin or from a .json file.', 'acf-php-json-converter'); ?></p>
                </div>
            </div>
            
            <div id="file-input" class="input-content">
                <div class="file-upload-area" id="json-file-drop">
                    <div class="upload-icon">
                        <span class="dashicons dashicons-cloud-upload"></span>
                    </div>
                    <div class="upload-text">
                        <p><strong><?php _e('Drop JSON file here or click to browse', 'acf-php-json-converter'); ?></strong></p>
                        <p><?php _e('Supports .json files up to 2MB', 'acf-php-json-converter'); ?></p>
                    </div>
                    <input type="file" id="json-file-input" accept=".json" style="display: none;">
                </div>
                <div id="file-info" class="file-info" style="display: none;">
                    <div class="file-details">
                        <span class="dashicons dashicons-media-document"></span>
                        <span id="file-name"></span>
                        <span id="file-size"></span>
                    </div>
                    <button type="button" id="remove-file" class="button button-secondary button-small">
                        <?php _e('Remove', 'acf-php-json-converter'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- JSON Validation -->
        <div id="json-validation" class="json-validation" style="display: none;">
            <div class="validation-status">
                <span class="validation-icon"></span>
                <span class="validation-message"></span>
            </div>
        </div>
        
        <!-- Convert Button -->
        <div class="convert-actions">
            <button type="button" id="convert-json-btn" class="button button-primary" disabled>
                <span class="dashicons dashicons-editor-code"></span>
                <?php _e('Convert to PHP', 'acf-php-json-converter'); ?>
            </button>
        </div>
        
        <!-- PHP Output -->
        <div id="php-output" class="php-output" style="display: none;">
            <div class="output-header">
                <h4><?php _e('Generated PHP Code', 'acf-php-json-converter'); ?></h4>
                <div class="output-actions">
                    <button type="button" id="copy-php-code" class="button button-secondary">
                        <span class="dashicons dashicons-clipboard"></span>
                        <?php _e('Copy Code', 'acf-php-json-converter'); ?>
                    </button>
                    <button type="button" id="download-php-code" class="button button-secondary">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Download', 'acf-php-json-converter'); ?>
                    </button>
                </div>
            </div>
            <div class="code-container">
                <pre id="php-code-output" class="php-code"></pre>
            </div>
        </div>
    </div>
</div>