/**
 * Live converter behavior: debounced AJAX conversion and clipboard copy.
 *
 * @param {object} $ jQuery instance.
 */
import { config, debounce, post } from './shared.js';

export function initConverter( $ ) {
	const $input = $( '#fgpjc-input' );
	const $output = $( '#fgpjc-output' );
	const $mode = $( 'input[name="mode"]' );
	const $status = $( '#fgpjc-convert-status' );

	function announce( message, isError ) {
		if ( ! $status.length ) {
			return;
		}
		$status.text( message );
		$status.attr( 'role', isError ? 'alert' : 'status' );
	}

	function convert() {
		const source = $input.val();
		const mode = $mode.filter( ':checked' ).val();

		if ( ! source.trim() ) {
			$output.val( '' );
			announce( '' );
			return;
		}

		post( config.action, { mode, source } )
			.then( ( response ) => {
				if ( response.success && response.data ) {
					if ( response.data.success ) {
						$output.val( response.data.output );
						announce( '' );
					} else if ( response.data.errors && response.data.errors.length ) {
						$output.val( response.data.errors.join( "\n" ) );
						announce( response.data.errors[ 0 ], true );
					}
				}
			} )
			.catch( () => announce( 'Conversion request failed.', true ) );
	}

	$input.on( 'input', debounce( convert, 400 ) );
	$mode.on( 'change', convert );

	$( '.fgpjc-copy' ).on( 'click', function () {
		if ( $output.val() && window.navigator.clipboard ) {
			window.navigator.clipboard.writeText( $output.val() );
			announce( 'Output copied to clipboard.' );
		}
	} );
}
