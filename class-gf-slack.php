<?php

GFForms::include_feed_addon_framework();

/**
 * Gravity Forms Slack Add-On.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2016, Rocketgenius
 */
class GFSlack extends GFFeedAddOn {

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @access private
	 * @var    object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the Slack Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_version Contains the version, defined from slack.php
	 */
	protected $_version = GF_SLACK_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '1.9.14.26';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformsslack';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformsslack/slack.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string The URL of the Add-On.
	 */
	protected $_url = 'http://www.gravityforms.com';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_title The title of the Add-On.
	 */
	protected $_title = 'Gravity Forms Slack Add-On';

	/**
	 * Defines the short title of the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_short_title The short title.
	 */
	protected $_short_title = 'Slack';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    bool
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_slack';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_slack';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_slack_uninstall';

	/**
	 * Defines the capabilities needed for the Post Creation Add-On
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array( 'gravityforms_slack', 'gravityforms_slack_uninstall' );

	/**
	 * Contains an instance of the Slack API library, if available.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    object $api If available, contains an instance of the Slack API library.
	 */
	protected $api = null;

	/**
	 * Get instance of this class.
	 *
	 * @access public
	 * @static
	 * @return GFSlack
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
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
				'option_label' => esc_html__( 'Send message to Slack only when payment is received.', 'gravityformsslack' ),
			)
		);

	}





	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Setup plugin settings fields.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {

		return array(
			array(
				'description' => $this->plugin_settings_description(),
				'fields'      => array(
					array(
						'name'              => 'auth_token',
						'label'             => esc_html__( 'Authentication Token', 'gravityformsslack' ),
						'type'              => 'text',
						'class'             => 'large',
						'feedback_callback' => array( $this, 'initialize_api' ),
					),
					array(
						'name'              => 'team_name',
						'label'             => esc_html__( 'Team Name', 'gravityformsslack' ),
						'type'              => 'text',
						'class'             => 'small',
						'after_input'       => '.slack.com',
						'readonly'          => true,
						'save_callback'     => array( $this, 'save_team_name' ),
					),
					array(
						'type'              => 'save',
						'messages'          => array(
							'success' => esc_html__( 'Slack settings have been updated.', 'gravityformsslack' ),
						),
					),
				),
			),
		);

	}

	/**
	 * Prepare plugin settings description.
	 *
	 * @since  1.0
	 * @access public
	 *
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
	 * Get and save team name when saving plugin settings.
	 *
	 * @since  1.4.1
	 * @access public
	 * @param  array  $field The field being saved.
	 * @param  string $field_setting The field value.
	 *
	 * @return string
	 */
	public function save_team_name( $field, $field_setting ) {

		// Get posted settings.
		$settings = $this->get_posted_settings();

		// If API is initialized, get team info and save domain.
		if ( ! rgblank( $settings['auth_token'] ) && true === $this->initialize_api( $settings['auth_token'] ) ) {

			// Get team info.
			$team_info = $this->api->get_team_info();

			// Set field setting to domain.
			$field_setting = rgars( $team_info, 'team/domain' );

		} else {

			// Set field setting to null.
			$field_setting = '';

		}

		// Set posted setting.
		$_gaddon_posted_settings['team_name'] = $field_setting;

		// Return field setting.
		return $field_setting;

	}



