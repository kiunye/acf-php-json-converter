<?php
/**
 * Low level reader over the token stream produced by token_get_all().
 *
 * Provides forward only traversal with whitespace and comment skipping so the
 * parser can focus on structure rather than token noise.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Parsers
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Parsers;

/**
 * Reads PHP tokens emitted by token_get_all().
 *
 * @since 2.0.0
 */
class Token_Reader {

	/**
	 * Token stream.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	private array $tokens;

	/**
	 * Current position in the token stream.
	 *
	 * @since 2.0.0
	 * @var int
	 */
	private int $pos = 0;

	/**
	 * Build a reader from PHP source.
	 *
	 * @since 2.0.0
	 * @param string $source PHP source code.
	 */
	public function __construct( string $source ) {
		$this->tokens = token_get_all( $source );
	}

	/**
	 * Whether the reader has consumed all tokens.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function at_end(): bool {
		return $this->pos >= count( $this->tokens );
	}

	/**
	 * Return the raw token at the current position without advancing.
	 *
	 * @since 2.0.0
	 * @return mixed
	 */
	public function current(): mixed {
		return $this->tokens[ $this->pos ] ?? null;
	}

	/**
	 * Advance the reader and return the next raw token.
	 *
	 * @since 2.0.0
	 * @return mixed
	 */
	public function next(): mixed {
		return $this->tokens[ $this->pos++ ] ?? null;
	}

	/**
	 * Peek at a token relative to the current position without advancing.
	 *
	 * @since 2.0.0
	 * @param int $offset Lookahead distance.
	 * @return mixed
	 */
	public function peek( int $offset = 0 ): mixed {
		return $this->tokens[ $this->pos + $offset ] ?? null;
	}

	/**
	 * Advance past whitespace and comment tokens.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function skip_whitespace_and_comments(): void {
		while ( ! $this->at_end() ) {
			$tok = $this->tokens[ $this->pos ];
			if ( is_array( $tok ) ) {
				$id = $tok[0];
				if ( T_WHITESPACE === $id || T_COMMENT === $id || T_DOC_COMMENT === $id || T_INLINE_HTML === $id ) {
					++$this->pos;
					continue;
				}
			}
			break;
		}
	}

	/**
	 * Line number of the current token, or 0 when unavailable.
	 *
	 * @since 2.0.0
	 * @return int
	 */
	public function current_line(): int {
		$tok = $this->current();
		return is_array( $tok ) ? (int) ( $tok[2] ?? 0 ) : 0;
	}

	/**
	 * Whether the current token is a single character token.
	 *
	 * @since 2.0.0
	 * @param string $char Single character token such as '(' or ','.
	 * @return bool
	 */
	public function is_char( string $char ): bool {
		$tok = $this->current();
		return is_string( $tok ) && $tok === $char;
	}

	/**
	 * Whether the current token is a T_* token of the given id.
	 *
	 * @since 2.0.0
	 * @param int $token_id Token identifier from token_get_all().
	 * @return bool
	 */
	public function is_token( int $token_id ): bool {
		$tok = $this->current();
		return is_array( $tok ) && $tok[0] === $token_id;
	}
}
