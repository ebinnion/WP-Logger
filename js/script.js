/* global wpCookies */

jQuery( document ).ready( function( $ ){
	var wrap    = $( '.wrap' ),
		ajaxContain = $( '#ajax' );

	/*
	 * Generate and display the log select whenever a user changes the plugin that
	 * they would like to view a report for.
	 */
	$( 'body' ).on( 'change', '#plugin-select', function(){
		var newPluginSelectVal = $(this).val();

		ajaxContain.addClass( 'ajaxed' );

		jQuery.post(
			ajaxurl,
			{
				'action': 'get_logger_log_select',
				'plugin_name': newPluginSelectVal
			},
			function(response){

				// This is fired on successfully returning the log select
				$( '#log-select-contain' ).html( response );
				$( '#session-select-contain' ).html( '' );
			}
		)
		.always(function(){

			// Whether the AJAX fails or succeeds, always remove the spinner on completion.
			ajaxContain.removeClass( 'ajaxed' );
		});
	});

	// Calls the process_email_log method to send a log as an email attachment.
	$( 'body' ).on( 'click', '#send-logger-email', function(){
		var formdata = $( '#logger-form' ).serialize(),
			email    = $( '#email-results' ).val(),
			p        = $( this ).parent(),
			t        = $( this );

		// Add spinner and disable button on click.
		p.addClass( 'ajaxed' );
		t.attr( 'disabled', 'disabled' );

		formdata += '&action=send_log_email&email-logs=' + encodeURIComponent( email );

		jQuery.post(
			ajaxurl,
			formdata,
			function(response){

				// Will return -1 if the ajax request fails
				if( -1 != response ) {
					$( '#email-response' ).html( response );
				}
			}
		)
		.always(function(){

			// Whether the AJAX fails or succeeds, always remove the spinner and enable button.
			p.removeClass( 'ajaxed' );
			t.removeAttr( 'disabled' );
		});
	});

	// Removes the session select input when a user removes the session tag.
	$( 'body' ).on( 'click', '.clear-session', function(e){
		$( '.tagchecklist' ).remove();
		$( '#session-select' ).remove();
	});
});
