<div class="converter-tab-content">
    <h2><?php _e('Field Group Converter', 'acf-php-json-converter'); ?></h2>
    <p><?php _e('Convert between PHP and JSON formats for ACF field groups.', 'acf-php-json-converter'); ?></p>
    
    <div class="conversion-direction">
        <h3><?php _e('Conversion Direction', 'acf-php-json-converter'); ?></h3>
        <label>
            <input type="radio" name="conversion_direction" value="php_to_json" checked>
            <?php _e('PHP to JSON', 'acf-php-json-converter'); ?>
        </label>
        <label>
            <input type="radio" name="conversion_direction" value="json_to_php">
            <?php _e('JSON to PHP', 'acf-php-json-converter'); ?>
        </label>
    </div>
    
    <div id="php-to-json-section" class="conversion-section">
        <h3><?php _e('Select Field Groups to Convert', 'acf-php-json-converter'); ?></h3>
        <div id="field-groups-list">
            <p><?php _e('Please scan theme files first to see available field groups.', 'acf-php-json-converter'); ?></p>
        </div>
        <button type="button" id="batch-convert-btn" class="button button-primary" disabled>
            <?php _e('Convert Selected', 'acf-php-json-converter'); ?>
        </button>
    </div>
    
    <div id="json-to-php-section" class="conversion-section" style="display: none;">
        <h3><?php _e('JSON Input', 'acf-php-json-converter'); ?></h3>
        <textarea id="json-input" rows="10" cols="80" placeholder="<?php esc_attr_e('Paste your ACF JSON here...', 'acf-php-json-converter'); ?>"></textarea>
        <br>
        <button type="button" id="convert-json-btn" class="button button-primary">
            <?php _e('Convert to PHP', 'acf-php-json-converter'); ?>
        </button>
        
        <div id="php-output" style="display: none;">
            <h3><?php _e('Generated PHP Code', 'acf-php-json-converter'); ?></h3>
            <pre id="php-code-output"></pre>
        </div>
    </div>
</div>