/**
 * Active theme scan and convert behavior.
 *
 * @param {object} $ jQuery instance.
 */
import { config, post } from './shared.js';

export function initTheme( $ ) {
	const $button = $( '#fgpjc-scan' );
	if ( ! $button.length ) {
		return;
	}

	const $status = $( '#fgpjc-scan-status' );
	const $results = $( '#fgpjc-scan-results' );
	const $list = $results.find( 'ul' );

	function announce( message, isError ) {
		$status.text( message );
		$status.attr( 'role', isError ? 'alert' : 'status' );
	}

	$button.on( 'click', function () {
		$button.prop( 'disabled', true );
		announce( '' );
		$results.prop( 'hidden', true );
		$list.empty();

		post( config.themeAction, { theme_action: 'scan', _wpnonce: config.themeNonce } )
			.then( ( response ) => {
				if ( ! response.success || ! response.data ) {
					announce( 'Scan failed.', true );
					return;
				}

				const groups = response.data.groups || [];
				if ( groups.length === 0 ) {
					announce( 'No field groups found in the active theme.' );
					return;
				}

				groups.forEach( ( group ) => {
					const label = group.title || group.key || '(unnamed)';
					const source = 'json' === group.source ? 'Local JSON' : 'PHP';
					$list.append(
						$( '<li>' ).text( label + ' — ' + source )
					);
				} );

				$results.prop( 'hidden', false );
				announce( groups.length + ' field group(s) found.' );
			} )
			.catch( () => announce( 'Scan request failed.', true ) )
			.always( () => $button.prop( 'disabled', false ) );
	} );
}
