<div class="wrap acf-php-json-converter-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="acf-php-json-converter-tabs nav-tab-wrapper">
        <a href="#scanner-tab" class="nav-tab nav-tab-active"><?php _e('Scanner', 'acf-php-json-converter'); ?></a>
        <a href="#converter-tab" class="nav-tab"><?php _e('Converter', 'acf-php-json-converter'); ?></a>
        <a href="#settings-tab" class="nav-tab"><?php _e('Settings', 'acf-php-json-converter'); ?></a>
    </div>
    
    <div id="scanner-tab" class="acf-php-json-converter-tab-content active">
        <?php include(ACF_PHP_JSON_CONVERTER_DIR . 'templates/scanner-tab.php'); ?>
    </div>
    
    <div id="converter-tab" class="acf-php-json-converter-tab-content">
        <?php include(ACF_PHP_JSON_CONVERTER_DIR . 'templates/converter-tab.php'); ?>
    </div>
    
    <div id="settings-tab" class="acf-php-json-converter-tab-content">
        <?php include(ACF_PHP_JSON_CONVERTER_DIR . 'templates/settings-tab.php'); ?>
    </div>
</div>