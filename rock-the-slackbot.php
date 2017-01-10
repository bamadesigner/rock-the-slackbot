<?php
/**
 * Plugin Name:     Rock The Slackbot
 * Plugin URI:      https://wordpress.org/plugins/rock-the-slackbot/
 * Description:     Helps you stay on top of changes by sending notifications straight to you and your team inside your Slack account.
 * Version:         1.1.2
 * Author:          Rachel Carden
 * Author URI:      https://bamadesigner.com
 * License:         GPL-2.0+
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:     rock-the-slackbot
 * Domain Path:     /languages
 *
 * @package         Rock_The_Slackbot
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*
 * @TODO:
 *
 * Add setting to disable 404 notifications from search engines
 *      or to allow blacklist for notifications?
 *
 * Add some identifiable info to the "Exclude Post Types" life
 *      for when CPTs share the same label (from Matt).
 */

// Load the files.
require_once plugin_dir_path( __FILE__ ) . 'includes/hooks.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/outgoing-webhooks.php';

// We only need you in the admin.
if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/admin.php';
}

/**
 * Class the holds the root
 * functionality for the plugin.
 *
 * @package     Rock_The_Slackbot
 */
class Rock_The_Slackbot {

	/**
	 * Whether or not this plugin is network active.
	 *
	 * @since   1.1.0
	 * @access  public
	 * @var     boolean
	 */
	public $is_network_active;

	/**
	 * Holds the plugin version number.
	 *
	 * @since   1.1.2
	 * @access  private
	 * @var     string
	 */
	private $version = '1.1.2';

	/**
	 * Holds the plugin's URL.
	 *
	 * @since   1.1.2
	 * @access  private
	 * @var     string
	 */
	private $plugin_url = 'https://wordpress.org/plugins/rock-the-slackbot/';

	/**
	 * Holds the plugin's
	 * relative file path.
	 *
	 * @since   1.1.2
	 * @access  private
	 * @var     string
	 */
	private $plugin_file = 'rock-the-slackbot/rock-the-slackbot.php';

	/**
	 * Holds the class instance.
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     Rock_The_Slackbot
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  Rock_The_Slackbot
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name = __CLASS__;
			self::$instance = new $class_name;
		}
		return self::$instance;
	}

	/**
	 * Warming up the Slack mobile.
	 *
	 * @access  protected
	 * @since   1.0.0
	 */
	protected function __construct() {

		// Is this plugin network active?
		$this->is_network_active = false;
		if ( is_multisite() ) {

			// Get the list of network active plugins.
			$plugins = get_site_option( 'active_sitewide_plugins' );
			if ( ! empty( $plugins ) ) {

				// Marks true if our plugin is network active.
				$plugin_file = $this->get_plugin_file();
				$this->is_network_active = $plugin_file && ! empty( $plugins[ $plugin_file ] );

			}
		}

		// Load our text domain.
		add_action( 'init', array( $this, 'textdomain' ) );

		// Runs on install.
		register_activation_hook( __FILE__, array( $this, 'install' ) );

		// Runs when the plugin is upgraded.
		add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_complete' ), 1, 2 );

	}

	/**
	 * Method to keep our instance from being cloned.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @return	void
	 */
	private function __clone() {}

	/**
	 * Method to keep our instance from being unserialized.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @return	void
	 */
	private function __wakeup() {}

	/**
	 * Runs when the plugin is installed.
	 *
	 * @TODO Set it up so it will store what post types are registered
	 * when the settings are first saved and so then it can recognize
	 * when new post types are added and ask you in the admin if you
	 * want to exclude them to your notifications?
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function install() {}

	/**
	 * Runs when the plugin is upgraded.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function upgrader_process_complete() {}

	/**
	 * Internationalization FTW.
	 * Load our textdomain.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function textdomain() {
		load_plugin_textdomain( 'rock-the-slackbot', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Returns the plugin version.
	 *
	 * @access  public
	 * @since   1.1.2
	 * @return  string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Returns the plugin's URL.
	 *
	 * @access  public
	 * @since   1.1.2
	 * @return  string
	 */
	public function get_plugin_url() {
		return $this->plugin_url;
	}