	// # FEED SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Setup fields for feed settings.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array $settings
	 */
	public function feed_settings_fields() {

		$settings = array(
			array(
				'fields' => array(
					array(
						'name'           => 'feed_name',
						'label'          => esc_html__( 'Name', 'gravityformsslack' ),
						'type'           => 'text',
						'class'          => 'medium',
						'required'       => true,
						'tooltip'        => $this->tooltip_for_feed_setting( 'feed_name' ),
						'default_value'  => $this->get_default_feed_name(),
					),
					array(
						'name'           => 'action',
						'label'          => esc_html__( 'Action', 'gravityformsslack' ),
						'type'           => 'radio',
						'required'       => true,
						'onchange'       => "jQuery(this).parents('form').submit();",
						'choices'        => $this->action_for_feed_setting(),
					),
					array(
						'name'           => 'email',
						'label'          => esc_html__( 'Email Address', 'gravityformsslack' ),
						'type'           => 'field_select',
						'required'       => true,
						'tooltip'        => $this->tooltip_for_feed_setting( 'email' ),
						'dependency'     => array( 'field' => 'action', 'values' => array( 'invite' ) ),
						'args'           => array( 'input_types' => array( 'email' ) ),
					),
					array(
						'name'           => 'send_to',
						'label'          => esc_html__( 'Send To', 'gravityformsslack' ),
						'type'           => 'radio',
						'required'       => true,
						'onchange'       => "jQuery(this).parents('form').submit();",
						'choices'        => $this->send_to_for_feed_setting(),
						'tooltip'        => $this->tooltip_for_feed_setting( 'send_to' ),
						'dependency'     => array( 'field' => 'action', 'values' => array( 'message' ) ),
					),
					array(
						'name'           => 'channel',
						'label'          => esc_html__( 'Slack Channel', 'gravityformsslack' ),
						'type'           => 'select',
						'required'       => true,
						'choices'        => $this->channels_for_feed_setting(),
						'tooltip'        => $this->tooltip_for_feed_setting( 'channel' ),
						'dependency'     => array( 'field' => 'send_to', 'values' => array( 'channel' ) ),
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
						'value'          => 'Entry #{entry_id} ({entry_url}) has been added.',
						'dependency'     => array( 'field' => 'send_to', 'values' => array( '_notempty_' ) ),
					),
					array(
						'name'           => 'feedCondition',
						'label'          => esc_html__( 'Conditional Logic', 'gravityformsslack' ),
						'type'           => 'feed_condition',
						'checkbox_label' => esc_html__( 'Enable', 'gravityformsslack' ),
						'instructions'   => esc_html__( 'Export to Slack if', 'gravityformsslack' ),
						'tooltip'        => '<h6>'. esc_html__( 'Conditional Logic', 'gravityformsslack' ) .'</h6>' . __( 'When conditional logic is enabled, form submissions will only be exported to Slack when the condition is met. When disabled, all form submissions will be posted.', 'gravityformsslack' ),
						'dependency'     => array( 'field' => 'action', 'values' => array( '_notempty_' ) ),
					),
				),
			),
		);

		$fileupload_fields_for_feed = $this->fileupload_fields_for_feed_setting();

		if ( ! empty( $fileupload_fields_for_feed ) ) {

			$settings[0]['fields'][] = array(
				'name'       => 'attachments',
				'type'       => 'checkbox',
				'label'      => __( 'Image Attachments', 'gravityformsslack' ),
				'choices'    => $fileupload_fields_for_feed,
				'tooltip'    => $this->tooltip_for_feed_setting( 'attachments' ),
				'dependency' => array( 'field' => 'send_to', 'values' => array( '_notempty_' ) ),
			);

		}

		$settings[0]['fields'][] = array(
			'name'           => 'feed_condition',
			'label'          => esc_html__( 'Conditional Logic', 'gravityformsslack' ),
			'type'           => 'feed_condition',
			'checkbox_label' => esc_html__( 'Enable', 'gravityformsslack' ),
			'instructions'   => esc_html__( 'Post to Slack if', 'gravityformsslack' ),
			'tooltip'        => $this->tooltip_for_feed_setting( 'feed_condition' ),
			'dependency'     => array( 'field' => 'send_to', 'values' => array( '_notempty_' ) ),
		);

		return $settings;

	}

