=== Field Group PHP-JSON Converter ===
Contributors: kiunye
Tags: acf, advanced custom fields, local json, converter, developer tools
Requires at least: 6.5
Tested up to: 6.6
Requires PHP: 8.0
Stable tag: 1.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://github.com/kiunye/field-group-php-json-converter

Scans your active theme for Advanced Custom Fields groups registered in PHP and converts them to ACF Local JSON (and back again), including nested repeaters, flexible content, and clone fields.

== Description ==

Field Group PHP-JSON Converter helps developers move ACF field groups between PHP (registered in a theme) and the JSON format used by ACF's Local JSON feature.

= What it does =

* Recursively scans the parent and child theme for `acf_add_local_field_group()` (and equivalent) calls.
* Converts PHP field groups to Local JSON, and JSON back to PHP, with no loss of structure or nesting.
* Handles repeaters, flexible content layouts, clone fields, and arbitrary sub-field nesting.
* Batch conversion with progress tracking, and a preview before anything is written.
* Automatic backups before any file on disk is modified.
* Export as an individual file download or a ZIP archive.

= Scope =

This is a developer utility. It follows native wp-admin design and supports only ACF's Local JSON format. It requires Advanced Custom Fields (free or Pro) to be active.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/field-group-php-json-converter/`.
2. Activate the plugin through the 'Plugins' menu in WordPress (ACF must be active).
3. Go to Tools > ACF PHP-JSON Converter.

== Frequently Asked Questions ==

= Does this modify my theme files? =

Only when you choose to save a conversion. A backup is written first, and you can preview before saving.

= Which ACF field types are supported? =

All standard ACF field types, including repeaters, flexible content, groups, and clone fields, at any nesting depth.

== Screenshots ==

1. Scan results listing field groups found in the active theme.
2. Converting a field group to Local JSON.

== Changelog ==

= 1.2.0 =

* v2 rewrite in progress: tokenizer-based PHP parsing and REST-based backend.

= 1.0.1 =

* Enhanced error handling and user feedback.

= 1.0.0 =

* Initial release.

== Upgrade Notice ==

= 1.2.0 =

* v2 rewrite. Backup your theme and ACF JSON before updating.