	/**
	 * Returns the plugin's
	 * relative file path.
	 *
	 * @access  public
	 * @since   1.1.2
	 * @return  string
	 */
	public function get_plugin_file() {
		return $this->plugin_file;
	}

	/**
	 * In order to send links in messages to Slack,
	 * you have to wrap them with <>, e.g. <http://wordpress.org>.
	 *
	 * This function will remove the <> around links.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param   string - $text - the text that has links.
	 * @return  string - the formatted text
	 */
	public function unformat_slack_links( $text ) {
		return preg_replace( '/\<(http([^\>])+)\>/i', '${1}', $text );
	}

	/**
	 * Returns all of our webhook events.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  array - array of names of notification events
	 */
	public function get_webhook_events() {
		return array(
			'content' => array(
				'label' => __( 'Content', 'rock-the-slackbot' ),
				'events' => array(
					'post_published' => array(
						'label' => __( 'When a post is published', 'rock-the-slackbot' ),
						'default' => 1,
					),
					'post_unpublished' => array(
						'label' => __( 'When a post is unpublished', 'rock-the-slackbot' ),
						'default' => 1,
					),
					'post_draft' => array(
						'label' => __( 'When a post is drafted', 'rock-the-slackbot' ),
						'default' => 1,
					),
					'post_pending' => array(
						'label' => __( 'When a post is pending review', 'rock-the-slackbot' ),
						'default' => 1,
					),
					'post_future' => array(
						'label' => __( 'When a post is scheduled', 'rock-the-slackbot' ),
						'default' => 1,
					),
					'post_updated' => array(
						'label' => __( "When a post's content is updated", 'rock-the-slackbot' ),
						'default' => 1,
					),
					'post_deleted' => array(
						'label' => __( 'When a post is deleted', 'rock-the-slackbot' ),
					),
					'post_trashed' => array(
						'label' => __( 'When a post is trashed', 'rock-the-slackbot' ),
					),
					'is_404' => array(
						'label' => __( 'When a 404 error is thrown', 'rock-the-slackbot' ),
					),
				),
			),
			'comments' => array(
				'label' => __( 'Comments', 'rock-the-slackbot' ),
				'events' => array(
					'insert_comment' => array(
						'label' => __( 'When a comment is added', 'rock-the-slackbot' ),
					),
					'comment_unapproved' => array(
						'label' => __( 'When a comment is unapproved', 'rock-the-slackbot' ),
					),
					'comment_approved' => array(
						'label' => __( 'When a comment is approved', 'rock-the-slackbot' ),
					),
					'comment_spammed' => array(
						'label' => __( 'When a comment is marked as spam', 'rock-the-slackbot' ),
					),
					'comment_trashed' => array(
						'label' => __( 'When a comment is trashed', 'rock-the-slackbot' ),
					),
				),
			),
			'menus' => array(
				'label' => __( 'Menus', 'rock-the-slackbot' ),
				'events' => array(
					'menu_item_deleted' => array(
						'label' => __( 'When a menu item is deleted', 'rock-the-slackbot' ),
					),
				),
			),
			'media' => array(
				'label' => __( 'Media', 'rock-the-slackbot' ),
				'events' => array(
					'add_attachment' => array(
						'label' => __( 'When media is added', 'rock-the-slackbot' ),
					),
					'edit_attachment' => array(
						'label' => __( 'When media is edited', 'rock-the-slackbot' ),
					),
					'delete_attachment' => array(
						'label' => __( 'When media is deleted', 'rock-the-slackbot' ),
					),
				),
			),
			'users' => array(
				'label' => __( 'Users', 'rock-the-slackbot' ),
				'events' => array(
					'user_added' => array(
						'label' => __( 'When a user is added', 'rock-the-slackbot' ),
					),
					'user_deleted' => array(
						'label' => __( 'When a user is deleted', 'rock-the-slackbot' ),
					),
					'set_user_role' => array(
						'label' => __( "When a user's role has changed", 'rock-the-slackbot' ),
					),
				),
			),
			'updates' => array(
				'label' => __( 'Updates', 'rock-the-slackbot' ),
				'events' => array(
					'core_update_available' => array(
						'label'     => sprintf( __( 'When a %s core update is available', 'rock-the-slackbot' ), 'WordPress' ),
						'default'   => 1,
					),
					'core_updated' => array(
						'label'     => sprintf( __( 'When %s core is updated', 'rock-the-slackbot' ), 'WordPress' ),
						'default'   => 1,
					),
					'plugin_update_available' => array(
						'label'     => __( 'When a plugin update is available', 'rock-the-slackbot' ),
					),
					'plugin_updated' => array(
						'label'     => __( 'When a plugin is updated', 'rock-the-slackbot' ),
					),
					'theme_update_available' => array(
						'label'     => __( 'When a theme update is available', 'rock-the-slackbot' ),
					),
					'theme_updated' => array(
						'label'     => __( 'When a theme is updated', 'rock-the-slackbot' ),
					),
				),
			),
		);
	}

