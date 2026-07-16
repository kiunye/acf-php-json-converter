<?php
/**
 * Validation of ACF field group structure and environment.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Utilities
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Utilities;

use Field_Group_PHP_JSON_Converter\Parsers\Field_Group_Parser;

/**
 * Detects malformed field groups and plugin/environment problems so the admin
 * "Issues" tab can surface them in one place.
 *
 * @since 2.0.0
 */
class Field_Group_Validator {

	/**
	 * Known ACF core field types.
	 *
	 * Used to flag unknown/unsupported field types as warnings.
	 *
	 * @since 2.0.0
	 * @var array<int,string>
	 */
	private const KNOWN_FIELD_TYPES = array(
		'text',
		'textarea',
		'number',
		'range',
		'email',
		'url',
		'password',
		'image',
		'file',
		'wysiwyg',
		'oembed',
		'gallery',
		'select',
		'checkbox',
		'radio',
		'button_group',
		'true_false',
		'link',
		'post_object',
		'page_link',
		'relationship',
		'taxonomy',
		'user',
		'google_map',
		'date_picker',
		'date_time_picker',
		'time_picker',
		'color_picker',
		'group',
		'repeater',
		'flexible_content',
		'clock_picker',
		'accordion',
		'tab',
		'message',
		'clone',
	);

	/**
	 * PHP source parser, used to extract groups from a pasted snippet.
	 *
	 * @since 2.0.0
	 * @var Field_Group_Parser
	 */
	private Field_Group_Parser $parser;

	/**
	 * Inject the parser.
	 *
	 * @since 2.0.0
	 * @param Field_Group_Parser|null $parser Parser instance.
	 */
	public function __construct( ?Field_Group_Parser $parser = null ) {
		$this->parser = $parser ?? new Field_Group_Parser();
	}

	/**
	 * Validate a pasted PHP or JSON snippet.
	 *
	 * @since 2.0.0
	 * @param string $source PHP or JSON source.
	 * @param string $format 'php' or 'json'.
	 * @return Validation_Result
	 */
	public function validate_source( string $source, string $format ): Validation_Result {
		$result = new Validation_Result();

		if ( '' === trim( $source ) ) {
			$result->add_issue( 'error', __( 'Nothing to validate: the source box is empty.', 'field-group-php-json-converter' ) );
			return $result;
		}

		if ( 'json' === $format ) {
			$decoded = json_decode( $source, true );
			if ( null === $decoded && 'null' !== trim( $source ) ) {
				$result->add_issue( 'error', __( 'The supplied JSON could not be decoded.', 'field-group-php-json-converter' ) );
				return $result;
			}
			$groups = is_array( $decoded ) ? $decoded : array();
			if ( ! empty( $groups ) && ! $this->is_list( $groups ) ) {
				// A single group object rather than a list of them.
				$groups = array( $groups );
			}
		} else {
			$parsed = $this->parser->parse_source( $source );
			$groups = $parsed->get_field_groups();
			foreach ( $parsed->get_errors() as $error ) {
				$result->add_issue(
					'warning',
					sprintf(
						/* translators: %s: parser error message. */
						__( 'Could not statically parse part of the snippet: %s', 'field-group-php-json-converter' ),
						$error->get_message()
					)
				);
			}
		}

		if ( 0 === count( $groups ) ) {
			$result->add_issue( 'warning', __( 'No field groups were found to validate.', 'field-group-php-json-converter' ) );
			return $result;
		}

		$this->validate_groups( $groups, __( 'pasted snippet', 'field-group-php-json-converter' ), $result );

		return $result;
	}

	/**
	 * Validate field groups discovered in the active theme (PHP + Local JSON).
	 *
	 * @since 2.0.0
	 * @param array<int,array{group:array,source:string,file:string}> $items Scan result items.
	 * @return Validation_Result
	 */
	public function validate_scan_items( array $items ): Validation_Result {
		$result = new Validation_Result();
		$groups = array();
		foreach ( $items as $item ) {
			if ( isset( $item['group'] ) && is_array( $item['group'] ) ) {
				$groups[] = $item['group'];
			}
		}

		if ( 0 === count( $groups ) ) {
			$result->add_issue( 'warning', __( 'No theme field groups were found to validate.', 'field-group-php-json-converter' ) );
			return $result;
		}

		$this->validate_groups( $groups, __( 'active theme', 'field-group-php-json-converter' ), $result );

		return $result;
	}

