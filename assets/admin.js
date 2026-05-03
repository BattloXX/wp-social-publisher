/* global fwwSP, jQuery */
( function ( $ ) {
	'use strict';

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	function setFeedback( $el, message, isSuccess ) {
		$el.text( message )
		   .removeClass( 'success error' )
		   .addClass( isSuccess ? 'success' : 'error' );
	}

	function startSpinner( $btn, $spinner ) {
		$btn.prop( 'disabled', true );
		$spinner.addClass( 'is-active' );
		if ( window.wp && wp.a11y ) {
			wp.a11y.speak( fwwSP.i18n.posting );
		}
	}

	function stopSpinner( $btn, $spinner ) {
		$btn.prop( 'disabled', false );
		$spinner.removeClass( 'is-active' );
	}

	// -------------------------------------------------------------------------
	// Post to Facebook
	// -------------------------------------------------------------------------

	$( document ).on( 'click', '#fww-post-facebook', function () {
		var $btn      = $( this );
		var $spinner  = $( '#fww-facebook-spinner' );
		var $feedback = $( '#fww-facebook-feedback' );

		startSpinner( $btn, $spinner );
		$feedback.text( fwwSP.i18n.posting ).removeClass( 'success error' );

		$.ajax( {
			url:  fwwSP.ajax_url,
			type: 'POST',
			data: {
				action:  'fww_post_to_facebook',
				nonce:   fwwSP.nonce_meta_box,
				post_id: fwwSP.post_id
			},
			success: function ( res ) {
				setFeedback( $feedback, res.data.message, res.success );
			},
			error: function () {
				setFeedback( $feedback, 'Request failed.', false );
			},
			complete: function () {
				stopSpinner( $btn, $spinner );
			}
		} );
	} );

	// -------------------------------------------------------------------------
	// Post to Instagram
	// -------------------------------------------------------------------------

	$( document ).on( 'click', '#fww-post-instagram', function () {
		var $btn      = $( this );
		var $spinner  = $( '#fww-instagram-spinner' );
		var $feedback = $( '#fww-instagram-feedback' );

		startSpinner( $btn, $spinner );
		$feedback.text( fwwSP.i18n.posting ).removeClass( 'success error' );

		$.ajax( {
			url:  fwwSP.ajax_url,
			type: 'POST',
			data: {
				action:  'fww_post_to_instagram',
				nonce:   fwwSP.nonce_meta_box,
				post_id: fwwSP.post_id
			},
			success: function ( res ) {
				setFeedback( $feedback, res.data.message, res.success );
			},
			error: function () {
				setFeedback( $feedback, 'Request failed.', false );
			},
			complete: function () {
				stopSpinner( $btn, $spinner );
			}
		} );
	} );

	// -------------------------------------------------------------------------
	// Copy WhatsApp text to clipboard
	// -------------------------------------------------------------------------

	$( document ).on( 'click', '#fww-copy-whatsapp', function () {
		var $btn  = $( this );
		var text  = $( '#fww-whatsapp-text' ).val();

		function markCopied() {
			var original = $btn.text();
			$btn.text( fwwSP.i18n.copied );
			setTimeout( function () {
				$btn.text( original );
			}, 2000 );
		}

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( markCopied ).catch( function () {
				legacyCopy( text, markCopied );
			} );
		} else {
			legacyCopy( text, markCopied );
		}
	} );

	function legacyCopy( text, callback ) {
		var ta = document.getElementById( 'fww-whatsapp-text' );
		ta.select();
		try {
			document.execCommand( 'copy' );
			if ( callback ) callback();
		} catch ( e ) {
			// silent – execCommand not supported
		}
	}

	// -------------------------------------------------------------------------
	// Test Facebook connection (settings page)
	// -------------------------------------------------------------------------

	$( document ).on( 'click', '#fww-test-facebook', function () {
		var $btn    = $( this );
		var $result = $( '#fww-test-facebook-result' );

		$btn.prop( 'disabled', true );
		$result.text( fwwSP.i18n.testing ).css( 'color', '#646970' );

		$.ajax( {
			url:  fwwSP.ajax_url,
			type: 'POST',
			data: {
				action: 'fww_test_facebook',
				nonce:  fwwSP.nonce_settings
			},
			success: function ( res ) {
				$result.text( res.data.message )
				       .css( 'color', res.success ? '#00a32a' : '#d63638' );
			},
			error: function () {
				$result.text( 'Request failed.' ).css( 'color', '#d63638' );
			},
			complete: function () {
				$btn.prop( 'disabled', false );
			}
		} );
	} );

	// -------------------------------------------------------------------------
	// Test Instagram connection (settings page)
	// -------------------------------------------------------------------------

	$( document ).on( 'click', '#fww-test-instagram', function () {
		var $btn    = $( this );
		var $result = $( '#fww-test-instagram-result' );

		$btn.prop( 'disabled', true );
		$result.text( fwwSP.i18n.testing ).css( 'color', '#646970' );

		$.ajax( {
			url:  fwwSP.ajax_url,
			type: 'POST',
			data: {
				action: 'fww_test_instagram',
				nonce:  fwwSP.nonce_settings
			},
			success: function ( res ) {
				$result.text( res.data.message )
				       .css( 'color', res.success ? '#00a32a' : '#d63638' );
			},
			error: function () {
				$result.text( 'Request failed.' ).css( 'color', '#d63638' );
			},
			complete: function () {
				$btn.prop( 'disabled', false );
			}
		} );
	} );

}( jQuery ) );
