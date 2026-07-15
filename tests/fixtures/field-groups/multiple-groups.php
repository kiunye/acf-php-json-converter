<?php
/**
 * Fixture: multiple field groups in one file.
 *
 * @package Field_Group_PHP_JSON_Converter
 */

acf_add_local_field_group(
	array(
		'key'    => 'group_one',
		'title'  => 'Group One',
		'fields' => array(
			array(
				'key'   => 'field_one_text',
				'label' => 'One',
				'name'  => 'one',
				'type'  => 'text',
			),
		),
	)
);

acf_add_local_field_group(
	array(
		'key'    => 'group_two',
		'title'  => 'Group Two',
		'fields' => array(
			array(
				'key'   => 'field_two_text',
				'label' => 'Two',
				'name'  => 'two',
				'type'  => 'textarea',
			),
		),
	)
);
