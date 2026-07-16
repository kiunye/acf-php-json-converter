<?php
/**
 * Outcome of a field group validation pass.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Utilities
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Utilities;

/**
 * Holds validation issues found while inspecting field groups.
 *
 * Each issue carries a severity ("error", "warning", or "ok"), a human
 * readable message, and an optional context (where the issue was found).
 *
 * @since 2.0.0
 */
class Validation_Result {

	/**
	 * Validation issues.
	 *
	 * @since 2.0.0
	 * @var array<int,array{severity:string,message:string,context:string}>
	 */
	private array $issues = array();

	/**
	 * Record an issue.
	 *
	 * @since 2.0.0
	 * @param string $severity "error", "warning", or "ok".
	 * @param string $message  Human readable message.
	 * @param string $context  Optional source context.
	 * @return void
	 */
	public function add_issue( string $severity, string $message, string $context = '' ): void {
		$this->issues[] = array(
			'severity' => $severity,
			'message'  => $message,
			'context'  => $context,
		);
	}

	/**
	 * All recorded issues.
	 *
	 * @since 2.0.0
	 * @return array<int,array{severity:string,message:string,context:string}>
	 */
	public function get_issues(): array {
		return $this->issues;
	}

	/**
	 * Whether any issues were recorded.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function has_issues(): bool {
		return count( $this->issues ) > 0;
	}

	/**
	 * Whether any error-severity issues were recorded.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function has_errors(): bool {
		foreach ( $this->issues as $issue ) {
			if ( 'error' === $issue['severity'] ) {
				return true;
			}
		}
		return false;
	}
}
