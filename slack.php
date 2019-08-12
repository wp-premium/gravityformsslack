<?php
/**
Plugin Name: Gravity Forms Slack Add-On
Plugin URI: https://www.gravityforms.com
Description: Integrates Gravity Forms with Slack, allowing alerts for Gravity Forms activity to be posted to your Slack channels.
Version: 1.9
Author: rocketgenius
Author URI: https://www.rocketgenius.com
License: GPL-2.0+
Text Domain: gravityformsslack
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2009 rocketgenius
last updated: October 20, 2010

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

define( 'GF_SLACK_VERSION', '1.9' );

// If Gravity Forms is loaded, bootstrap the Slack Add-On.
add_action( 'gform_loaded', array( 'GF_Slack_Bootstrap', 'load' ), 5 );

/**
 * Class GF_Slack_Bootstrap
 *
 * Handles the loading of the Slack Add-On and registers with the Add-On Framework.
 */
class GF_Slack_Bootstrap {

	/**
	 * If the Feed Add-On Framework exists, Slack Add-On is loaded.
	 *
	 * @access public
	 * @static
	 */
	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		require_once( 'class-gf-slack.php' );

		GFAddOn::register( 'GFSlack' );

	}

}

/**
 * Returns an instance of the GFSlack class
 *
 * @see    GFSlack::get_instance()
 *
 * @return object GFSlack
 */
function gf_slack() {
	return GFSlack::get_instance();
}
