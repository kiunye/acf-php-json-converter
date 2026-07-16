/**
 * Field issues tab: runs every check and renders the results.
 *
 * @param {object} $ jQuery instance.
 */
import { config, post } from './shared.js';

export function initIssues( $ ) {
	const $button = $( '#fgpjc-check-issues' );
	if ( ! $button.length ) {
		return;
	}

	const $status  = $( '#fgpjc-issues-status' );
	const $summary = $( '#fgpjc-issues-summary' );
	const $list    = $( '#fgpjc-issues-list' );

	function announce( message, isError ) {
		$status.text( message );
		$status.attr( 'role', isError ? 'alert' : 'status' );
	}

	$button.on( 'click', function () {
		$button.prop( 'disabled', true );
		announce( '' );
		$list.empty();
		$summary.prop( 'hidden', true ).empty();

		const source = $( '#fgpjc-input' ).val();
		const isJson = /^\s*[[{]/.test( source );
		const format = isJson ? 'json' : 'php';

		post( config.issuesAction, { source, format, _wpnonce: config.issuesNonce } )
			.then( ( response ) => {
				if ( ! response.success || ! response.data ) {
					announce( 'Issue check failed.', true );
					return;
				}

				const data    = response.data;
				const issues  = data.issues || [];
				const errors  = data.errors || 0;
				const warnings = data.warnings || 0;

				if ( 0 === issues.length ) {
					announce( 'No issues were detected.' );
					return;
				}

				$summary
					.prop( 'hidden', false )
					.text(
						errors + ' error(s), ' + warnings + ' warning(s)'
					);

				issues.forEach( ( issue ) => {
					const $item = $( '<li>' )
						.addClass( 'fgpjc-issue fgpjc-issue-' + issue.severity )
						.text( issue.message );
					if ( issue.context ) {
						$item.append( $( '<span>' ).addClass( 'fgpjc-issue-context' ).text( ' (' + issue.context + ')' ) );
					}
					$list.append( $item );
				} );

				announce( errors + ' error(s) and ' + warnings + ' warning(s) found.' );
			} )
			.catch( () => announce( 'Issue check request failed.', true ) )
			.always( () => $button.prop( 'disabled', false ) );
	} );
}
