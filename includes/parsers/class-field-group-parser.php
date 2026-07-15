<?php
/**
 * Tokenizer based extractor for Advanced Custom Fields local field groups.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Parsers
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Parsers;

/**
 * Extracts field group definitions registered through acf_add_local_field_group()
 * and acf_add_local_field_groups() using PHP's built in tokenizer.
 *
 * The parser only resolves literal array/value expressions. Anything built
 * dynamically (variables, function calls, concatenation) is reported as a
 * clear, specific error rather than producing a silent partial result.
 *
 * @since 2.0.0
 */
class Field_Group_Parser {

	/**
	 * Functions whose first argument is treated as a field group definition.
	 *
	 * @since 2.0.0
	 * @var array<int,string>
	 */
	private const TARGETS = array(
		'acf_add_local_field_group',
		'acf_add_local_field_groups',
	);

	/**
	 * File currently being parsed, for error reporting.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $file = '';

	/**
	 * Parse a PHP file from disk.
	 *
	 * @since 2.0.0
	 * @param string $file_path Path to the PHP file.
	 * @return Parser_Result
	 */
	public function parse_file( string $file_path ): Parser_Result {
		$this->file = $file_path;

		if ( ! is_file( $file_path ) || ! is_readable( $file_path ) ) {
			$result = new Parser_Result();
			$result->add_error(
				new Parser_Error( $file_path, 0, 'unreadable_file', sprintf( 'File "%s" could not be read.', $file_path ) )
			);
			return $result;
		}

		$source = file_get_contents( $file_path );
		if ( false === $source ) {
			$result = new Parser_Result();
			$result->add_error(
				new Parser_Error( $file_path, 0, 'unreadable_file', sprintf( 'File "%s" could not be read.', $file_path ) )
			);
			return $result;
		}

		return $this->parse_source( $source );
	}

	/**
	 * Parse PHP source for field group definitions.
	 *
	 * @since 2.0.0
	 * @param string $source PHP source code.
	 * @return Parser_Result
	 */
	public function parse_source( string $source ): Parser_Result {
		$result = new Parser_Result();

		$this->detect_syntax_errors( $source, $result );

		$reader = new Token_Reader( $source );

		while ( ! $reader->at_end() ) {
			$reader->skip_whitespace_and_comments();
			if ( $reader->at_end() ) {
				break;
			}

			if ( ! $reader->is_token( T_STRING ) ) {
				$reader->next();
				continue;
			}

			$name = $reader->current()[1];
			if ( ! in_array( $name, self::TARGETS, true ) ) {
				$reader->next();
				continue;
			}

			$reader->next();
			$reader->skip_whitespace_and_comments();
			if ( ! $reader->is_char( '(' ) ) {
				continue;
			}

			$reader->next();
			$reader->skip_whitespace_and_comments();

			try {
				$value = $this->parse_value( $reader );
			} catch ( Parser_Exception $e ) {
				$this->skip_to_enclosing_paren( $reader );
				$result->add_error(
					new Parser_Error(
						$this->file,
						$e->get_line(),
						'dynamic_group',
						sprintf(
							'Field group passed to %s() at line %d is built dynamically and cannot be parsed statically.',
							$name,
							$e->get_line()
						)
					)
				);
				continue;
			}

			$reader->skip_whitespace_and_comments();
			if ( $reader->is_char( ')' ) ) {
				$reader->next();
			}

			$this->collect_groups( $value, $name, $result );
		}

		return $result;
	}

