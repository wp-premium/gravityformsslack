jQuery( document ).ready( function( $ ) {

	var strings = gform_slack_pluginsettings_strings;

	// Detect failed legacy token.
	if ( $( 'input#auth_token' ) && $( 'input#auth_token' ).next().is( '.gf_invalid' ) ) {
		showLegacyAuth();
	}

	$( document ).on( 'click', '#gform_slack_deauth_button', deAuthorize );
	$( document ).on( 'click', '#gform_slack_auth_legacy', showLegacyAuth );
	$( document ).on( 'click', '#gform_slack_auth_standard', showStandardAuth );

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
			data:     { action: 'gfslack_deauthorize' },
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

	function showLegacyAuth() {

		// Hide standard auth.
		$( '#gform_slack_auth_container' ).hide();

		// Show legacy auth.
		$( '#auth_token, #auth_token + .gf_invalid, table.gforms_form_settings tbody tr:last-child' ).show();

	}

	function showStandardAuth() {

		// Show standard auth.
		$( '#auth_token, #auth_token + .gf_invalid, table.gforms_form_settings tbody tr:last-child' ).hide();

		// Hide legacy auth.
		$( '#gform_slack_auth_container' ).show();

	}

} );