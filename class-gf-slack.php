<?php
	
GFForms::include_feed_addon_framework();

class GFSlack extends GFFeedAddOn {

	protected $_version = GF_SLACK_VERSION;
	protected $_min_gravityforms_version = '1.9.12';
	protected $_slug = 'gravityformsslack';
	protected $_path = 'gravityformsslack/slack.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'Gravity Forms Slack Add-On';
	protected $_short_title = 'Slack';
	protected $_enable_rg_autoupgrade = true;
	protected $api = null;
	private static $_instance = null;

	/* Permissions */
	protected $_capabilities_settings_page = 'gravityforms_slack';
	protected $_capabilities_form_settings = 'gravityforms_slack';
	protected $_capabilities_uninstall = 'gravityforms_slack_uninstall';

	/* Members plugin integration */
	protected $_capabilities = array( 'gravityforms_slack', 'gravityforms_slack_uninstall' );

	/**
	 * Get instance of this class.
	 * 
	 * @access public
	 * @static
	 * @return $_instance
	 */
	public static function get_instance() {
		
		if ( self::$_instance == null ) {
			self::$_instance = new self;
		}

		return self::$_instance;
		
	}

	/**
	 * Plugin starting point. Adds PayPal delayed payment support.
	 * 
	 * @access public
	 * @return void
	 */
	public function init() {
		
		parent::init();
		
		$this->add_delayed_payment_support(
			array(
				'option_label' => esc_html__( 'Send message to Slack only when payment is received.', 'gravityformsslack' )
			)
		);
		
	}

	/**
	 * Setup plugin settings fields.
	 * 
	 * @access public
	 * @return array
	 */
	public function plugin_settings_fields() {

		return array(
			array(
				'title'       => '',
				'description' => $this->plugin_settings_description(),
				'fields'      => array(
					array(
						'name'              => 'auth_token',
						'label'             => esc_html__( 'Authentication Token', 'gravityformsslack' ),
						'type'              => 'text',
						'class'             => 'large',
						'feedback_callback' => array( $this, 'initialize_api' )
					),
					array(
						'type'              => 'save',
						'messages'          => array(
							'success' => esc_html__( 'Slack settings have been updated.', 'gravityformsslack' )
						),
					),
				),
			),
		);
		
	}

	/**
	 * Prepare plugin settings description.
	 * 
	 * @access public
	 * @return string $description
	 */
	public function plugin_settings_description() {
		
		$description  = '<p>';
		$description .= sprintf(
			esc_html__( 'Slack provides simple group chat for your team. Use Gravity Forms to alert your Slack channels of a new form submission. If you don\'t have a Slack account, you can %1$s sign up for one here.%2$s', 'gravityformsslack' ),
			'<a href="https://www.slack.com/" target="_blank">', '</a>'
		);
		$description .= '</p>';
		
		if ( ! $this->initialize_api() ) {
			
			$description .= '<p>';
			$description .= sprintf(
				esc_html__( 'Gravity Forms Slack Add-On requires an API authentication token. You can find your authentication token by visiting the %1$sSlack Web API page%2$s while logged into your Slack account.', 'gravityformsslack' ),
				'<a href="https://api.slack.com/web" target="_blank">', '</a>'
			);
			$description .= '</p>';
			
		}
		
		return $description;
		
	}