	/**
	 * Add parsed value(s) to the result, accounting for singular vs plural targets.
	 *
	 * @since 2.0.0
	 * @param mixed         $value  Parsed argument value.
	 * @param string        $name   Target function name.
	 * @param Parser_Result $result Result to populate.
	 * @return void
	 */
	private function collect_groups( $value, string $name, Parser_Result $result ): void {
		if ( 'acf_add_local_field_groups' === $name ) {
			if ( ! is_array( $value ) ) {
				$result->add_error(
					new Parser_Error( $this->file, 0, 'parse_error', 'acf_add_local_field_groups() expects an array of field groups.' )
				);
				return;
			}
			foreach ( $value as $group ) {
				if ( is_array( $group ) ) {
					$result->add_field_group( $group );
				}
			}
			return;
		}

		if ( is_array( $value ) ) {
			$result->add_field_group( $value );
			return;
		}

		$result->add_error(
			new Parser_Error( $this->file, 0, 'parse_error', 'acf_add_local_field_group() expects an array argument.' )
		);
	}

	/**
	 * Parse a single value, recursing into arrays.
	 *
	 * @since 2.0.0
	 * @param Token_Reader $reader Token reader positioned before the value.
	 * @return mixed
	 * @throws Parser_Exception When a non literal expression is encountered.
	 */
	private function parse_value( Token_Reader $reader ): mixed {
		$reader->skip_whitespace_and_comments();

		if ( $reader->at_end() ) {
			throw new Parser_Exception( $reader->current_line(), 'Unexpected end of file while parsing a value.' );
		}

		$tok = $reader->current();

		if ( $reader->is_token( T_ARRAY ) ) {
			$reader->next();
			$reader->skip_whitespace_and_comments();
			if ( ! $reader->is_char( '(' ) ) {
				throw new Parser_Exception( $reader->current_line(), 'Expected "(" after array keyword.' );
			}
			$reader->next();
			return $this->parse_array_body( $reader, ')' );
		}

		if ( $reader->is_char( '[' ) ) {
			$reader->next();
			return $this->parse_array_body( $reader, ']' );
		}

		if ( $reader->is_char( '-' ) || $reader->is_char( '+' ) ) {
			$sign = $reader->current();
			$reader->next();
			$reader->skip_whitespace_and_comments();
			$next = $reader->current();
			if ( $reader->is_token( T_LNUMBER ) ) {
				$reader->next();
				return ( '-' === $sign ? -1 : 1 ) * (int) $next[1];
			}
			if ( $reader->is_token( T_DNUMBER ) ) {
				$reader->next();
				return ( '-' === $sign ? -1.0 : 1.0 ) * (float) $next[1];
			}
			throw new Parser_Exception( $reader->current_line(), 'Unsupported expression in field group definition.' );
		}

		if ( $reader->is_token( T_LNUMBER ) ) {
			$reader->next();
			return (int) $tok[1];
		}

		if ( $reader->is_token( T_DNUMBER ) ) {
			$reader->next();
			return (float) $tok[1];
		}

		if ( $reader->is_token( T_CONSTANT_ENCAPSED_STRING ) ) {
			$reader->next();
			return $this->unquote( $tok[1] );
		}

		if ( $reader->is_token( T_STRING ) ) {
			$lower = strtolower( $tok[1] );
			if ( 'true' === $lower ) {
				$reader->next();
				return true;
			}
			if ( 'false' === $lower ) {
				$reader->next();
				return false;
			}
			if ( 'null' === $lower ) {
				$reader->next();
				return null;
			}
			throw new Parser_Exception( $reader->current_line(), 'Unresolvable constant "' . $tok[1] . '" in field group definition.' );
		}

		throw new Parser_Exception( $reader->current_line(), 'Dynamic expression not supported in field group definition.' );
	}