	/**
	 * Validate field groups registered in the database via ACF.
	 *
	 * @since 2.0.0
	 * @return Validation_Result
	 */
	public function validate_db_groups(): Validation_Result {
		$result = new Validation_Result();

		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			$result->add_issue( 'error', __( 'ACF is not active, so database field groups cannot be checked.', 'field-group-php-json-converter' ) );
			return $result;
		}

		$groups = acf_get_field_groups();
		if ( ! is_array( $groups ) || 0 === count( $groups ) ) {
			$result->add_issue( 'warning', __( 'No field groups are currently registered in the database.', 'field-group-php-json-converter' ) );
			return $result;
		}

		$this->validate_groups( $groups, __( 'database', 'field-group-php-json-converter' ), $result );

		return $result;
	}

	/**
	 * Validate the plugin/environment status (ACF availability, post type).
	 *
	 * @since 2.0.0
	 * @return Validation_Result
	 */
	public function validate_environment(): Validation_Result {
		$result = new Validation_Result();

		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			$result->add_issue(
				'error',
				__( 'Advanced Custom Fields is not active. Database and Local JSON features are unavailable.', 'field-group-php-json-converter' )
			);
		} elseif ( ! post_type_exists( 'acf-field-group' ) ) {
			$result->add_issue(
				'error',
				__( 'The ACF field group post type is missing. ACF may be partially installed or disabled.', 'field-group-php-json-converter' )
			);
		} else {
			$result->add_issue( 'ok', __( 'Advanced Custom Fields is active and ready.', 'field-group-php-json-converter' ) );
		}

		return $result;
	}

	/**
	 * Run every available check and aggregate the results.
	 *
	 * @since 2.0.0
	 * @param array<int,array{group:array,source:string,file:string}> $scan_items Theme scan items (may be empty).
	 * @return Validation_Result
	 */
	public function validate_all( array $scan_items = array() ): Validation_Result {
		$result = new Validation_Result();

		$env  = $this->validate_environment();
		$scan = $this->validate_scan_items( $scan_items );
		$db   = $this->validate_db_groups();

		foreach ( $env->get_issues() as $issue ) {
			$result->add_issue( $issue['severity'], $issue['message'], $issue['context'] );
		}
		foreach ( $scan->get_issues() as $issue ) {
			$result->add_issue( $issue['severity'], $issue['message'], $issue['context'] );
		}
		foreach ( $db->get_issues() as $issue ) {
			$result->add_issue( $issue['severity'], $issue['message'], $issue['context'] );
		}

		if ( ! $result->has_issues() ) {
			$result->add_issue( 'ok', __( 'No issues were detected.', 'field-group-php-json-converter' ) );
		}

		return $result;
	}

	/**
	 * Validate a list of field group arrays for structural problems.
	 *
	 * @since 2.0.0
	 * @param array             $groups  Field group definitions.
	 * @param string            $context Human readable source label.
	 * @param Validation_Result $result Result to populate.
	 * @return void
	 */
	private function validate_groups( array $groups, string $context, Validation_Result $result ): void {
		$seen_keys = array();
		$position  = 0;

		foreach ( $groups as $group ) {
			++$position;
			if ( ! is_array( $group ) ) {
				$result->add_issue(
					'error',
					sprintf(
						/* translators: 1: position, 2: context. */
						__( 'Group #%1$d in the %2$s is not a valid array.', 'field-group-php-json-converter' ),
						$position,
						$context
					),
					$context
				);
				continue;
			}

			$label = isset( $group['title'] ) && '' !== $group['title']
				? (string) $group['title']
				: ( isset( $group['key'] ) ? (string) $group['key'] : sprintf( '#%d', $position ) );

			if ( ! isset( $group['key'] ) || '' === $group['key'] || ! is_string( $group['key'] ) ) {
				$result->add_issue(
					'error',
					sprintf(
						/* translators: %s: group label. */
						__( 'Group "%s" is missing a "key".', 'field-group-php-json-converter' ),
						$label
					),
					$context
				);
			} else {
				$key = (string) $group['key'];
				if ( isset( $seen_keys[ $key ] ) ) {
					$result->add_issue(
						'error',
						sprintf(
							/* translators: %s: duplicate group key. */
							__( 'Duplicate group key "%1$s" found in the %2$s.', 'field-group-php-json-converter' ),
							$key,
							$context
						),
						$context
					);
				}
				$seen_keys[ $key ] = true;
			}

			if ( ! isset( $group['title'] ) || '' === $group['title'] ) {
				$result->add_issue(
					'warning',
					sprintf(
						/* translators: %s: group label. */
						__( 'Group "%s" is missing a "title".', 'field-group-php-json-converter' ),
						$label
					),
					$context
				);
			}

			if ( isset( $group['fields'] ) && is_array( $group['fields'] ) ) {
				$this->validate_fields( $group['fields'], $label, $context, $result );
			}
		}
	}

	/**
	 * Validate the fields of a single group.
	 *
	 * @since 2.0.0
	 * @param array             $fields  Field definitions.
	 * @param string            $group_label Group label for messages.
	 * @param string            $context Human readable source label.
	 * @param Validation_Result $result Result to populate.
	 * @return void
	 */
	private function validate_fields( array $fields, string $group_label, string $context, Validation_Result $result ): void {
		$seen_keys = array();

		foreach ( $fields as $index => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			if ( ! isset( $field['key'] ) || '' === $field['key'] || ! is_string( $field['key'] ) ) {
				$result->add_issue(
					'error',
					sprintf(
						/* translators: 1: position, 2: group label. */
						__( 'Field #%1$d in group "%2$s" is missing a "key".', 'field-group-php-json-converter' ),
						$index + 1,
						$group_label
					),
					$context
				);
			} else {
				$fkey = (string) $field['key'];
				if ( isset( $seen_keys[ $fkey ] ) ) {
					$result->add_issue(
						'error',
						sprintf(
							/* translators: 1: field key, 2: group label. */
							__( 'Duplicate field key "%1$s" in group "%2$s".', 'field-group-php-json-converter' ),
							$fkey,
							$group_label
						),
						$context
					);
				}
				$seen_keys[ $fkey ] = true;
			}

			if ( ! isset( $field['name'] ) || '' === $field['name'] ) {
				$result->add_issue(
					'warning',
					sprintf(
						/* translators: 1: field label/key, 2: group label. */
						__( 'Field "%1$s" in group "%2$s" is missing a "name".', 'field-group-php-json-converter' ),
						$field['key'] ?? ( $index + 1 ),
						$group_label
					),
					$context
				);
			}

			if ( ! isset( $field['type'] ) || '' === $field['type'] ) {
				$result->add_issue(
					'error',
					sprintf(
						/* translators: 1: field label/key, 2: group label. */
						__( 'Field "%1$s" in group "%2$s" is missing a "type".', 'field-group-php-json-converter' ),
						$field['key'] ?? ( $index + 1 ),
						$group_label
					),
					$context
				);
			} elseif ( ! in_array( (string) $field['type'], self::KNOWN_FIELD_TYPES, true ) ) {
				$result->add_issue(
					'warning',
					sprintf(
						/* translators: 1: field type, 2: field label/key, 3: group label. */
						__( 'Field "%2$s" in group "%3$s" uses unknown type "%1$s".', 'field-group-php-json-converter' ),
						(string) $field['type'],
						$field['key'] ?? ( $index + 1 ),
						$group_label
					),
					$context
				);
			}
		}
	}

	/**
	 * Detect whether an array is a list (sequential integer keys from 0).
	 *
	 * Polyfill for array_is_list(), which is only available in PHP 8.1+.
	 *
	 * @since 2.0.0
	 * @param array $items Array to inspect.
	 * @return bool
	 */
	private function is_list( array $items ): bool {
		if ( function_exists( 'array_is_list' ) ) {
			return array_is_list( $items );
		}

		if ( array() === $items ) {
			return true;
		}

		$expected = 0;
		foreach ( $items as $key => $value ) {
			if ( $key !== $expected ) {
				return false;
			}
			++$expected;
		}

		return true;
	}
}
