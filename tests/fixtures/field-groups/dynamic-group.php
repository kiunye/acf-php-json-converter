<?php
/**
 * Fixture: field group built dynamically from a variable.
 *
 * @package Field_Group_PHP_JSON_Converter
 */

$fields = array(
	array(
		'key'   => 'field_dynamic_text',
		'label' => 'Dynamic',
		'name'  => 'dynamic',
		'type'  => 'text',
	),
);

acf_add_local_field_group(
	array(
		'key'    => 'group_dynamic',
		'title'  => 'Dynamic Group',
		'fields' => $fields,
	)
);