	/**
	 * Returns all of our outgoing webhooks, no matter their status.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param	boolean - $network - whether or not to retrieve network webhooks.
	 * @return  array|false - array of webhook or false if none exist
	 */
	public function get_all_outgoing_webhooks( $network = true ) {

		// Get site webhooks.
		$webhooks = get_option( 'rock_the_slackbot_outgoing_webhooks', array() );

		// Make sure its an array.
		if ( empty( $webhooks ) ) {
			$webhooks = array();
		}

		// Get network webhooks.
		if ( $network && $this->is_network_active ) {

			if ( ( $network_webhooks = get_site_option( 'rock_the_slackbot_network_outgoing_webhooks', array() ) )
				&& is_array( $network_webhooks ) ) {
				$webhooks = array_merge( $network_webhooks, $webhooks );
			}
		}

		return $webhooks;
	}

	/**
	 * Returns all of our active outgoing webhooks.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  array|false - array of webhooks or false if none exist
	 */
	public function get_active_outgoing_webhooks() {

		// Get all outgoing webhooks.
		if ( ! ( $outgoing_webhooks = $this->get_all_outgoing_webhooks() ) ) {
			return false;
		}

		// Go through and pick out active outgoing webhooks.
		$active_outgoing_webhooks = array();

		// Check for not deactivated hooks.
		foreach ( $outgoing_webhooks as $hook ) {
			if ( ! ( isset( $hook['deactivate'] ) && $hook['deactivate'] > 0 ) ) {
				$active_outgoing_webhooks[] = $hook;
			}
		}

		return ! empty( $active_outgoing_webhooks ) ? $active_outgoing_webhooks : false;
	}

