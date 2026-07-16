/**
 * Entry point for the converter admin UI.
 *
 * Imports the feature modules and wires them up once the DOM is ready.
 */
import { initConverter } from './modules/converter.js';
import { initAcfGroups } from './modules/acf-groups.js';
import { initTheme } from './modules/theme.js';
import { initIssues } from './modules/issues.js';

function initTabs( $ ) {
	const $tabs = $( '.fgpjc-tab' );
	if ( ! $tabs.length ) {
		return;
	}

	$tabs.on( 'click', function () {
		const $tab = $( this );
		const target = $tab.attr( 'aria-controls' );

		$tabs.removeClass( 'is-active' ).attr( 'aria-selected', 'false' );
		$tab.addClass( 'is-active' ).attr( 'aria-selected', 'true' );

		$( '.fgpjc-panel' ).prop( 'hidden', true );
		$( '#' + target ).prop( 'hidden', false );
	} );
}

window.jQuery( function ( $ ) {
	initTabs( $ );
	initConverter( $ );
	initAcfGroups( $ );
	initTheme( $ );
	initIssues( $ );
} );