	/**
	 * Get feed tooltip.
	 *
	 * @since  1.0
	 * @access public
	 * @param  array $field Field name.
	 *
	 * @return string
	 */
	public function tooltip_for_feed_setting( $field ) {

		// Setup tooltip array.
		$tooltips = array();

		// Feed Name.
		$tooltips['feed_name']  = '<h6>'. esc_html__( 'Name', 'gravityformsslack' ) .'</h6>';
		$tooltips['feed_name'] .= esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformsslack' );

		// Send To.
		$tooltips['send_to']  = '<h6>'. esc_html__( 'Send To', 'gravityformsslack' ) .'</h6>';
		$tooltips['send_to'] .= esc_html__( 'Select what type of channel Slack will send the message to: a public channel, a private group or an IM channel.', 'gravityformsslack' );

		// Email Address.
		$tooltips['email']  = '<h6>'. esc_html__( 'Email Address', 'gravityformsslack' ) .'</h6>';
		$tooltips['email'] .= esc_html__( 'Select what email field will be used to send the Slack invite.', 'gravityformsslack' );

		// Channel.
		$tooltips['channel']  = '<h6>'. esc_html__( 'Slack Channel', 'gravityformsslack' ) .'</h6>';
		$tooltips['channel'] .= esc_html__( 'Select which Slack channel this feed will post a message to.', 'gravityformsslack' );

		// Private Group.
		$tooltips['group']  = '<h6>'. esc_html__( 'Slack Private Group', 'gravityformsslack' ) .'</h6>';
		$tooltips['group'] .= esc_html__( 'Select which Slack private group this feed will post a message to.', 'gravityformsslack' );

		// User.
		$tooltips['user']  = '<h6>'. esc_html__( 'Slack User', 'gravityformsslack' ) .'</h6>';
		$tooltips['user'] .= esc_html__( 'Select which Slack user this feed will post a message to.', 'gravityformsslack' );

		// Message.
		$tooltips['message']  = '<h6>'. __( 'Message', 'gravityformsslack' ) .'</h6>';
		$tooltips['message'] .= esc_html__( 'Enter the message that will be posted to the room.', 'gravityformsslack' ) . '<br /><br />';
		$tooltips['message'] .= '<strong>'. __( 'Available formatting:', 'gravityformsslack' ) .'</strong><br />';
		$tooltips['message'] .= '<strong>*asterisks*</strong> to create bold text<br />';
		$tooltips['message'] .= '<em>_underscores_</em> to italicize text<br /><br />';
		$tooltips['message'] .= '<strong>></strong> to indent a single line<br />';
		$tooltips['message'] .= '<strong>>>></strong> to indent multiple paragraphs<br /><br />';
		$tooltips['message'] .= '<strong>`single backticks`</strong> display as inline fixed-width text<br />';
		$tooltips['message'] .= '<strong>```triple backticks```</strong> create a block of pre-formatted, fixed-width text';

		// Image Attachment.
		$tooltips['attachments']  = '<h6>'. esc_html__( 'Image Attachments', 'gravityformsslack' ) .'</h6>';
		$tooltips['attachments'] .= esc_html__( 'Select which file upload fields will be attached to the Slack message. Only image files will be attached.', 'gravityformsslack' );

		// Feed Condition.
		$tooltips['feed_condition']  = '<h6>'. esc_html__( 'Conditional Logic', 'gravityformsslack' ) .'</h6>';
		$tooltips['feed_condition'] .= esc_html__( 'When conditional logic is enabled, form submissions will only be posted to Slack when the condition is met. When disabled, all form submissions will be posted.', 'gravityformsslack' );

		// Return desired tooltip.
		return $tooltips[ $field ];

	}

	/**
	 * Get Slack action types for feed settings field.
	 *
	 * @since  1.4
	 * @access public
	 *
	 * @return array
	 */
	public function action_for_feed_setting() {

		// Initialize actions.
		$actions = array(
			array(
				'label' => esc_html__( 'Send Message', 'gravityformsslack' ),
				'value' => 'message',
				'icon'  => 'fa-comment',
			),
		);

		// If team name is defined, add invite action.
		if ( $this->get_plugin_setting( 'team_name' ) ) {
			$actions[] = array(
				'label' => esc_html__( 'Invite to Team', 'gravityformsslack' ),
				'value' => 'invite',
				'icon'  => 'fa-users',
			);
		}

		return $actions;

	}

