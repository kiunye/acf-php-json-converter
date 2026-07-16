/**
 * Shared helpers for the converter admin UI.
 *
 * @param {object} cfg Configuration supplied via wp_localize_script (fgpjcAdmin).
 */
export const config = window.fgpjcAdmin || {};

/**
 * Debounce a function by the given delay in milliseconds.
 *
 * @param {Function} fn Function to debounce.
 * @param {number} delay Milliseconds to wait.
 * @return {Function} Debounced wrapper.
 */
export function debounce( fn, delay ) {
	let timer = null;
	return function ( ...args ) {
		window.clearTimeout( timer );
		timer = window.setTimeout( () => fn.apply( this, args ), delay );
	};
}

/**
 * Perform an AJAX request against the converter endpoint.
 *
 * @param {string} action AJAX action name.
 * @param {object} data Extra POST data.
 * @return {Promise<object>} Resolved with the parsed JSON response.
 */
export function post( action, data ) {
	return new Promise( ( resolve, reject ) => {
		window.jQuery
			.ajax( {
				url: config.ajaxUrl,
				method: 'POST',
				data: Object.assign(
					{
						action,
						_wpnonce: config.nonce,
					},
					data
				),
			} )
			.done( resolve )
			.fail( ( jqXhr ) => reject( jqXhr.responseJSON || { success: false } ) );
	} );
}
