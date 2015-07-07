<?php
	
GFForms::include_feed_addon_framework();

class GFSlack extends GFFeedAddOn {

	protected $_version = GF_SLACK_VERSION;
	protected $_min_gravityforms_version = '1.9.5.1';
	protected $_slug = 'gravityformsslack';
	protected $_path = 'gravityformsslack/slack.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'Gravity Forms Slack Add-On';
	protected $_short_title = 'Slack';

	// Members plugin integration
	protected $_capabilities = array( 'gravityforms_slack', 'gravityforms_slack_uninstall' );

	// Permissions
	protected $_capabilities_settings_page = 'gravityforms_slack';
	protected $_capabilities_form_settings = 'gravityforms_slack';
	protected $_capabilities_uninstall = 'gravityforms_slack_uninstall';
	protected $_enable_rg_autoupgrade = true;

	protected $api = null;
	private static $_instance = null;

	public static function get_instance() {
		
		if ( self::$_instance == null )
			self::$_instance = new GFSlack();

		return self::$_instance;
		
	}

	/* Settings Page */
	public function plugin_settings_fields() {

		return array(
			array(
				'title'       => '',
				'description' => $this->plugin_settings_description(),
				'fields'      => array(
					array(
						'name'              => 'auth_token',
						'label'             => __( 'Authentication Token', 'gravityformsslack' ),
						'type'              => 'text',
						'class'             => 'large',
						'feedback_callback' => array( $this, 'initialize_api' )
					),
					array(
						'type'              => 'save',
						'messages'          => array(
							'success' => __( 'Slack settings have been updated.', 'gravityformsslack' )
						),
					),
				),
			),
		);
		
	}

	/* Prepare plugin settings description */
	public function plugin_settings_description() {
		
		$description  = '<p>';
		$description .= sprintf(
			__( 'Slack provides simple group chat for your team. Use Gravity Forms to alert your Slack channels of a new form submission. If you don\'t have a Slack account, you can %1$s sign up for one here.%2$s', 'gravityformsslack' ),
			'<a href="https://www.slack.com/" target="_blank">', '</a>'
		);
		$description .= '</p>';
		
		if ( ! $this->initialize_api() ) {
			
			$description .= '<p>';
			$description .= sprintf(
				__( 'Gravity Forms Slack Add-On requires an API authentication token. You can find your authentication token by visiting the %1$sSlack Web API page%2$s while logged into your Slack account.', 'gravityformsslack' ),
				'<a href="https://api.slack.com/web" target="_blank">', '</a>'
			);
			$description .= '</p>';
			
		}
		
		return $description;
		
	}

	/* Setup feed settings fields */
	public function feed_settings_fields() {	        

		return array(
			array(
				'title' =>	'',
				'fields' =>	array(
					array(
						'name'           => 'feed_name',
						'label'          => __( 'Name', 'gravityformsslack' ),
						'type'           => 'text',
						'class'          => 'medium',
						'required'       => true,
						'tooltip'        => $this->tooltip_for_feed_setting( 'feed_name' )
					),
					array(
						'name'           => 'send_to',
						'label'          => __( 'Send To', 'gravityformsslack' ),
						'type'           => 'select',
						'required'       => true,
						'onchange'       => "jQuery(this).parents('form').submit();",
						'choices'        => $this->send_to_for_feed_setting(),
						'tooltip'        => $this->tooltip_for_feed_setting( 'send_to' )
					),
					array(
						'name'           => 'channel',
						'label'          => __( 'Slack Channel', 'gravityformsslack' ),
						'type'           => 'select',
						'required'       => true,
						'choices'        => $this->channels_for_feed_setting(),
						'tooltip'        => $this->tooltip_for_feed_setting( 'channel' ),
						'dependency'     => array( 'field' => 'send_to', 'values' => array( '', 'channel' ) ),
					),
					array(
						'name'           => 'group',
						'label'          => __( 'Slack Private Group', 'gravityformsslack' ),
						'type'           => 'select',
						'required'       => true,
						'choices'        => $this->groups_for_feed_setting(),
						'tooltip'        => $this->tooltip_for_feed_setting( 'group' ),
						'dependency'     => array( 'field' => 'send_to', 'values' => array( 'group' ) ),
					),
					array(
						'name'           => 'user',
						'label'          => __( 'Slack User', 'gravityformsslack' ),
						'type'           => 'select',
						'required'       => true,
						'choices'        => $this->users_for_feed_setting(),
						'tooltip'        => $this->tooltip_for_feed_setting( 'user' ),
						'dependency'     => array( 'field' => 'send_to', 'values' => array( 'user' ) ),
					),
					array(
						'name'           => 'message',
						'label'          => __( 'Message', 'gravityformsslack' ),
						'type'           => 'textarea',
						'required'       => true,
						'class'          => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
						'tooltip'        => $this->tooltip_for_feed_setting( 'message' ),
						'value'          => 'Entry #{entry_id} ({entry_url}) has been added.'
					),
					array(
						'name'           => 'feed_condition',
						'label'          => __( 'Conditional Logic', 'gravityformsslack' ),
						'type'           => 'feed_condition',
						'checkbox_label' => __( 'Enable', 'gravityformsslack' ),
						'instructions'   => __( 'Post to Slack if', 'gravityformsslack' ),
						'tooltip'        => $this->tooltip_for_feed_setting( 'feed_condition' )

					)
				)
			)
		);
	
	}
	
