jQuery( document ).ready( function( $ ) {

	var strings = gform_slack_pluginsettings_strings;

	$( document ).on( 'click', '#gform_slack_deauth_button', deAuthorize );

	function deAuthorize() {

		// Get button.
		var $button = $( '#gform_slack_deauth_button' );

		// Confirm deletion.
		if ( ! confirm( strings.disconnect ) ) {
			return false;
		}

		// Set disabled state.
		$button.attr( 'disabled', 'disabled' );

		// De-Authorize.
		$.ajax( {
			async:     false,
			url:       ajaxurl,
			dataType: 'json',
			data:     {
				action: 'gfslack_deauthorize',
				nonce:  strings.nonce_deauthorize,
			},
			success:  function( response ) {

				if ( response.success ) {
					window.location.href = strings.pluginSettingsURL;
				} else {
					alert( response.data.message );
				}

				$button.removeAttr( 'disabled' );

			}
		} );

	}

} );