	/**
	 * Get Slack channel types for feed settings field.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function send_to_for_feed_setting() {

		return array(
			array(
				'label' => esc_html__( 'Public Channel', 'gravityformsslack' ),
				'value' => 'channel',
				'icon'  => 'fa-hashtag',
			),
			array(
				'label' => esc_html__( 'Private Group', 'gravityformsslack' ),
				'value' => 'group',
				'icon'  => 'fa-users',
			),
			array(
				'label' => esc_html__( 'IM Channel', 'gravityformsslack' ),
				'value' => 'user',
				'icon'  => 'fa-user-secret',
			),
		);

	}

	/**
	 * Get Slack channels for feed settings field.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array $choices
	 */
	public function channels_for_feed_setting() {

		// Setup choices array.
		$choices = array(
			array(
				'label' => esc_html__( 'Select a Channel', 'gravityformsslack' ),
				'value' => '',
			),
		);

		// If Slack API instance is not initialized, return choices.
		if ( ! $this->initialize_api() ) {
			return $choices;
		}

		// Get the channels.
		$channels = $this->api->get_channels();

		// Add lists to the choices array.
		if ( rgar( $channels, 'channels' ) && ! empty( $channels['channels'] ) ) {

			foreach ( $channels['channels'] as $channel ) {

				$choices[] = array(
					'label' => $channel['name'],
					'value' => $channel['id'],
				);

			}

		}

		return $choices;

	}

	/**
	 * Get Slack groups for feed settings field.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array $choices
	 */
	public function groups_for_feed_setting() {

		// Setup choices array.
		$choices = array(
			array(
				'label' => esc_html__( 'Select a Group', 'gravityformsslack' ),
				'value' => '',
			),
		);

		// If Slack API instance is not initialized, return choices.
		if ( ! $this->initialize_api() ) {
			return $choices;
		}

		// Get the channels.
		$groups = $this->api->get_groups();

		// Add lists to the choices array.
		if ( rgar( $groups, 'groups' ) && ! empty( $groups['groups'] ) ) {

			foreach ( $groups['groups'] as $group ) {

				$choices[] = array(
					'label' => $group['name'],
					'value' => $group['id'],
				);

			}

		}

		return $choices;

	}

	/**
	 * Get Slack users for feed settings field.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array $choices
	 */
	public function users_for_feed_setting() {

		// Setup choices array.
		$choices = array(
			array(
				'label' => esc_html__( 'Select a User', 'gravityformsslack' ),
				'value' => '',
			),
		);

		// If Slack API instance is not initialized, return choices.
		if ( ! $this->initialize_api() ) {
			return $choices;
		}

		// Setup choices array.
		$choices = array();

		// Get the channels.
		$users = $this->api->get_users();

		// Add lists to the choices array.
		if ( rgar( $users, 'members' ) && ! empty( $users['members'] ) ) {

			foreach ( $users['members'] as $user ) {

				$choices[] = array(
					'label' => $user['name'],
					'value' => $user['id'],
				);

			}

		}

		return $choices;

	}

