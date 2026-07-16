# Field Group PHP-JSON Converter

A developer utility for WordPress that converts ACF (Advanced Custom Fields) field groups between PHP registration code and the JSON format used by ACF's Local JSON feature — in both directions, with lossless handling of nested repeaters, flexible content, and clone fields.

## Features

### Convert (paste & live convert)

- Paste PHP `acf_add_local_field_group()` code or ACF JSON and convert live in the admin, with copy-to-clipboard.
- PHP &rarr; JSON and JSON &rarr; PHP, preserving all field properties and arbitrary nesting.
- Bulk export every ACF field group (database) to a single JSON file, and import a JSON file to generate PHP registration code.

### Scan theme

- Recursively scans the active theme (parent and child) for field groups registered in PHP via `acf_add_local_field_group()`, and for groups stored as ACF Local JSON.
- Convert a PHP-registered group into Local JSON written straight into the theme's `acf-json` folder (a backup is written before any file is overwritten).
- Convert Local JSON field groups into PHP registration code ready to paste into `functions.php`.
- `array_merge()` / `array_merge_recursive()` of literal arrays are resolved statically, so groups built from shared base arrays are detected. Groups built dynamically (variables, function calls) are reported under "Skipped groups" instead of failing the scan.

### Field issues

A single "Check for issues" run that validates, and reports with severity:

- **ACF / plugin status** — ACF not active or the `acf-field-group` post type missing.
- **The pasted snippet** — malformed/duplicate groups and unsupported field types.
- **The active theme** — the same checks across discovered PHP and Local JSON groups.
- **The database** — groups currently registered in ACF.

Checks flag groups missing a `key` or `title`, duplicate group keys, fields missing `key`/`name`/`type`, duplicate field keys, and unknown field types.

### Technical

- Tokenizer-based PHP parsing — no bundled third-party libraries are shipped.
- REST API under `/field-group-php-json-converter/v1/`: `scan-theme`, `theme-to-json`, `theme-to-php`, `php-to-json`, `json-to-php`.
- Backup-before-write safety on every file modification.
- Native wp-admin design; follows WordPress coding standards and ships a `.pot` for translation.

## Installation

1. Upload the plugin files to `/wp-content/plugins/field-group-php-json-converter/`.
2. Activate the plugin through the 'Plugins' menu in WordPress (ACF must be active).
3. Go to **Tools &rarr; Field Group Converter**.

## Requirements

- WordPress 6.5 or higher
- PHP 8.0 or higher
- Advanced Custom Fields (free or Pro) installed and activated (required for database and Local JSON features)

## Usage

### Convert tab

1. Paste PHP or JSON into the source box (or pick a direction and paste).
2. The conversion runs live; click **Copy output** to copy the result.
3. Use **Export all field groups** to download every ACF group as JSON, or **Import JSON file** to generate PHP registration code.

### Scan theme tab

1. Click **Scan theme** to discover PHP-registered and Local JSON field groups.
2. Choose a direction and click **Convert discovered groups** to write Local JSON into the theme, or to generate `functions.php` code.

### Field issues tab

1. Click **Check for issues** to run every check at once.
2. Review the severity-coded list (errors, warnings, and environment status).

## Architecture

```
field-group-php-json-converter/
├── includes/
│   ├── admin/        # Admin UI, tabs, AJAX/POST handlers
│   ├── parsers/      # Tokenizer-based PHP field-group parser
│   ├── converters/   # PHP <-> JSON conversion
│   ├── theme/        # Theme scanner + theme conversion service
│   ├── services/     # Conversion orchestration, results
│   ├── rest/         # REST API controller
│   └── utilities/    # Security, validation, logger
├── assets/           # CSS and JS (ES modules, built with esbuild)
├── languages/        # Translation template (.pot)
└── tests/            # Unit and integration tests
```

### Key classes

- `Field_Group_Parser` — extracts field groups from PHP source via `token_get_all()`.
- `Theme_Scanner` / `Theme_Service` — discover and convert theme field groups.
- `Field_Group_Validator` / `Validation_Result` — structural issue detection.
- `Field_Group_Conversion_Service` — orchestrates parse → convert → write with backups.
- `Rest_Controller` — exposes conversions and theme scanning over REST.

## Development

### Running tests

```bash
composer test                      # run the full suite
vendor/bin/phpunit --filter=Validator
```

### Code style

```bash
composer lint                      # WordPress Coding Standards (phpcs)
```

### Building assets

```bash
npm install
npm run build                      # bundle assets/js/src into assets/js/admin.js
```

## Troubleshooting

### "ACF plugin not found"

Ensure Advanced Custom Fields is installed and activated. Database and Local JSON features require it; the paste converter works without ACF but cannot read the database.

### A group is listed under "Skipped groups"

The group is registered dynamically (a variable or function call), so it cannot be parsed statically. Convert it manually, or refactor it to a literal array or an `array_merge()` of literal arrays.

### "Could not determine the theme Local JSON directory"

The theme directory is not writable by WordPress. Check file permissions (644 for files, 755 for directories) and that the web server can write to the theme.

### Conversion produced unexpected output

Ensure the source is a single group or a list of groups using `acf_add_local_field_group()` / `acf_add_local_field_groups()`, and that arrays are literal (not built at runtime).

## License

GPL-2.0-or-later. See `LICENSE` for details.

## Contributing

1. Fork the repository.
2. Create a feature branch.
3. Add tests for new functionality.
4. Run `composer lint` and `composer test`.
5. Submit a pull request.

## Credits

Developed by Chris Araya for the WordPress community.
