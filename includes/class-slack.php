<?php
	
	class Slack {
		
		protected $api_url = 'https://slack.com/api/';
		
		function __construct( $auth_token ) {
			
			$this->auth_token = $auth_token;
			
		}
		
		/**
		 * Make API request.
		 * 
		 * @access public
		 * @param string $path
		 * @param array $options
		 * @param bool $return_status (default: false)
		 * @param string $method (default: 'GET')
		 * @return void
		 */
		function make_request( $path, $options = array(), $method = 'GET' ) {
			
			/* Build base request options string. */
			$request_options = '?token='. $this->auth_token;
			
			/* Add options if this is a GET request. */
			$request_options .= ( $method == 'GET' && ! empty( $options ) ) ? '&'. http_build_query( $options ) : null;
			
			/* Build request URL. */
			$request_url = $this->api_url . $path . $request_options;
						
			/* Execute request based on method. */
			switch ( $method ) {
				
				case 'POST':
					$args = array(
						'body' => $options	
					);
					$response = wp_remote_post( $request_url, $args );
					break;
					
				case 'GET':
					$response = wp_remote_get( $request_url );
					break;
				
			}
						
			/* If WP_Error, die. Otherwise, return decoded JSON. */
			if ( is_wp_error( $response ) ) {
				
				die( 'Request failed. '. $response->get_error_messages() );
				
			} else {
				
				return json_decode( $response['body'], true );		
				
			}
			
		}
		
		/**
		 * Test authentication token.
		 * 
		 * @access public
		 * @return bool
		 */
		function auth_test() {
			
			return $this->make_request( 'auth.test' );
			
		}
		
		/**
		 * Get a channel.
		 * 
		 * @access public
		 * @param string $channel
		 * @return array
		 */
		function get_channel( $channel ) {
			
			return $this->make_request( 'channels.info', array( 'channel' => $channel ) );
			
		}
		
		/**
		 * Get all channels.
		 * 
		 * @access public
		 * @return void
		 */
		function get_channels() {
			
			return $this->make_request( 'channels.list' );
			
		}

		/**
		 * Get a group.
		 * 
		 * @access public
		 * @param string $group
		 * @return array
		 */
		function get_group( $group ) {
			
			return $this->make_request( 'groups.info', array( 'channel' => $group ) );
			
		}

		/**
		 * Get all groups.
		 * 
		 * @access public
		 * @return void
		 */
		function get_groups() {
			
			return $this->make_request( 'groups.list' );
			
		}

		/**
		 * Get a user.
		 * 
		 * @access public
		 * @return void
		 */
		function get_user( $user ) {
			
			return $this->make_request( 'users.info', array( 'user' => $user ) );
			
		}

		/**
		 * Get all users.
		 * 
		 * @access public
		 * @return void
		 */
		function get_users() {
			
			return $this->make_request( 'users.list' );
			
		}
		
		/**
		 * Open IM channel.
		 * 
		 * @access public
		 * @param mixed $user
		 * @return void
		 */
		function open_im( $user ) {
			
			return $this->make_request( 'users.info', array( 'user' => $user ), 'POST' );			
			
		}
		
		/**
		 * Post message to channel.
		 * 
		 * @access public
		 * @param array $message
		 * @return array
		 */
		function post_message( $message ) {
			
			return $this->make_request( 'chat.postMessage', $message, 'POST' );
			
		}
		
	}