	/**
	 * Returns active outgoing webhooks, allows you to filter by event.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param   string|array - $events - if provided, only return webhooks with these events.
	 * @param   array - $event_data - allows hooks to pass event specific data to test with webhooks.
	 * @return  array|false - array of webhooks or false if none exist
	 */
	public function get_outgoing_webhooks( $events = null, $event_data = array() ) {

		// Get active outgoing webhooks.
		if ( ! ( $outgoing_webhooks = $this->get_active_outgoing_webhooks() ) ) {
			return false;
		}

		// If we're not filtering by event, then get out of here.
		if ( empty( $events ) ) {
			return $outgoing_webhooks;
		} else {

			// Make sure events is an array.
			if ( ! is_array( $events ) ) {
				$events = explode( ',', str_replace( ' ', '', $events ) );
			}

			// Filter by event.
			$filtered_webhooks = array();

			// Did we pass data? Make sure its ready to go.
			if ( ! empty( $event_data ) ) {

				// Did we pass a post type?
				if ( ! empty( $event_data['post_type'] ) ) {

					// Make sure its an array.
					if ( ! is_array( $event_data['post_type'] ) ) {
						$event_data['post_type'] = explode( ',', $event_data['post_type'] );
					}
				}
			}

			// Go through and check for the event.
			foreach ( $outgoing_webhooks as $hook ) {

				// If we have excluded post types and a post type was sent, then skip this webhook.
				if ( isset( $event_data['post_type'] )
					&& ( isset( $hook['exclude_post_types'] ) || isset( $hook['network_exclude_post_types'] ) ) ) {

					// Get the post types we should exclude.
					$exclude_post_types = array();

					// Get the regular exclude post types.
					if ( ! empty( $hook['exclude_post_types'] ) ) {

						// Make sure its an array.
						if ( ! is_array( $hook['exclude_post_types'] ) ) {
							$hook['exclude_post_types'] = explode( ',', $hook['exclude_post_types'] );
						}

						// Add to list.
						$exclude_post_types = $hook['exclude_post_types'];

					}

					// Get the network exclude post types.
					if ( ! empty( $hook['network_exclude_post_types'] ) ) {

						// Make sure its an array.
						if ( ! is_array( $hook['network_exclude_post_types'] ) ) {
							$hook['network_exclude_post_types'] = explode( ',', $hook['network_exclude_post_types'] );
						}

						// Add to list.
						$exclude_post_types = array_merge( $exclude_post_types, $hook['network_exclude_post_types'] );

					}

					// Check to see if the post type sent is supposed to be excluded.
					if ( array_intersect( $event_data['post_type'], $exclude_post_types ) ) {
						continue;
					}
				}

				// Check the events.
				if ( ! empty( $hook['events'] ) && is_array( $hook['events'] ) ) {
					foreach ( $events as $event ) {

						// This webhook has the event we're looking for.
						if ( array_key_exists( $event, $hook['events'] ) ) {

							// Get the event settings.
							$event_settings = $hook['events'][ $event ];

							// Don't include if not active.
							if ( ! ( isset( $event_settings['active'] ) && 1 == $event_settings['active'] ) ) {
								continue;
							}

							// If this event has excluded post types and a post type was sent, then skip this webhook.
							if ( ! empty( $event_data['post_type'] ) && ! empty( $event_settings['exclude_post_types'] ) ) {

								// Make sure its an array.
								if ( ! is_array( $event_settings['exclude_post_types'] ) ) {
									$event_settings['exclude_post_types'] = explode( ',', $event_settings['exclude_post_types'] );
								}

								// Check to see if the post type sent is supposed to be excluded.
								if ( array_intersect( $event_data['post_type'], $event_settings['exclude_post_types'] ) ) {
									continue;
								}
							}

							// Add the webhook.
							$filtered_webhooks[] = $hook;

							break;

						}
					}
				}
			}

			return ! empty( $filtered_webhooks ) ? $filtered_webhooks : false;
		}
	}

	/**
	 * Returns a specific outgoing webhook.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param	string - $hook_id - the hook ID.
	 * @param	boolean - $network - whether or not this is a network webhook.
	 * @return  array|false - the webhook or false if it doesn't exist
	 */
	public function get_outgoing_webhook( $hook_id, $network = false ) {

		// Get all outgoing webhooks.
		$outgoing_webhooks = $this->get_all_outgoing_webhooks( $network );
		if ( ! $outgoing_webhooks ) {
			return false;
		}

		// Go through and find one with ID.
		foreach ( $outgoing_webhooks as $hook ) {
			if ( isset( $hook['ID'] ) && $hook['ID'] == $hook_id ) {
				return $hook;
			}
		}

		return false;
	}

	/**
	 * Send a simple, custom message to Slack via webhook.
	 *
	 * @access  public
	 * @since   1.1.1
	 * @param   string - $webhook_id_or_url - provide the webhook URL or the ID of one stored in settings.
	 * @param   string - $message - the message you want to send.
	 * @param   string - $channel - the channel you want to send message to, will use default channel if webhook ID is passed.
	 * @return  boolean|WP_Error - true if sent, WP_Error if error
	 */
	public function send_webhook_message( $webhook_id_or_url, $message, $channel = '' ) {

		// Create the payload.
		$payload = array(
			'channel'	=> $channel,
			'text' 		=> $message,
		);

		// Set webhook URL if what is passed is URL.
		$webhook_url = preg_match( '/^http/i', $webhook_id_or_url ) ? $webhook_id_or_url : false;

		// If not URL, check for ID.
		if ( ! $webhook_url ) {

			// Get webhook - check the network too.
			$webhook = rock_the_slackbot()->get_outgoing_webhook( $webhook_id_or_url, true );

			// If webhook and has URL.
			if ( ! empty( $webhook['webhook_url'] ) ) {
				$webhook_url = $webhook['webhook_url'];
			} else {

				// Return the error.
				return new WP_Error( 'slack_send_message_error', __( 'The webhook ID passed is not valid.', 'rock-the-slackbot' ) );

			}
		}

		// Send the message.
		$sent_message = rock_the_slackbot_outgoing_webhooks()->send_payload( $webhook_url, $payload );

		// Was there an error?
		if ( is_wp_error( $sent_message ) ) {

			// Return the error.
			return new WP_Error( 'slack_send_message_error', $sent_message->get_error_message() );

		}

		return true;
	}