	/* Get needed feed tooltip */
	public function tooltip_for_feed_setting( $field ) {
		
		/* Setup tooltip array */
		$tooltips = array();
		
		/* Feed Name */
		$tooltips['feed_name']  = '<h6>'. __( 'Name', 'gravityformsslack' ) .'</h6>';
		$tooltips['feed_name'] .= __( 'Enter a feed name to uniquely identify this setup.', 'gravityformsslack' );
		
		/* Send To */
		$tooltips['send_to']  = '<h6>'. __( 'Send To', 'gravityformsslack' ) .'</h6>';
		$tooltips['send_to'] .= __( 'Select what type of channel Slack will send the message to: a public channel, a private group or an IM channel.', 'gravityformsslack' );

		/* Channel */
		$tooltips['channel']  = '<h6>'. __( 'Slack Channel', 'gravityformsslack' ) .'</h6>';
		$tooltips['channel'] .= __( 'Select which Slack channel this feed will post a message to.', 'gravityformsslack' );

		/* Private Group */
		$tooltips['group']  = '<h6>'. __( 'Slack Private Group', 'gravityformsslack' ) .'</h6>';
		$tooltips['group'] .= __( 'Select which Slack private group this feed will post a message to.', 'gravityformsslack' );

		/* User */
		$tooltips['user']  = '<h6>'. __( 'Slack User', 'gravityformsslack' ) .'</h6>';
		$tooltips['user'] .= __( 'Select which Slack user this feed will post a message to.', 'gravityformsslack' );
		
		/* Message */
		$tooltips['message']  = '<h6>'. __( 'Message', 'gravityformsslack' ) .'</h6>';
		$tooltips['message'] .= __( 'Enter the message that will be posted to the room.', 'gravityformsslack' ) . '<br /><br />';
		$tooltips['message'] .= '<strong>'. __( 'Available formatting:', 'gravityformsslack' ) .'</strong><br />';
		$tooltips['message'] .= '<strong>*asterisks*</strong> to create bold text<br />';
		$tooltips['message'] .= '<em>_underscores_</em> to italicize text<br /><br />';
		$tooltips['message'] .= '<strong>></strong> to indent a single line<br />';
		$tooltips['message'] .= '<strong>>>></strong> to indent multiple paragraphs<br /><br />';
		$tooltips['message'] .= '<strong>`single backticks`</strong> display as inline fixed-width text<br />';
		$tooltips['message'] .= '<strong>```triple backticks```</strong> create a block of pre-formatted, fixed-width text';
		
		/* Feed Condition */
		$tooltips['feed_condition']  = '<h6>'. __( 'Conditional Logic', 'gravityformsslack' ) .'</h6>';
		$tooltips['feed_condition'] .= __( 'When conditional logic is enabled, form submissions will only be posted to Slack when the condition is met. When disabled, all form submissions will be posted.', 'gravityformsslack' );
		
		/* Return desired tooltip */
		return $tooltips[$field];
		
	}

