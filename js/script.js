/* global wpCookies */

jQuery( document ).ready( function( $ ){
	var wrap            = $( '.wrap' ),
		actions         = $( '.tablenav .actions.bulkactions' );

	/*
	 * Generate and display the log select whenever a user changes the plugin that
	 * they would like to view a report for.
	 */
	$( 'body' ).on( 'change', '#plugin-select', function(){
		var newPluginSelectVal = $(this).val();

		actions.addClass( 'ajaxed' );

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
			actions.removeClass( 'ajaxed' );
		});
	});

	$( 'body' ).on( 'change', '#log-select', function(){
		var newLogSelectVal = $(this).val();

		actions.addClass( 'ajaxed' );

		jQuery.post(
			ajaxurl,
			{
				'action': 'get_logger_session_select',
				'log_select': newLogSelectVal
			},
			function(response){

				// This is fired on successfully returning the log select
				$( '#session-select-contain' ).html( response );
			}
		)
		.always(function(){

			// Whether the AJAX fails or succeeds, always remove the spinner on completion.
			actions.removeClass( 'ajaxed' );
		});
	});

	$( '#send-logger-email' ).click( function() {
		loggerForm.prepend( '<input type="hidden" name="send_logger_email" value="1" >' );
		loggerForm.submit();
	});
});