	/**
	 * Setup fields for feed settings.
	 * 
	 * @access public
	 * @return array $settings
	 */
	public function feed_settings_fields() {	        

		$settings = array(
			array(
				'title' =>	'',
				'fields' =>	array(
					array(
						'name'           => 'feed_name',
						'label'          => esc_html__( 'Name', 'gravityformsslack' ),
						'type'           => 'text',
						'class'          => 'medium',
						'required'       => true,
						'tooltip'        => $this->tooltip_for_feed_setting( 'feed_name' )
					),
					array(
						'name'           => 'send_to',
						'label'          => esc_html__( 'Send To', 'gravityformsslack' ),
						'type'           => 'select',
						'required'       => true,
						'onchange'       => "jQuery(this).parents('form').submit();",
						'choices'        => $this->send_to_for_feed_setting(),
						'tooltip'        => $this->tooltip_for_feed_setting( 'send_to' )
					),
					array(
						'name'           => 'channel',
						'label'          => esc_html__( 'Slack Channel', 'gravityformsslack' ),
						'type'           => 'select',
						'required'       => true,
						'choices'        => $this->channels_for_feed_setting(),
						'tooltip'        => $this->tooltip_for_feed_setting( 'channel' ),
						'dependency'     => array( 'field' => 'send_to', 'values' => array( '', 'channel' ) ),
					),
					array(
						'name'           => 'group',
						'label'          => esc_html__( 'Slack Private Group', 'gravityformsslack' ),
						'type'           => 'select',
						'required'       => true,
						'choices'        => $this->groups_for_feed_setting(),
						'tooltip'        => $this->tooltip_for_feed_setting( 'group' ),
						'dependency'     => array( 'field' => 'send_to', 'values' => array( 'group' ) ),
					),
					array(
						'name'           => 'user',
						'label'          => esc_html__( 'Slack User', 'gravityformsslack' ),
						'type'           => 'select',
						'required'       => true,
						'choices'        => $this->users_for_feed_setting(),
						'tooltip'        => $this->tooltip_for_feed_setting( 'user' ),
						'dependency'     => array( 'field' => 'send_to', 'values' => array( 'user' ) ),
					),
					array(
						'name'           => 'message',
						'label'          => esc_html__( 'Message', 'gravityformsslack' ),
						'type'           => 'textarea',
						'required'       => true,
						'class'          => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
						'tooltip'        => $this->tooltip_for_feed_setting( 'message' ),
						'value'          => 'Entry #{entry_id} ({entry_url}) has been added.'
					),
				)
			)
		);
		
		$fileupload_fields_for_feed = $this->fileupload_fields_for_feed_setting();

		if ( ! empty ( $fileupload_fields_for_feed ) ) {

			$settings[0]['fields'][] = array(
				'name'    => 'attachments',
				'type'    => 'checkbox',
				'label'   => __( 'Image Attachments', 'gravityformsslack' ),
				'choices' => $fileupload_fields_for_feed,
				'tooltip' => $this->tooltip_for_feed_setting( 'attachments' ),
			);

		}

		$settings[0]['fields'][] = array(
			'name'           => 'feed_condition',
			'label'          => esc_html__( 'Conditional Logic', 'gravityformsslack' ),
			'type'           => 'feed_condition',
			'checkbox_label' => esc_html__( 'Enable', 'gravityformsslack' ),
			'instructions'   => esc_html__( 'Post to Slack if', 'gravityformsslack' ),
			'tooltip'        => $this->tooltip_for_feed_setting( 'feed_condition' )
		);

		
		return $settings;
	
	}
	
	/**
	 * Get feed tooltip.
	 * 
	 * @access public
	 * @param array $field
	 * @return string
	 */
	public function tooltip_for_feed_setting( $field ) {
		
		/* Setup tooltip array */
		$tooltips = array();
		
		/* Feed Name */
		$tooltips['feed_name']  = '<h6>'. esc_html__( 'Name', 'gravityformsslack' ) .'</h6>';
		$tooltips['feed_name'] .= esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformsslack' );
		
		/* Send To */
		$tooltips['send_to']  = '<h6>'. esc_html__( 'Send To', 'gravityformsslack' ) .'</h6>';
		$tooltips['send_to'] .= esc_html__( 'Select what type of channel Slack will send the message to: a public channel, a private group or an IM channel.', 'gravityformsslack' );

		/* Channel */
		$tooltips['channel']  = '<h6>'. esc_html__( 'Slack Channel', 'gravityformsslack' ) .'</h6>';
		$tooltips['channel'] .= esc_html__( 'Select which Slack channel this feed will post a message to.', 'gravityformsslack' );

		/* Private Group */
		$tooltips['group']  = '<h6>'. esc_html__( 'Slack Private Group', 'gravityformsslack' ) .'</h6>';
		$tooltips['group'] .= esc_html__( 'Select which Slack private group this feed will post a message to.', 'gravityformsslack' );

		/* User */
		$tooltips['user']  = '<h6>'. esc_html__( 'Slack User', 'gravityformsslack' ) .'</h6>';
		$tooltips['user'] .= esc_html__( 'Select which Slack user this feed will post a message to.', 'gravityformsslack' );
		
		/* Message */
		$tooltips['message']  = '<h6>'. __( 'Message', 'gravityformsslack' ) .'</h6>';
		$tooltips['message'] .= esc_html__( 'Enter the message that will be posted to the room.', 'gravityformsslack' ) . '<br /><br />';
		$tooltips['message'] .= '<strong>'. __( 'Available formatting:', 'gravityformsslack' ) .'</strong><br />';
		$tooltips['message'] .= '<strong>*asterisks*</strong> to create bold text<br />';
		$tooltips['message'] .= '<em>_underscores_</em> to italicize text<br /><br />';
		$tooltips['message'] .= '<strong>></strong> to indent a single line<br />';
		$tooltips['message'] .= '<strong>>>></strong> to indent multiple paragraphs<br /><br />';
		$tooltips['message'] .= '<strong>`single backticks`</strong> display as inline fixed-width text<br />';
		$tooltips['message'] .= '<strong>```triple backticks```</strong> create a block of pre-formatted, fixed-width text';
		
		/* Image Attachment */
		$tooltips['attachments']  = '<h6>'. esc_html__( 'Image Attachments', 'gravityformsslack' ) .'</h6>';
		$tooltips['attachments'] .= esc_html__( 'Select which file upload fields will be attached to the Slack message. Only image files will be attached.', 'gravityformsslack' );

		/* Feed Condition */
		$tooltips['feed_condition']  = '<h6>'. esc_html__( 'Conditional Logic', 'gravityformsslack' ) .'</h6>';
		$tooltips['feed_condition'] .= esc_html__( 'When conditional logic is enabled, form submissions will only be posted to Slack when the condition is met. When disabled, all form submissions will be posted.', 'gravityformsslack' );
		
		/* Return desired tooltip */
		return $tooltips[ $field ];
		
	}

