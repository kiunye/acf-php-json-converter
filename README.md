# ACF PHP-to-JSON Converter Plugin

A comprehensive WordPress plugin that automatically scans theme files for ACF field groups defined in PHP using `acf_add_local_field_group()` and converts them to JSON format for easy import/export and synchronization.

## Features

### Core Functionality

- **Automatic Theme Scanning**: Recursively scans all PHP files in active theme directory (parent and child themes)
- **Bidirectional Conversion**: Convert PHP field groups to JSON and JSON back to PHP
- **Batch Processing**: Process multiple field groups simultaneously with progress tracking
- **Preview Mode**: Preview converted JSON before saving to verify accuracy
- **Local JSON Integration**: Automatically creates and manages ACF Local JSON directories

### Advanced Features

- **Comprehensive Error Handling**: Detailed error messages with recovery suggestions
- **Progress Tracking**: Real-time progress indicators for long-running operations
- **Backup System**: Automatic backups before any file modifications
- **Export Options**: Download individual files or ZIP archives of multiple field groups
- **Security**: Input sanitization, capability checks, and secure file operations
- **Logging**: Comprehensive logging with configurable levels and cleanup

## Installation

1. Upload the plugin files to `/wp-content/plugins/acf-php-json-converter/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Tools > ACF PHP-JSON Converter

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Advanced Custom Fields (ACF) plugin installed and activated

## Usage

### Scanning Theme Files

1. Go to the **Scanner** tab
2. Click "Scan Theme Files" to discover all ACF field groups in your theme
3. Review the results table showing field group details
4. Use individual actions (Preview, Convert, Download) for each field group

### Converting PHP to JSON

1. From the Scanner results, click "Convert to JSON" for any field group
2. Or use the **Converter** tab for batch processing
3. Select multiple field groups using checkboxes
4. Click "Convert Selected" to process all at once

### Converting JSON to PHP

1. Go to the **Converter** tab
2. Select "JSON to PHP" conversion direction
3. Upload a JSON file or paste JSON content
4. Click "Convert" to generate PHP code
5. Copy the generated code to your theme files

### Settings Configuration

Access the **Settings** tab to configure:

- **Auto-create Local JSON folder**: Automatically create acf-json directories
- **Default export location**: Choose where to save converted files
- **Logging preferences**: Set log levels and retention policies
- **Error handling**: Configure error display and recovery options

## Plugin Architecture

### Core Services

#### Scanner Service

- Discovers ACF field groups in theme files
- Caches results for improved performance
- Handles file system traversal and PHP parsing

#### Converter Service

- Bidirectional conversion between PHP and JSON
- Validates field group structure and ACF compatibility
- Preserves all field properties and relationships

#### File Manager Service

- Creates and manages ACF Local JSON directories
- Handles file operations with proper permissions
- Manages backups and exports

### Utility Classes

#### Error Handler

- Comprehensive error handling with user-friendly messages
- Recovery suggestions and troubleshooting guidance
- Batch operation support with progress tracking

#### Logger

- Configurable logging levels (error, warning, info, debug)
- Log rotation and cleanup
- Error statistics and reporting

#### Security

- Input sanitization and validation
- User capability checks
- Secure file path validation

## Development

### Running Tests

The plugin includes comprehensive unit and integration tests:

```bash
# Run all tests
composer test