	/* Get Slack channel types for feed settings field */
	public function send_to_for_feed_setting() {
		
		return array(
			array(
				'label' => __( 'Public Channel', 'gravityformsslack' ),
				'value' => 'channel',	
			),
			array(
				'label' => __( 'Private Group', 'gravityformsslack' ),
				'value' => 'group',	
			),
			array(
				'label' => __( 'IM Channel to User', 'gravityformsslack' ),
				'value' => 'user',	
			),
		);
		
	}

	/* Get Slack channels for feed settings field */
	public function channels_for_feed_setting() {
		
		/* If Slack API instance is not initialized, return an empty array. */
		if ( ! $this->initialize_api() )
			return array();
		
		/* Setup choices array */
		$choices = array();
		
		/* Get the channels */
		$channels = $this->api->get_channels();
		
		/* Add lists to the choices array */
		if ( rgar( $channels, 'channels' ) && ! empty( $channels['channels'] ) ) {
			
			foreach ( $channels['channels'] as $channel ) {
				
				$choices[] = array(
					'label' => $channel['name'],
					'value' => $channel['id']
				);
				
			}
			
		}
		
		return $choices;
		
	}

	/* Get Slack groups for feed settings field */
	public function groups_for_feed_setting() {
		
		/* If Slack API instance is not initialized, return an empty array. */
		if ( ! $this->initialize_api() )
			return array();
		
		/* Setup choices array */
		$choices = array();
		
		/* Get the channels */
		$groups = $this->api->get_groups();
		
		/* Add lists to the choices array */
		if ( rgar( $groups, 'groups' ) && ! empty( $groups['groups'] ) ) {
			
			foreach ( $groups['groups'] as $group ) {
				
				$choices[] = array(
					'label' => $group['name'],
					'value' => $group['id']
				);
				
			}
			
		}
		
		return $choices;
		
	}

	/* Get Slack users for feed settings field */
	public function users_for_feed_setting() {
		
		/* If Slack API instance is not initialized, return an empty array. */
		if ( ! $this->initialize_api() )
			return array();
		
		/* Setup choices array */
		$choices = array();
		
		/* Get the channels */
		$users = $this->api->get_users();
		
		/* Add lists to the choices array */
		if ( rgar( $users, 'members' ) && ! empty( $users['members'] ) ) {
			
			foreach ( $users['members'] as $user ) {
				
				$choices[] = array(
					'label' => $user['name'],
					'value' => $user['id']
				);
				
			}
			
		}
		
		return $choices;
		
	}

	/* Hide "Add New" feed button if API credentials are invalid */		
	public function feed_list_title() {
		
		if ( $this->initialize_api() )
			return parent::feed_list_title();
			
		return sprintf( __( '%s Feeds', 'gravityforms' ), $this->get_short_title() );
		
	}

	/* Notify user to configure add-on before setting up feeds */
	public function feed_list_message() {

		$message = parent::feed_list_message();
		
		if ( $message !== false )
			return $message;

		if ( ! $this->initialize_api() )
			return $this->configure_addon_message();

		return false;
		
	}
	
	/* Feed list message for user to configure add-on */
	public function configure_addon_message() {
		
		$settings_label = sprintf( __( '%s Settings', 'gravityformsslack' ), $this->get_short_title() );
		$settings_link  = sprintf( '<a href="%s">%s</a>', esc_url( $this->get_plugin_settings_url() ), $settings_label );

		return sprintf( __( 'To get started, please configure your %s.', 'gravityformsslack' ), $settings_link );
		
	}

	/* Setup feed list columns */
	public function feed_list_columns() {
		
		return array(
			'feed_name' => __( 'Name', 'gravityformsslack' ),
			'send_to'   => __( 'Send To', 'gravityformsslack' )
		);
		
	}
	
