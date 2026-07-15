<?php
/**
 * Fixture: clone field referencing another group.
 *
 * @package Field_Group_PHP_JSON_Converter
 */

acf_add_local_field_group(
	array(
		'key'    => 'group_clone',
		'title'  => 'Clone Group',
		'fields' => array(
			array(
				'key'        => 'field_clone',
				'label'      => 'Cloned',
				'name'       => 'cloned',
				'type'       => 'clone',
				'clone'      => array(
					'group_simple',
					'field_text',
				),
				'prefix_name' => true,
				'display'     => 'group',
			),
		),
	)
);