# Run specific test suites
vendor/bin/phpunit --filter="Validator"
vendor/bin/phpunit --filter="Logger"
vendor/bin/phpunit --filter="Security"
```

### Code Structure

```
acf-php-json-converter/
├── includes/
│   ├── admin/                  # Admin interface
│   ├── services/              # Core business logic
│   ├── utilities/             # Helper classes
│   ├── converters/            # Conversion logic
│   └── parsers/               # PHP parsing
├── assets/                    # CSS, JS, images
├── templates/                 # Admin page templates
└── tests/                     # Unit and integration tests
```

### Extending the Plugin

The plugin is designed with extensibility in mind:

#### Adding Custom Field Types

Extend the converter classes to support custom field types:

```php
// In your theme's functions.php
add_filter('acf_php_json_converter_field_types', function($field_types) {
    $field_types['custom_field'] = 'Custom Field Handler';
    return $field_types;
});
```

#### Custom Error Handlers

Register custom error handlers for specific scenarios:

```php
add_filter('acf_php_json_converter_error_handlers', function($handlers) {
    $handlers['custom_error'] = 'Custom_Error_Handler';
    return $handlers;
});
```

## Troubleshooting

### Common Issues

#### "ACF plugin not found"

- Ensure Advanced Custom Fields plugin is installed and activated
- Check that ACF version is compatible (5.0+ recommended)

#### "Permission denied" errors

- Verify file permissions (644 for files, 755 for directories)
- Check that WordPress has write access to theme directory
- Ensure user has appropriate WordPress capabilities

#### "Conversion failed" errors

- Check for unsupported field types
- Verify field group structure is valid
- Try converting individual field groups to isolate issues

#### Memory or timeout errors

- Reduce batch size in settings
- Ask hosting provider to increase PHP memory limit
- Process fewer items at once

### Debug Mode

Enable debug mode by adding to wp-config.php:

```php
define('ACF_PHP_JSON_CONVERTER_DEBUG', true);
```

This will:
- Enable detailed logging
- Show additional error information
- Preserve temporary files for inspection

## Debugging Guide

### Common Issues and Solutions

#### 1. "Failed to convert field group to JSON"
**Symptoms**: Conversion fails with generic error message

**Debugging Steps**:
```php
// Check the error log in Settings > Error Log
// Look for specific conversion errors like:
// - "Missing required field: key/title/fields"
// - "Input data must be an array"
// - "Fields must be an array"
```

**Solutions**:
- Ensure field groups have required properties (key, title, fields)
- Verify field group structure matches ACF format
- Check for corrupted or incomplete field group data

#### 2. JSON Parsing Errors
**Symptoms**: "JSON decode failed: Syntax error" in logs

**Debugging Steps**:
```php
// Enable debug logging to see conversion details
// Check for complex nested arrays or special characters
// Look for PHP syntax issues in original field group code
```

**Solutions**:
- The plugin now uses fallback parsing for complex structures
- Check original PHP code for syntax errors
- Verify field group arrays are properly formatted

#### 3. Fatal Errors in AJAX Handlers
**Symptoms**: White screen or 500 errors during operations

**Debugging Steps**:
```php
// Check PHP error logs for fatal errors
// Common issues:
// - "Call to undefined method" errors
// - Memory limit exceeded
// - Class not found errors
```

**Solutions**:
- Ensure all plugin files are uploaded correctly
- Check PHP memory limit (recommended: 256MB+)
- Verify WordPress and ACF versions are compatible

#### 4. Scanner Not Finding Field Groups
**Symptoms**: Theme scan returns empty results

**Debugging Steps**:
```php
// Check if field groups use acf_add_local_field_group()
// Verify theme files are readable
// Look for file permission issues
```

**Solutions**:
- Ensure field groups are defined using `acf_add_local_field_group()`
- Check file permissions (644 for files, 755 for directories)
- Verify theme structure is standard WordPress format

### Advanced Debugging

#### Enable Detailed Logging
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('ACF_PHP_JSON_CONVERTER_DEBUG', true);

// Set plugin log level to debug
// Go to Settings > Logging Level > Debug
```

#### Test Individual Components
```php
// Test PHP array parsing
$parser = new ACF_PHP_JSON_Converter\Parsers\PHP_Parser($logger, $security);
$result = $parser->safely_evaluate_array($your_array_string);

// Test conversion
$converter = new ACF_PHP_JSON_Converter\Services\Converter_Service($logger, $security);
$result = $converter->convert_php_to_json($field_group_data);
```

#### Check Plugin Dependencies
```php
// Verify ACF is active
if (!class_exists('ACF')) {
    // ACF plugin not found
}

// Check required PHP extensions
if (!extension_loaded('json')) {
    // JSON extension required
}

// Verify file system permissions
if (!is_writable(get_stylesheet_directory())) {
    // Theme directory not writable
}
```

#### Memory and Performance Issues
```php
// Check current memory usage
echo 'Memory usage: ' . memory_get_usage(true) / 1024 / 1024 . ' MB';
echo 'Memory limit: ' . ini_get('memory_limit');

// For large field groups, process in smaller batches
// Recommended batch size: 10-20 field groups at once
```

#### Database and Caching Issues
```php
// Clear plugin caches
delete_transient('acf_php_json_converter_scan_results');

// Check for database errors
global $wpdb;
if ($wpdb->last_error) {
    echo 'Database error: ' . $wpdb->last_error;
}
```

### Error Log Analysis

The plugin provides detailed error logging. Common log entries and their meanings:

- **"JSON decode failed"**: Complex PHP array couldn't be converted to JSON (fallback parsing will be used)
- **"Field key reformatted"**: Warning that field keys were updated to match ACF format (normal behavior)
- **"Conversion failed"**: Critical error in conversion process (check field group structure)
- **"Permission denied"**: User lacks required capabilities or file permissions
- **"Theme scan failed"**: Issues accessing theme files (check permissions and theme structure)

### Performance Monitoring

Monitor plugin performance with these metrics:
- **Scan time**: Should complete within 30 seconds for most themes
- **Conversion time**: Individual conversions should complete within 5 seconds
- **Memory usage**: Should not exceed 80% of available PHP memory
- **Error rate**: Should be less than 5% for well-formed field groups

### Getting Support

1. Check the error log in Settings > Error Log
2. Review the troubleshooting section above
3. Enable debug mode and reproduce the issue
4. Search existing issues on GitHub
5. Create a new issue with:
   - WordPress version
   - ACF version
   - Plugin version
   - Error messages from debug log
   - Steps to reproduce
   - Sample field group code (if applicable)

## Changelog

### Version 1.0.1

- Enhanced error handling and user feedback
- Improved batch processing with progress tracking
- Added comprehensive logging system
- Fixed compatibility issues with various ACF versions
- Improved security and input validation

### Version 1.0.0

- Initial release
- Core PHP to JSON conversion
- Basic theme scanning
- Admin interface
- File management system

## License

This plugin is licensed under the GPL-2.0+ license. See LICENSE file for details.

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Add tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

## Credits

Developed by Chris Araya for the WordPress community.
