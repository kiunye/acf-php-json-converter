/**
 * ACF field group bulk export and JSON import behavior.
 *
 * @param {object} $ jQuery instance.
 */
import { config, post } from './shared.js';

function download( filename, contents, mime ) {
	const blob = new Blob( [ contents ], { type: mime } );
	const url = window.URL.createObjectURL( blob );
	const anchor = document.createElement( 'a' );
	anchor.href = url;
	anchor.download = filename;
	anchor.click();
	window.URL.revokeObjectURL( url );
}

export function initAcfGroups( $ ) {
	const $exportStatus = $( '#fgpjc-export-status' );

	function announceExport( message, isError ) {
		if ( ! $exportStatus.length ) {
			return;
		}
		$exportStatus.text( message );
		$exportStatus.attr( 'role', isError ? 'alert' : 'status' );
	}

	$( '#fgpjc-bulk-export' ).on( 'click', function () {
		const $button = $( this );
		$button.prop( 'disabled', true );
		announceExport( '' );

		post( 'fgpjc_bulk_export', {} )
			.then( ( response ) => {
				if ( response.success && response.data && response.data.json ) {
					download( 'acf-field-groups.json', response.data.json, 'application/json' );
					announceExport(
						response.data.count + ' field group(s) exported.'
					);
				} else {
					announceExport( 'Export failed.', true );
				}
			} )
			.catch( () => announceExport( 'Export request failed.', true ) )
			.always( () => $button.prop( 'disabled', false ) );
	} );

	$( '#fgpjc-import-file' ).on( 'change', function () {
		const file = this.files && this.files[ 0 ];
		if ( ! file ) {
			return;
		}

		const reader = new FileReader();
		reader.onload = function () {
			const contents = String( reader.result || '' );
			post( config.action, { mode: 'json_to_php', source: contents } )
				.then( ( response ) => {
					const $output = $( '#fgpjc-output' );
					if ( response.success && response.data && response.data.success ) {
						$output.val( response.data.output );
						announceExport( 'Imported file converted to PHP.' );
					} else if ( response.data && response.data.errors ) {
						$output.val( response.data.errors.join( "\n" ) );
						announceExport( response.data.errors[ 0 ], true );
					}
				} )
				.catch( () => announceExport( 'Import request failed.', true ) );
		};
		reader.readAsText( file );
	} );
}
