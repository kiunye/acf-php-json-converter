<?php
/**
 * Fixture: a single, flat field group.
 *
 * @package Field_Group_PHP_JSON_Converter
 */

acf_add_local_field_group(
	array(
		'key'                   => 'group_simple',
		'title'                 => 'Simple Group',
		'fields'                => array(
			array(
				'key'   => 'field_text',
				'label' => 'Text',
				'name'  => 'text',
				'type'  => 'text',
			),
		),
		'location'              => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'post',
				),
			),
		),
		'menu_order'            => 0,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen'        => '',
		'active'                => true,
		'description'           => '',
	)
);
