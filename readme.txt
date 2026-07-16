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

* Scans the active theme (parent and child) for ACF field groups registered in PHP via `acf_add_local_field_group()`, and for field groups stored as ACF Local JSON.
* Converts a PHP-registered group into Local JSON and writes it straight into the theme's `acf-json` folder (backing up any file it overwrites).
* Converts Local JSON field groups into PHP registration code ready to paste into the theme's `functions.php`.
* Converts pasted PHP field-group code to Local JSON, and JSON back to PHP, with no loss of structure or nesting.
* Resolves `array_merge()` (and `array_merge_recursive()`) of literal arrays statically, so groups built from shared base arrays are detected during a theme scan. Groups that are built dynamically (variables, function calls) are reported as skipped rather than failing the whole scan.
* Handles repeaters, flexible content layouts, clone fields, and arbitrary sub-field nesting.
* A three-tab admin tool (Tools &rarr; Field Group Converter):
  * **Convert** &mdash; paste PHP or JSON and convert live with copy-to-clipboard, plus bulk export/import of all ACF field groups.
  * **Scan theme** &mdash; discover PHP and Local JSON groups in the active theme and convert between the two formats.
  * **Field issues** &mdash; check the ACF environment, the pasted snippet, the active theme, and the database for malformed or duplicate field groups and unsupported field types.
* A REST API (`/field-group-php-json-converter/v1/scan-theme`, `/theme-to-json`, `/theme-to-php`, `/php-to-json` and `/json-to-php`) for headless and automation use.
* The parsing layer is built on PHP's tokenizer with no bundled third-party libraries, so nothing extra is shipped with the plugin.

= Scope =

This is a developer utility. It follows native wp-admin design and supports only ACF's Local JSON format. It requires Advanced Custom Fields (free or Pro) to be active for database and Local JSON features.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/field-group-php-json-converter/`.
2. Activate the plugin through the 'Plugins' menu in WordPress (ACF must be active).
3. Go to Tools > Field Group Converter.

== Frequently Asked Questions ==

= Does this modify my theme files? =

Only when you choose to save a conversion. A backup is written first, and you can preview before saving.

= Which ACF field types are supported? =

All standard ACF field types, including repeaters, flexible content, groups, and clone fields, at any nesting depth.

= My group is registered from a variable, not a literal array. Will it be converted? =

Groups passed to `acf_add_local_field_group()` as a variable, or built inside a function call, cannot be parsed statically. They are listed under "Skipped groups" in the scan results rather than breaking the scan. Groups assembled with `array_merge()` of literal arrays are supported.

== Screenshots ==

1. The Convert tab: paste PHP or JSON, pick a direction, and convert live with copy-to-clipboard.
2. The Scan theme tab: discover PHP and Local JSON field groups and convert between them.
3. The Field issues tab: surface malformed or duplicate groups and unsupported field types.
4. Bulk export and import of ACF field groups.

== Changelog ==

= 2.0.0 =

* Clean rewrite with a tokenizer-based PHP parser and a REST-based backend.
* Three-tab admin: Convert, Scan theme, and Field issues.
* Live converter in the admin with copy-to-clipboard.
* Theme scanning for PHP-registered and Local JSON field groups, with conversion in both directions.
* Field issues checker for the ACF environment, pasted snippet, theme, and database.
* Static resolution of `array_merge()` / `array_merge_recursive()` of literal arrays.
* REST API endpoints for headless conversion.
* Bulk export and import of ACF field groups.

= 1.0.1 =

* Enhanced error handling and user feedback.

= 1.0.0 =

* Initial release.

== Upgrade Notice ==

= 2.0.0 =

* v2 rewrite. Backup your theme and ACF JSON before updating.