	/**
	 * Parse the body of an array until the given closing character.
	 *
	 * @since 2.0.0
	 * @param Token_Reader $reader Token reader positioned after the opening character.
	 * @param string       $close  Closing character: ")" for long arrays or "]" for short arrays.
	 * @return array
	 * @throws Parser_Exception When the array is malformed or unterminated.
	 */
	private function parse_array_body( Token_Reader $reader, string $close ): array {
		$array = array();

		while ( true ) {
			$reader->skip_whitespace_and_comments();

			if ( $reader->at_end() ) {
				throw new Parser_Exception( $reader->current_line(), 'Unterminated array in field group definition.' );
			}

			if ( $reader->is_char( $close ) ) {
				$reader->next();
				return $array;
			}

			$key_value = $this->parse_value( $reader );

			$reader->skip_whitespace_and_comments();
			if ( $reader->is_token( T_DOUBLE_ARROW ) ) {
				$reader->next();
				$value               = $this->parse_value( $reader );
				$array[ $key_value ] = $value;
			} else {
				$array[] = $key_value;
			}

			$reader->skip_whitespace_and_comments();
			if ( $reader->at_end() ) {
				throw new Parser_Exception( $reader->current_line(), 'Unterminated array in field group definition.' );
			}

			if ( $reader->is_char( ',' ) ) {
				$reader->next();
				continue;
			}

			if ( $reader->is_char( $close ) ) {
				$reader->next();
				return $array;
			}

			throw new Parser_Exception( $reader->current_line(), 'Unexpected token in array definition.' );
		}
	}

	/**
	 * Advance the reader past the closing parenthesis of the current call.
	 *
	 * @since 2.0.0
	 * @param Token_Reader $reader Token reader positioned inside the call arguments.
	 * @return void
	 */
	private function skip_to_enclosing_paren( Token_Reader $reader ): void {
		$depth = 1;
		while ( ! $reader->at_end() ) {
			$tok = $reader->next();
			if ( ! is_string( $tok ) ) {
				continue;
			}
			if ( '(' === $tok ) {
				++$depth;
			} elseif ( ')' === $tok ) {
				--$depth;
				if ( 0 === $depth ) {
					return;
				}
			}
		}
	}

	/**
	 * Scan the whole token stream for obvious PHP syntax errors.
	 *
	 * @since 2.0.0
	 * @param string        $source PHP source code.
	 * @param Parser_Result $result Result to populate with any syntax errors.
	 * @return void
	 */
	private function detect_syntax_errors( string $source, Parser_Result $result ): void {
		$reader = new Token_Reader( $source );
		$depth  = 0;

		while ( ! $reader->at_end() ) {
			$tok = $reader->next();

			if ( is_string( $tok ) ) {
				if ( '(' === $tok || '[' === $tok || '{' === $tok ) {
					++$depth;
				} elseif ( ')' === $tok || ']' === $tok || '}' === $tok ) {
					--$depth;
				}
				continue;
			}

			if ( ! is_array( $tok ) ) {
				continue;
			}

			$id = $tok[0];
			if ( ( defined( 'T_ERROR' ) && constant( 'T_ERROR' ) === $id ) || ( defined( 'T_BAD_CHARACTER' ) && constant( 'T_BAD_CHARACTER' ) === $id ) ) {
				$result->add_error(
					new Parser_Error(
						$this->file,
						(int) ( $tok[2] ?? 0 ),
						'syntax_error',
						sprintf( 'PHP syntax error detected near line %d.', (int) ( $tok[2] ?? 0 ) )
					)
				);
			}
		}

		if ( $depth !== 0 ) {
			$result->add_error(
				new Parser_Error(
					$this->file,
					0,
					'syntax_error',
					'Unbalanced parentheses, brackets, or braces detected; the file may contain a PHP syntax error.'
				)
			);
		}
	}

	/**
	 * Convert a quoted token string into its literal PHP value.
	 *
	 * @since 2.0.0
	 * @param string $text Token text including surrounding quotes.
	 * @return string
	 */
	private function unquote( string $text ): string {
		$first = $text[0];
		$last  = $text[ strlen( $text ) - 1 ];

		if ( ( "'" === $first && "'" === $last ) || ( '"' === $first && '"' === $last ) ) {
			$inner = substr( $text, 1, -1 );
			if ( "'" === $first ) {
				$inner = str_replace( "\\'", "'", $inner );
				$inner = str_replace( '\\\\', '\\', $inner );
				return $inner;
			}
			return stripcslashes( $inner );
		}

		return $text;
	}
}
