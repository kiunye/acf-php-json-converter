<?php
/**
 * Fixture: intentionally broken PHP (unbalanced brackets / syntax error).
 *
 * This file is NOT valid PHP and is used to confirm the parser reports a
 * clear syntax error instead of failing silently. It must never be loaded
 * by PHPUnit via require/include.
 *
 * @package Field_Group_PHP_JSON_Converter
 */

acf_add_local_field_group(
	array(
		'key'   => 'group_broken',
		'title' => 'Broken',
		'fields' => array(
			array(
				'key' => 'field_broken',
			// missing closing brackets below
