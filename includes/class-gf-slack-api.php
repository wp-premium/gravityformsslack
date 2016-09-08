<?php

/**
 * Gravity Forms Slack Add-On API library.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2016, Rocketgenius
 */
class GF_Slack_API {

	/**
	 * Base Slack API URL.
	 *
	 * @since  1.0
	 * @var    string
	 * @access protected
	 */
	protected $api_url = 'https://slack.com/api/';

	/**
	 * Initialize Slack API library.
	 *
	 * @since  1.0
	 * @access public
	 * @param  string $auth_token Authentication token.
	 */
	public function __construct( $auth_token ) {

		$this->auth_token = $auth_token;

	}

	/**
	 * Make API request.
	 *
	 * @since  1.0
	 * @access public
	 * @param  string $path Request path.
	 * @param  array  $options Request option.
	 * @param  string $method (default: 'GET') Request method.
	 *
	 * @return array
	 */
	public function make_request( $path, $options = array(), $method = 'GET' ) {

		// Get API URL.
		$api_url = $this->api_url;

		// If team name is defined in request options, add team name to API URL.
		if ( rgar( $options, 'team' ) ) {
			$api_url = str_replace( 'slack.com', $options['team'] . '.slack.com', $api_url );
			unset( $options['team'] );
		}

		// Build base request options string.
		$request_options = '?token='. $this->auth_token;

		// Add options if this is a GET request.
		$request_options .= ( 'GET' === $method && ! empty( $options ) ) ? '&'. http_build_query( $options ) : null;

		// Build request URL.
		$request_url = $api_url . $path . $request_options;

		// Build request arguments.
		$args = array(
			'body'   => 'GET' !== $method ? $options : null,
			'method' => $method,
		);

		// Execute request.
		$response = wp_remote_request( $request_url, $args );

		// If WP_Error, die. Otherwise, return decoded JSON.
		if ( is_wp_error( $response ) ) {

			die( 'Request failed. '. $response->get_error_messages() );

		} else {

			return json_decode( $response['body'], true );

		}

	}

	/**
	 * Test authentication token.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return bool
	 */
	public function auth_test() {

		return $this->make_request( 'auth.test' );

	}

	/**
	 * Get a channel.
	 *
	 * @since  1.0
	 * @access public
	 * @param  string $channel Channel name.
	 *
	 * @return array
	 */
	public function get_channel( $channel ) {

		return $this->make_request( 'channels.info', array( 'channel' => $channel ) );

	}

	/**
	 * Get all channels.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function get_channels() {

		return $this->make_request( 'channels.list' );

	}

	/**
	 * Get a group.
	 *
	 * @since  1.0
	 * @access public
	 * @param  string $group Group ID.
	 *
	 * @return array
	 */
	public function get_group( $group ) {

		return $this->make_request( 'groups.info', array( 'channel' => $group ) );

	}

	/**
	 * Get all groups.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function get_groups() {

		return $this->make_request( 'groups.list' );

	}

	/**
	 * Get current team info.
	 *
	 * @since  1.4.1
	 * @access public
	 *
	 * @return array
	 */
	public function get_team_info() {

		return $this->make_request( 'team.info' );

	}

	/**
	 * Get a user.
	 *
	 * @since  1.0
	 * @access public
	 * @param  string $user User ID.
	 *
	 * @return array
	 */
	public function get_user( $user ) {

		return $this->make_request( 'users.info', array( 'user' => $user ) );

	}

	/**
	 * Get all users.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function get_users() {

		return $this->make_request( 'users.list' );

	}

	/**
	 * Invite user to team.
	 *
	 * @since  1.4
	 * @access public
	 * @param  string $team Team name.
	 * @param  string $email Email address.
	 *
	 * @return array
	 */
	public function invite_user( $team, $email ) {

		return $this->make_request( 'users.admin.invite', array( 'team' => $team, 'email' => $email, 'set_active' => true ), 'POST' );

	}

	/**
	 * Open IM channel.
	 *
	 * @since  1.0
	 * @access public
	 * @param  string $user User ID.
	 *
	 * @return array
	 */
	public function open_im( $user ) {

		return $this->make_request( 'users.info', array( 'user' => $user ), 'POST' );

	}

	/**
	 * Post message to channel.
	 *
	 * @since  1.0
	 * @access public
	 * @param  array $message Message details.
	 *
	 * @return array
	 */
	public function post_message( $message ) {

		return $this->make_request( 'chat.postMessage', $message, 'POST' );

	}

}
