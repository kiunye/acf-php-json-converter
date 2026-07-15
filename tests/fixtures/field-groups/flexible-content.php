<?php
/**
 * Fixture: flexible content field with layouts and nested sub fields.
 *
 * @package Field_Group_PHP_JSON_Converter
 */

acf_add_local_field_group(
	array(
		'key'    => 'group_flexible',
		'title'  => 'Flexible Content',
		'fields' => array(
			array(
				'key'      => 'field_layout',
				'label'    => 'Layout',
				'name'     => 'layout',
				'type'     => 'flexible_content',
				'layouts'  => array(
					array(
						'key'        => 'layout_text_block',
						'name'       => 'text_block',
						'label'      => 'Text Block',
						'sub_fields' => array(
							array(
								'key'   => 'field_heading',
								'label' => 'Heading',
								'name'  => 'heading',
								'type'  => 'text',
							),
							array(
								'key'   => 'field_body',
								'label' => 'Body',
								'name'  => 'body',
								'type'  => 'wysiwyg',
							),
						),
					),
					array(
						'key'        => 'layout_image_block',
						'name'       => 'image_block',
						'label'      => 'Image Block',
						'sub_fields' => array(
							array(
								'key'   => 'field_image',
								'label' => 'Image',
								'name'  => 'image',
								'type'  => 'image',
							),
						),
					),
				),
			),
		),
	)
);
