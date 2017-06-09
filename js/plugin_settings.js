// De-Authorize Slack.
jQuery( document ).on( 'click', '#gform_slack_deauth_button', function( e ){

	// Prevent default event.
	e.preventDefault();

	// Confirm deletion.
	if ( ! confirm( gform_slack_pluginsettings_strings.disconnect ) ) {
		return false;
	}

	// Set disabled state.
	jQuery( this ).attr( 'disabled', 'disabled' );

	// De-Authorize.
	jQuery.ajax( {
		async:     false,
		url:       ajaxurl,
		dataType: 'json',
		data:     { action: 'gfslack_deauthorize' },
		success:  function( response ) {

			if ( response.success ) {
				window.location.reload();
			} else {
				alert( response.data.message );
			}

			jQuery( this ).removeAttr( 'disabled' );

		}
	} );

} );
