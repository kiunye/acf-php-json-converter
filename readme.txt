=== Field Group PHP-JSON Converter ===
Contributors: kiunye
Tags: acf, advanced custom fields, local json, converter, developer tools
Requires at least: 6.5
Tested up to: 6.6
Requires PHP: 8.0
Stable tag: 2.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://github.com/kiunye/field-group-php-json-converter

Convert ACF field groups between PHP and JSON, in both directions, with lossless handling of nested repeaters, flexible content, and clone fields.

== Description ==

Field Group PHP-JSON Converter helps developers move ACF field groups between PHP registration code and the JSON format used by ACF's Local JSON feature.

= What it does =

* Converts pasted PHP field-group code to Local JSON, and JSON back to PHP, with no loss of structure or nesting.
* Handles repeaters, flexible content layouts, clone fields, and arbitrary sub-field nesting.
* A live converter in the admin (Tools &rarr; Field Group Converter) with copy-to-clipboard.
* A REST API (`/field-group-php-json-converter/v1/php-to-json` and `/json-to-php`) for headless and automation use.
* Bulk export of every ACF field group to a single JSON file, and import of a JSON file to generate PHP registration code.
* The parsing layer is built on PHP's tokenizer with no bundled third-party libraries, so nothing extra is shipped with the plugin.

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

1. The converter tool: paste PHP or JSON, pick a direction, and convert live with copy-to-clipboard.
2. Bulk export and import of ACF field groups.

== Changelog ==

= 2.0.0 =

* Clean rewrite with a tokenizer-based PHP parser and a REST-based backend.
* Live converter in the admin with copy-to-clipboard.
* REST API endpoints for headless conversion.
* Bulk export and import of ACF field groups.

= 1.0.1 =

* Enhanced error handling and user feedback.

= 1.0.0 =

* Initial release.

== Upgrade Notice ==

= 1.2.0 =

* v2 rewrite. Backup your theme and ACF JSON before updating.
