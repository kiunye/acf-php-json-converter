<?php
/**
 * Fixture: repeater field containing a nested repeater.
 *
 * @package Field_Group_PHP_JSON_Converter
 */

acf_add_local_field_group(
	array(
		'key'    => 'group_nested_repeater',
		'title'  => 'Nested Repeater',
		'fields' => array(
			array(
				'key'          => 'field_outer_repeater',
				'label'        => 'Outer Repeater',
				'name'         => 'outer_repeater',
				'type'         => 'repeater',
				'sub_fields'   => array(
					array(
						'key'        => 'field_inner_repeater',
						'label'      => 'Inner Repeater',
						'name'       => 'inner_repeater',
						'type'       => 'repeater',
						'sub_fields' => array(
							array(
								'key'   => 'field_inner_text',
								'label' => 'Inner Text',
								'name'  => 'inner_text',
								'type'  => 'text',
							),
						),
					),
					array(
						'key'   => 'field_outer_text',
						'label' => 'Outer Text',
						'name'  => 'outer_text',
						'type'  => 'text',
					),
				),
			),
		),
	)
);