	/**
	 * Send the "error" email when sending a payload fails.
	 *
	 * @since   1.1.2
	 * @param   string - the email address.
	 * @param   array - the email arguments.
	 */
	public function send_error_email( $email, $args = array() ) {

		// Define the defaults.
		$defaults = array(
			'webhook_url'   => '',
			'payload'       => '',
		);

		// Merge incoming arguments.
		$args = wp_parse_args( $args, $defaults );

		// Build email message.
		$message = sprintf( __( 'There was an error when trying to post to %1$s from %2$s.', 'rock-the-slackbot' ), 'Slack', 'WordPress' );

		// Add payload URL.
		if ( ! empty( $args['webhook_url'] ) ) {
			$message .= "\n\n<br /><br /><strong>" . __( 'Payload URL', 'rock-the-slackbot' ) . ':</strong> ' . $args['webhook_url'];
		}

		// Add payload channel.
		if ( ! empty( $args['payload']['channel'] ) ) {
			$message .= "\n<br /><strong>" . __( 'Channel', 'rock-the-slackbot' ) . ':</strong> ' . $args['payload']['channel'];
		}

		// Fix any links in the general text message.
		if ( ! empty( $args['payload']['text'] ) ) {

			// Replace Slack links.
			$args['payload']['text'] = rock_the_slackbot()->unformat_slack_links( $args['payload']['text'] );

			// Add general message.
			$message .= "\n\n<br /><br /><strong>" . __( 'Message', 'rock-the-slackbot' ) . ':</strong> ' . $args['payload']['text'];

		}

		// Add attachment info.
		if ( ! empty( $args['payload']['attachments'] ) ) {

			$message .= "\n\n<br /><br /><strong>" . __( 'Attachments', 'rock-the-slackbot' ) . ':</strong>';
			foreach ( $args['payload']['attachments'] as $attachment ) {
				$message .= "\n<br />";

				// Add fields.
				if ( ! empty( $attachment['fields'] ) ) {
					foreach ( $attachment['fields'] as $field ) {
						$message .= "\n\t<br />&nbsp;&nbsp;&nbsp;&nbsp;<strong>" . $field['title'] . ':</strong> ' . $field['value'];
					}
				}
			}
		}

		// Filter the email pieces.
		$email_pieces = apply_filters( 'rock_the_slackbot_error_email', array(
			'to'        => $email,
			'subject'   => sprintf( __( '%1$s to %2$s error', 'rock-the-slackbot' ), 'WordPress', 'Slack' ),
			'message'   => $message,
		), $args );

		// Make sure we still have email pieces.
		if ( ! empty( $email_pieces['to'] ) && ! empty( $email_pieces['subject'] ) ) {

			// Set email to be HTML.
			add_filter( 'wp_mail_content_type', 'rock_the_slackbot_set_html_content_type' );

			// Send email notification to the admin.
			$send_email = wp_mail( $email_pieces['to'], $email_pieces['subject'], isset( $email_pieces['message'] ) ? $email_pieces['message'] : '' );

			// Reset content-type to avoid conflicts.
			remove_filter( 'wp_mail_content_type',  'rock_the_slackbot_set_html_content_type' );

		}

		return $send_email;
	}

}

/**
 * Set emails to be HTML.
 *
 * @since   1.1.1
 * @return  string - email content type
 */
function rock_the_slackbot_set_html_content_type() {
	return 'text/html';
}

/**
 * Returns the instance of our main Rock_The_Slackbot class.
 *
 * Will come in handy when we need to access the
 * class to retrieve data throughout the plugin.
 *
 * @since	1.0.0
 * @access	public
 * @return	Rock_The_Slackbot
 */
function rock_the_slackbot() {
	return Rock_The_Slackbot::instance();
}

// Let's get this show on the road.
rock_the_slackbot();