	/**
	 * Get Slack channel types for feed settings field.
	 * 
	 * @access public
	 * @return array
	 */
	public function send_to_for_feed_setting() {
		
		return array(
			array(
				'label' => esc_html__( 'Public Channel', 'gravityformsslack' ),
				'value' => 'channel',	
			),
			array(
				'label' => esc_html__( 'Private Group', 'gravityformsslack' ),
				'value' => 'group',	
			),
			array(
				'label' => esc_html__( 'IM Channel to User', 'gravityformsslack' ),
				'value' => 'user',	
			),
		);
		
	}

	/**
	 * Get Slack channels for feed settings field.
	 * 
	 * @access public
	 * @return array $choices
	 */
	public function channels_for_feed_setting() {
		
		/* If Slack API instance is not initialized, return an empty array. */
		if ( ! $this->initialize_api() ) {
			return array();
		}
		
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

	/**
	 * Get Slack groups for feed settings field.
	 * 
	 * @access public
	 * @return array $choices
	 */
	public function groups_for_feed_setting() {
		
		/* If Slack API instance is not initialized, return an empty array. */
		if ( ! $this->initialize_api() ) {
			return array();
		}
		
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

	/**
	 * Get Slack users for feed settings field.
	 * 
	 * @access public
	 * @return array $choices
	 */
	public function users_for_feed_setting() {
		
		/* If Slack API instance is not initialized, return an empty array. */
		if ( ! $this->initialize_api() ) {
			return array();
		}
		
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

	/**
	 * Get file upload fields for feed settings field.
	 * 
	 * @access public
	 * @return array $choices
	 */
	public function fileupload_fields_for_feed_setting() {

		/* Setup choices array. */
		$choices = array();

		/* Get the form file fields. */
		$form       = GFAPI::get_form( rgget( 'id' ) );
		$file_fields = GFCommon::get_fields_by_type( $form, array( 'fileupload' ) );

		if ( ! empty ( $file_fields ) ) {

			foreach ( $file_fields as $field ) {

				$choices[] = array(
					'name'          => 'attachments[' . $field->id . ']',
					'label'         => $field->label,
					'default_value' => 0,
				);

			}

		}

		return $choices;

	}

	/**
	 * Set feed creation control.
	 * 
	 * @access public
	 * @return bool
	 */
	public function can_create_feed() {
		
		return $this->initialize_api();
		
	}

	/**
	 * Setup columns for feed list table.
	 * 
	 * @access public
	 * @return array
	 */
	public function feed_list_columns() {
		
		return array(
			'feed_name' => esc_html__( 'Name', 'gravityformsslack' ),
			'send_to'   => esc_html__( 'Send To', 'gravityformsslack' )
		);
		
	}
	
	/**
	 * Get value for send to feed list column.
	 * 
	 * @access public
	 * @param array $feed
	 * @return string
	 */
	public function get_column_value_send_to( $feed ) {
			
		/* If Slack instance is not initialized, return channel ID. */
		if ( ! $this->initialize_api() ) {
			return ucfirst( $feed['meta']['send_to'] );
		}
		
		switch ( $feed['meta']['send_to'] ) {
			
			case 'group':
				$group = $this->api->get_group( $feed['meta']['group'] );		
				$destination = ( rgar( $group, 'group' ) ) ? $group['group']['name'] : $feed['meta']['group'];
				return sprintf( esc_html__( 'Private Group: %s', 'gravityformsslack' ), $destination );
				break;

			case 'user':
				$user = $this->api->get_user( $feed['meta']['user'] );		
				$destination = ( rgar( $user, 'user' ) ) ? $user['user']['name'] : $feed['meta']['user'];
				return sprintf( esc_html__( 'IM Channel to user: %s', 'gravityformsslack' ), $destination );
				break;

			default:
				$channel = $this->api->get_channel( $feed['meta']['channel'] );		
				$destination = ( rgar( $channel, 'channel' ) ) ? $channel['channel']['name'] : $feed['meta']['channel'];
				return sprintf( esc_html__( 'Public Channel: %s', 'gravityformsslack' ), $destination );
				break;
			
		}
		
	}

	/**
	 * Process feed.
	 * 
	 * @access public
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @return void
	 */
	public function process_feed( $feed, $entry, $form ) {
		
		$this->log_debug( __METHOD__ . '(): Processing feed.' );
		
		/* If Slack instance is not initialized, exit. */
		if ( ! $this->initialize_api() ) {
			$this->add_feed_error( esc_html__( 'Feed was not processed because API was not initialized.', 'gravityformsslack' ), $feed, $entry, $form );
			return;
		}
		
		/* Prepare notification array. */
		$message = array(
			'as_user'  => false,
			'icon_url' => apply_filters( 'gform_slack_icon', $this->get_base_url() . '/images/icon.png', $feed, $entry, $form ),
			'text'     => $feed['meta']['message'],
			'username' => gf_apply_filters( 'gform_slack_username', array( $form['id'] ), 'Gravity Forms', $feed, $entry, $form ),
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
		$message['text'] = GFCommon::replace_variables( $message['text'], $form, $entry, false, false, false, 'text' );
		
		/* If message is empty, exit. */
		if ( rgblank( $message['text'] ) ) {
			
			$this->log_error( __METHOD__ . "(): Notification message is empty." );
			return;
			
		}

		/* Prepare attachments. */
		$message = $this->prepare_attachments( $message, $feed, $entry, $form );

		/* Post message to channel. */
		$this->log_debug( __METHOD__ . '(): Posting message: ' . print_r( $message, true ) );
		$message_channel = $this->api->post_message( $message );
		
		if ( rgar( $message_channel, 'ok' ) ) {
			$this->log_debug( __METHOD__ . "(): Message was posted to channel." );	
		} else {
			$this->add_feed_error( esc_html__( 'Message was not posted to channel.', 'gravityformsslack' ), $feed, $entry, $form );
		}
									
	}

	/**
	 * Prepare attachments for Slack message.
	 * 
	 * @access public
	 * @param array $message
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @return array $message
	 */
	public function prepare_attachments( $message, $feed, $entry, $form ) {
		
		$files = array();
		
		/* Get files for the selected fields. */
		if ( ! empty( $feed['meta']['attachments'] ) ) {
			foreach ( $feed['meta']['attachments'] as $field => $enabled ) {

				/* If this field is not enabled for this feed, skip it. */
				if ( $enabled == '0' ) {
					continue;
				}

				/* Get the field value. */
				$field_value = $this->get_field_value( $form, $entry, $field );

				/* If no files were uploaded for this field, move on. */
				if ( rgblank( $field_value ) ) {
					continue;
				}

				$field_value = explode( ',', $field_value );
				$field_value = array_map( 'trim', $field_value );

				$files = array_merge( $files, $field_value );

			}
		}
		
		/* If no files were uploaded, return the message. */
		if ( empty( $files ) ) {
			return $message;
		}
		
		/* Add images to attachments array. */
		foreach ( $files as $file ) {
			
			/* Get file path. */
			$file_path = str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $file );
		
			/* Check if first file is an image. */
			$file_details = wp_check_filetype_and_ext( $file_path, basename( $file_path ) );
			if ( strpos( $file_details['type'], 'image') === false ) {
				continue;
			}
		
			/* Attach image to message. */
			$message['attachments'][] = array(
				'fallback'  => $message['text'],
				'image_url' => $file,
				'text'      => basename( $file_path ),
			);
			
		}
		
		/* Convert attachments to JSON string. */
		if ( rgar( $message, 'attachments' ) ) {
			$message['attachments'] = json_encode( $message['attachments'] );
		}
		
		/* Return message object. */
		return $message;
		
	}

	/**
	 * Open an IM channel with user.
	 * 
	 * @access public
	 * @param int $user
	 * @return int $channel_id
	 */
	public function open_im_channel( $user ) {
		
		if ( ! $this->initialize_api() ) {
			return null;
		}
		
		$im_channel = $this->api->open_im( $user );
		
		return rgar( $im_channel, 'channel' ) ? $im_channel['channel']['id'] : $user;
		
	}

	/**
	 * Initializes Slack API if credentials are valid.
	 * 
	 * @access public
	 * @return bool
	 */
	public function initialize_api() {
		
		if ( ! is_null( $this->api ) )
			return true;

		/* Load the API library. */
		if ( ! class_exists( 'Slack' ) ) {
			require_once( 'includes/class-slack.php' );
		}

		/* Get the OAuth token. */
		$auth_token = $this->get_plugin_setting( 'auth_token' );
		
		/* If the OAuth token, do not run a validation check. */
		if ( rgblank( $auth_token ) )
			return null;
		
		$this->log_debug( __METHOD__ . "(): Validating API Info." );

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
