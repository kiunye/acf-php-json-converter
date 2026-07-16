/**
 * Entry point for the converter admin UI.
 *
 * Imports the feature modules and wires them up once the DOM is ready.
 */
import { initConverter } from './modules/converter.js';
import { initAcfGroups } from './modules/acf-groups.js';
import { initTheme } from './modules/theme.js';

window.jQuery( function ( $ ) {
	initConverter( $ );
	initAcfGroups( $ );
	initTheme( $ );
} );
