jQuery( function ( $ ) {
	var $input  = $( '#fgpjc-input' );
	var $output = $( '#fgpjc-output' );
	var $mode   = $( 'input[name="mode"]' );
	var $form   = $( '.fgpjc-form' );

	function doConvert() {
		var source = $input.val();
		var mode   = $mode.filter( ':checked' ).val();

		$.ajax( {
			url: fgpjcAdmin.ajaxUrl,
			method: 'POST',
			data: {
				action: fgpjcAdmin.action,
				_wpnonce: fgpjcAdmin.nonce,
				mode: mode,
				source: source
			},
			success: function ( response ) {
				if ( response.success && response.data ) {
					if ( response.data.success ) {
						$output.val( response.data.output );
					} else if ( response.data.errors ) {
						$output.val( response.data.errors.join( "\n" ) );
					}
				}
			}
		} );
	}

	$input.on( 'input', function () {
		window.clearTimeout( $input.data( 'fgpjc-timer' ) );
		$input.data( 'fgpjc-timer', window.setTimeout( doConvert, 400 ) );
	} );

	$mode.on( 'change', doConvert );

	$( '.fgpjc-copy' ).on( 'click', function () {
		if ( $output.val() ) {
			navigator.clipboard.writeText( $output.val() );
		}
	} );

	$( '#fgpjc-bulk-export' ).on( 'click', function () {
		var $button = $( this );
		$button.prop( 'disabled', true );

		$.ajax( {
			url: fgpjcAdmin.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fgpjc_bulk_export',
				_wpnonce: fgpjcAdmin.nonce
			},
			success: function ( response ) {
				if ( response.success && response.data && response.data.json ) {
					var blob = new Blob( [ response.data.json ], { type: 'application/json' } );
					var url  = window.URL.createObjectURL( blob );
					var a    = document.createElement( 'a' );
					a.href = url;
					a.download = 'acf-field-groups.json';
					a.click();
					window.URL.revokeObjectURL( url );
				}
				$button.prop( 'disabled', false );
			},
			error: function () {
				$button.prop( 'disabled', false );
			}
		} );
	} );
} );