	/**
	 * Get file upload fields for feed settings field.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array $choices
	 */
	public function fileupload_fields_for_feed_setting() {

		// Setup choices array.
		$choices = array();

		// Get the form file fields.
		$form        = GFAPI::get_form( rgget( 'id' ) );
		$file_fields = GFCommon::get_fields_by_type( $form, array( 'fileupload' ) );

		if ( ! empty( $file_fields ) ) {

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
	 * @since  1.0
	 * @access public
	 *
	 * @return bool
	 */
	public function can_create_feed() {

		return $this->initialize_api();

	}

	/**
	 * Enable feed duplication.
	 *
	 * @since  1.0
	 * @access public
	 * @param  int $feed_id Feed ID requesting duplication ability.
	 *
	 * @return bool
	 */
	public function can_duplicate_feed( $feed_id ) {

		return true;

	}





	// # FEED LIST -----------------------------------------------------------------------------------------------------

	/**
	 * Setup columns for feed list table.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function feed_list_columns() {

		return array(
			'feed_name' => esc_html__( 'Name', 'gravityformsslack' ),
			'action'    => esc_html__( 'Action', 'gravityformsslack' ),
			'send_to'   => esc_html__( 'Send To', 'gravityformsslack' ),
		);

	}

	/**
	 * Get value for action feed list column.
	 *
	 * @since  1.0
	 * @access public
	 * @param  array $feed Feed object.
	 *
	 * @return string
	 */
	public function get_column_value_action( $feed ) {

		switch ( rgars( $feed, 'meta/action' ) ) {

			case 'invite':
				return esc_html__( 'Invite to Team', 'gravityformsslack' );

			case 'message':
				return esc_html__( 'Send Message', 'gravityformsslack' );

		}

	}

	/**
	 * Get value for send to feed list column.
	 *
	 * @since  1.0
	 * @access public
	 * @param  array $feed Feed object.
	 *
	 * @return string
	 */
	public function get_column_value_send_to( $feed ) {

		// If this is an invite feed, return null.
		if ( 'invite' === rgars( $feed, 'meta/action' ) ) {
			return null;
		}

		// If Slack instance is not initialized, return channel ID.
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





	// # FEED PROCESSING -----------------------------------------------------------------------------------------------

	/**
	 * Process feed.
	 *
	 * @since  1.0
	 * @access public
	 * @param  array $feed The feed object to be processed.
	 * @param  array $entry The entry object currently being processed.
	 * @param  array $form The form object currently being processed.
	 */
	public function process_feed( $feed, $entry, $form ) {

		// If Slack instance is not initialized, exit.
		if ( ! $this->initialize_api() ) {
			$this->add_feed_error( esc_html__( 'Feed was not processed because API was not initialized.', 'gravityformsslack' ), $feed, $entry, $form );
			return;
		}

		// Process feed based on action.
		switch ( rgars( $feed, 'meta/action' ) ) {

			case 'invite':
				$this->send_invite( $feed, $entry, $form );
				break;

			default:
				$this->send_message( $feed, $entry, $form );
				break;

		}

	}

	/**
	 * Send Slack invite.
	 *
	 * @since  1.4
	 * @access public
	 * @param  array $feed The feed object to be processed.
	 * @param  array $entry The entry object currently being processed.
	 * @param  array $form The form object currently being processed.
	 */
	public function send_invite( $feed, $entry, $form ) {

		// Get team name.
		$team_name = $this->get_plugin_setting( 'team_name' );

		// If no team name is provided, exit.
		if ( rgblank( $team_name ) ) {
			$this->add_feed_error( esc_html__( 'Unable to invite user because no team name is defined.', 'gravityformsslack' ), $feed, $entry, $form );
		}

		// Get email address.
		$email_address = $this->get_field_value( $form, $entry, rgars( $feed, 'meta/email' ) );

		// If no email address is provided, exit.
		if ( rgblank( $email_address ) ) {
			$this->add_feed_error( esc_html__( 'Unable to invite user because no email address was provided.', 'gravityformsslack' ), $feed, $entry, $form );
		}

		// Send invite.
		$this->log_debug( __METHOD__ . '(): Sending invite to: ' . $email_address );
		$invite = $this->api->invite_user( $team_name, $email_address );

		// Log result.
		if ( rgar( $invite, 'ok' ) ) {
			$this->log_debug( __METHOD__ . '(): User was invited.' );
		} else {
			$this->add_feed_error( esc_html__( 'User was not invited.', 'gravityformsslack' ), $feed, $entry, $form );
		}

	}

	/**
	 * Send Slack message.
	 *
	 * @since  1.4
	 * @access public
	 * @param  array $feed The feed object to be processed.
	 * @param  array $entry The entry object currently being processed.
	 * @param  array $form The form object currently being processed.
	 */
	public function send_message( $feed, $entry, $form ) {

		// Prepare notification array.
		$message = array(
			'as_user'  => false,
			'icon_url' => apply_filters( 'gform_slack_icon', $this->get_base_url() . '/images/icon.png', $feed, $entry, $form ),
			'text'     => $feed['meta']['message'],
			'username' => gf_apply_filters( 'gform_slack_username', array( $form['id'] ), 'Gravity Forms', $feed, $entry, $form ),
		);

		// Add channel based on send_to.
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

		// Replace merge tags on notification message.
		$message['text'] = GFCommon::replace_variables( $message['text'], $form, $entry, false, false, false, 'text' );
		if ( gf_apply_filters( 'gform_slack_process_message_shortcodes', $form['id'], false, $form, $feed ) ) {
			$message['text'] = do_shortcode( $message['text'] );
		}

		// If message is empty, exit.
		if ( rgblank( $message['text'] ) ) {
			$this->add_feed_error( esc_html__( 'Notification message is empty.', 'gravityformsslack' ), $feed, $entry, $form );
			return;
		}

		// Prepare attachments.
		$message = $this->prepare_attachments( $message, $feed, $entry, $form );

		// Post message to channel.
		$this->log_debug( __METHOD__ . '(): Posting message: ' . print_r( $message, true ) );
		$message_channel = $this->api->post_message( $message );

		// Log result.
		if ( rgar( $message_channel, 'ok' ) ) {
			$this->log_debug( __METHOD__ . '(): Message was posted to channel.' );
		} else {
			$this->add_feed_error( esc_html__( 'Message was not posted to channel.', 'gravityformsslack' ), $feed, $entry, $form );
		}

	}

	/**
	 * Prepare attachments for Slack message.
	 *
	 * @since  1.0
	 * @access public
	 * @param  array $message Slack message object.
	 * @param  array $feed The feed object to be processed.
	 * @param  array $entry The entry object currently being processed.
	 * @param  array $form The form object currently being processed.
	 *
	 * @return array $message
	 */
	public function prepare_attachments( $message, $feed, $entry, $form ) {

		// Initialize files array.
		$files = array();

		// Get files for the selected fields.
		if ( rgars( $feed, 'meta/attachments' ) ) {

			// Loop through attachment fields.
			foreach ( $feed['meta']['attachments'] as $field => $enabled ) {

				// If this field is not enabled for this feed, skip it.
				if ( '0' === $enabled ) {
					continue;
				}

				// Get the field value.
				$field_value = $this->get_field_value( $form, $entry, $field );

				// If no files were uploaded for this field, skip it.
				if ( rgblank( $field_value ) ) {
					continue;
				}

				// Convert attachments string to array.
				$field_value = explode( ',', $field_value );
				$field_value = array_map( 'trim', $field_value );

				// Add field files to files array.
				$files = array_merge( $files, $field_value );

			}

		}

		// If no files were uploaded, return the message object.
		if ( empty( $files ) ) {
			return $message;
		}

		// Add images to attachments array.
		foreach ( $files as $file ) {

			// Get file path.
			$file_path = str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $file );

			// Get file details.
			$file_details = wp_check_filetype_and_ext( $file_path, basename( $file_path ) );

			// If file is not an image, skip it.
			if ( strpos( $file_details['type'], 'image') === false ) {
				continue;
			}

			// Attach image to message.
			$message['attachments'][] = array(
				'fallback'  => $message['text'],
				'image_url' => $file,
				'text'      => basename( $file_path ),
			);

		}

		// Convert attachments to JSON string.
		if ( rgar( $message, 'attachments' ) ) {
			$message['attachments'] = json_encode( $message['attachments'] );
		}

		return $message;

	}





	// # HELPER FUNCTIONS ----------------------------------------------------------------------------------------------

	/**
	 * Initializes Slack API if credentials are valid.
	 *
	 * @since  1.0
	 * @access public
	 * @param  string $auth_token Authentication token.
	 *
	 * @return bool|null
	 */
	public function initialize_api( $auth_token = null ) {

		// If API is alredy initialized and auth token is not provided, return true.
		if ( ! is_null( $this->api ) && is_null( $auth_token ) ) {
			return true;
		}

		// Load the API library.
		if ( ! class_exists( 'GF_Slack_API' ) ) {
			require_once( 'includes/class-gf-slack-api.php' );
		}

		// Get the OAuth token.
		if ( rgblank( $auth_token ) ) {
			$auth_token = $this->get_plugin_setting( 'auth_token' );
		}

		// If the OAuth token, do not run a validation check.
		if ( rgblank( $auth_token ) ) {
			return null;
		}

		// Log validation step.
		$this->log_debug( __METHOD__ . '(): Validating API Info.' );

		// Setup a new Slack object with the API credentials.
		$slack = new GF_Slack_API( $auth_token );

		// Run an authentication test.
		$auth_test = $slack->auth_test();

		// If authentication test passed, assign API library to instance.
		if ( rgar( $auth_test, 'ok' ) ) {

			// Assign API library to instance.
			$this->api = $slack;

			// Log that authentication test passed.
			$this->log_debug( __METHOD__ . '(): API credentials are valid.' );

			return true;

		} else {

			// Log that authentication test failed.
			$this->log_error( __METHOD__ . '(): API credentials are invalid; '. $auth_test['error'] );

			return false;

		}

	}

	/**
	 * Open an IM channel with user.
	 *
	 * @since  1.0
	 * @access public
	 * @param  string $user User ID.
	 *
	 * @return null|string
	 */
	public function open_im_channel( $user ) {

		// If API is not initialized, return null.
		if ( ! $this->initialize_api() ) {
			return null;
		}

		// Open IM channel.
		$im_channel = $this->api->open_im( $user );

		// Return IM channel ID or user ID.
		return rgar( $im_channel, 'channel' ) ? $im_channel['channel']['id'] : $user;

	}





	// # UPGRADES ------------------------------------------------------------------------------------------------------

	/**
	 * Run required routines when upgrading from previous versions of Add-On.
	 *
	 * @since  1.4
	 * @access public
	 * @param  string $previous_version Previous version number.
	 */
	public function upgrade( $previous_version ) {

		// Determine if previous version is before invite to team feature or auto team name detection.
		$previous_is_pre_invite    = ! empty( $previous_version ) && version_compare( $previous_version, '1.4', '<' );
		$previous_is_pre_auto_team = ! empty( $previous_version ) && version_compare( $previous_version, '1.4.1', '<' );

		// If previous version is before invite to team feature, add action to existing feeds.
		if ( $previous_is_pre_invite ) {

			// Get feeds.
			$feeds = $this->get_feeds();

			// If no feeds are found, exit.
			if ( empty( $feeds ) ) {
				return;
			}

			// Loop through feeds.
			foreach ( $feeds as $feed ) {

				// If action is defined, skip feed.
				if ( rgars( $feed, 'meta/action' ) ) {
					continue;
				}

				// Add action.
				$feed['meta']['action'] = 'message';

				// Update feed.
				$this->update_feed_meta( $feed['id'], $feed['meta'] );

			}

		}

		// If previous version is before auto team name detection was added, add team name to plugin settings.
		if ( $previous_is_pre_auto_team ) {

			// If API is not initialized, return.
			if ( ! $this->initialize_api() ) {
				return;
			}

			// Get plugin settings.
			$settings = $this->get_plugin_settings();

			// Get team info.
			$team_info = $this->api->get_team_info();

			// Set team name plugin setting.
			$settings['team_name'] = rgars( $team_info, 'team/domain' );

			// Update plugin settings.
			$this->update_plugin_settings( $settings );

		}

	}

}