	/* Change value of send to feed column to object name */
	public function get_column_value_send_to( $feed ) {
			
		/* If Slack instance is not initialized, return channel ID. */
		if ( ! $this->initialize_api() )
			return ucfirst( $feed['meta']['send_to'] );
		
		switch ( $feed['meta']['send_to'] ) {
			
			case 'group':
				$group = $this->api->get_group( $feed['meta']['group'] );		
				$destination = ( rgar( $group, 'group' ) ) ? $group['group']['name'] : $feed['meta']['group'];
				return sprintf( __( 'Private Group: %s', 'gravityformsslack' ), $destination );
				break;

			case 'user':
				$user = $this->api->get_user( $feed['meta']['user'] );		
				$destination = ( rgar( $user, 'user' ) ) ? $user['user']['name'] : $feed['meta']['user'];
				return sprintf( __( 'IM Channel to user: %s', 'gravityformsslack' ), $destination );
				break;

			default:
				$channel = $this->api->get_channel( $feed['meta']['channel'] );		
				$destination = ( rgar( $channel, 'channel' ) ) ? $channel['channel']['name'] : $feed['meta']['channel'];
				return sprintf( __( 'Public Channel: %s', 'gravityformsslack' ), $destination );
				break;
			
		}
		
	}

	/* Process feed */
	public function process_feed( $feed, $entry, $form ) {
		
		$this->log_debug( __METHOD__ . '(): Processing feed.' );
		
		/* If Slack instance is not initialized, exit. */
		if ( ! $this->initialize_api() ) {
			$this->log_error( __METHOD__ . '(): API not initialized; feed will not be processed.' );
			return;
		}
		
		/* Prepare notification array. */
		$message = array(
			'as_user'  => false,
			'icon_url' => apply_filters( 'gform_slack_icon', $this->get_base_url() . '/images/icon.png', $feed, $entry, $form ),
			'text'     => $feed['meta']['message'],
			'username' => 'Gravity Forms',
		);
		
		/* Add channel based on send_to. */
		switch ( $feed['meta']['send_to'] ) {
			
			case 'group':
				$message['channel'] = $feed['meta']['group'];
				break;

			case 'user':
				$message['channel'] = $this->open_im_channel( $feed['meta']['user'] );
				break;

			default:
				$message['channel'] = $feed['meta']['channel'];
				break;
			
		}
		
		/* Replace merge tags on notification message. */
		$message['text'] = GFCommon::replace_variables( $message['text'], $form, $entry, false, true, false, 'text' );
		
		/* If message is empty, exit. */
		if ( rgblank( $message['text'] ) ) {
			
			$this->log_error( __METHOD__ . "(): Notification message is empty." );
			return;
			
		}

		/* Post message to channel. */
		$message_channel = $this->api->post_message( $message );
		
		if ( rgar( $message_channel, 'ok' ) ) {
			
			$this->log_debug( __METHOD__ . "(): Message was posted to channel." );	
					
		} else {
			
			$this->log_error( __METHOD__ . "(): Message was not posted to channel." );
			
		}
									
	}

	/* Open an IM channel to send a message to. */
	public function open_im_channel( $user ) {
		
		if ( ! $this->initialize_api() )
			return null;
		
		$im_channel = $this->api->open_im( $user );
		
		return ( rgar( $im_channel, 'channel' ) ) ? $im_channel['channel']['id'] : $user;
		
	}

	/* Checks validity of Slack API credentials and initializes API if valid. */
	public function initialize_api() {
		
		if ( ! is_null( $this->api ) )
			return true;

		/* Load the API library. */
		require_once( 'includes/class-slack.php' );

		/* Get the OAuth token. */
		$auth_token = $this->get_plugin_setting( 'auth_token' );
		
		/* If the OAuth token, do not run a validation check. */
		if ( rgblank( $auth_token ) )
			return null;
		
		$this->log_debug( __METHOD__ . "(): Validating login for API Info for {$auth_token}." );

		/* Setup a new Slack object with the API credentials. */
		$slack = new Slack( $auth_token );
		
		/* Run an authentication test. */
		$auth_test = $slack->auth_test();
		
		if ( rgar( $auth_test, 'ok' ) ) {
		
			$this->api = $slack;
			$this->log_debug( __METHOD__ . '(): API credentials are valid.' );
			return true;
			
		} else {
			
			$this->log_error( __METHOD__ . '(): API credentials are invalid; '. $auth_test['error'] );
			return false;			
			
		}
		
	}

}