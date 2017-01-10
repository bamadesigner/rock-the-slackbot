<?php
/**
 * Holds the functionality
 * for our different hooks
 * that interact with WordPress.
 *
 * @package  Rock_The_Slackbot
 */

/**
 * Class that holds all of the
 * functionality for our different
 * hooks that interact with WordPress.
 *
 * @package Rock_The_Slackbot
 */
class Rock_The_Slackbot_Hooks {

	/**
	 * Holds the class instance.
	 *
	 * @since   1.1.2
	 * @access  private
	 * @var     Rock_The_Slackbot
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @access  public
	 * @since   1.1.2
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
	 * This class sets up the notifications
	 * for all of the various hooks.
	 *
	 * @access  protected
	 * @since   1.0.0
	 */
	protected function __construct() {

		/*
         * @TODO Setup notifications for:
		 *      When menu item is added
		 *      When an option is added or edited?
		 *      When a new comment is awaiting moderation?
		 *          Time it to send a message once a week if comments need moderation
		 *      When certain folks log in?
		 *      When there are PHP errors
		 *      When plugins are activated
		 *      When plugins and themes are installed/uploaded
		 *      When themes are selected
		 */

		// Sends notifications when a core, plugin, or theme update is available.
		add_action( 'admin_init', array( $this, 'core_update_available_notification' ), 100 );
		add_action( 'admin_init', array( $this, 'plugin_update_available_notification' ), 100 );
		add_action( 'admin_init', array( $this, 'theme_update_available_notification' ), 100 );

		// Fires when the bulk upgrader process is complete.
		add_action( 'upgrader_process_complete', array( $this, 'upgrade_notification' ), 100, 2 );

		// Fires once an existing post has been updated.
		add_action( 'post_updated', array( $this, 'updated_post_notification' ), 100, 3 );

		// Fires when a post is transitioned from one status to another.
		add_action( 'transition_post_status', array( $this, 'transition_post_status_notification' ), 100, 3 );

		// Fires before a post is sent to the trash.
		add_action( 'wp_trash_post', array( $this, 'wp_trash_post_notification' ), 100 );

		// Fires before a post is deleted, at the start of wp_delete_post().
		add_action( 'before_delete_post', array( $this, 'delete_post_notification' ), 100, 1 );

		// Fires once an attachment has been added.
		add_action( 'add_attachment', array( $this, 'add_attachment_notification' ), 100 );

		// Fires once an existing attachment has been updated.
		add_action( 'edit_attachment', array( $this, 'edit_attachment_notification' ), 100 );

		// Fires before an attachment is deleted, at the start of wp_delete_attachment().
		add_action( 'delete_attachment', array( $this, 'delete_attachment_notification' ), 100 );

		// Fires once the WordPress environment has been set up.
		add_action( 'wp', array( $this, 'is_404_notification' ), 100 );

		// Fires immediately after a new user is registered.
		add_action( 'user_register', array( $this, 'user_added_notification' ), 100 );

		// Fires immediately before a user is deleted from the database.
		add_action( 'delete_user', array( $this, 'user_deleted_notification' ), 100, 2 );

		// Fires after the user's role has changed.
		add_action( 'set_user_role', array( $this, 'user_role_notification' ), 100, 3 );

		// Fires immediately after a comment is added.
		add_action( 'wp_insert_comment', array( $this, 'comment_inserted' ), 100, 2 );

		// Fires when a comment status is in transition.
		add_action( 'transition_comment_status', array( $this, 'transition_comment_status' ), 100, 3 );

	}

	/**
	 * Method to keep our instance from being cloned.
	 *
	 * @since   1.1.2
	 * @access  private
	 * @return  void
	 */
	private function __clone() {}

	/**
	 * Method to keep our instance from being unserialized.
	 *
	 * @since   1.1.2
	 * @access  private
	 * @return  void
	 */
	private function __wakeup() {}

	/**
	 * Retrieves saved outgoing webhooks.
	 *
	 * If event names are passed, then only
	 * retrieves webhooks tied to the event(s).
	 *
	 * @access  private
	 * @since   1.0.0
	 * @param   string|array - $events - when you want webhooks tied to specific events.
	 * @param   array - $event_data - allows hooks to pass event specific data to test with webhooks.
	 * @return  array|false - the webhooks or false if none
	 */
	private function get_outgoing_webhooks( $events = null, $event_data = array() ) {
		return rock_the_slackbot()->get_outgoing_webhooks( $events, $event_data );
	}

	/**
	 * Makes sure the payload is setup properly.
	 *
	 * @access  private
	 * @since   1.0.0
	 * @param   array - $payload - the payload itself.
	 * @param   array - $attachments - the attachments info.
	 * @param   array - $webhook - the Slack webhook info.
	 * @param   string - $event - the ID of the event that's being processed.
	 * @return  array - the setup payload
	 */
	private function prepare_payload( $payload = array(), $attachments = array(), $webhook = array(), $event = null ) {

		// If a particular event was passed, see if it has info to overwrite the default payload.
		$event_settings = false;
		if ( $event && isset( $webhook['events'] ) && ! empty( $webhook['events'][ $event ] ) ) {

			// Set the event settings.
			$event_settings = $webhook['events'][ $event ];

		}

		// Add to the payload.
		foreach ( array( 'channel', 'username', 'icon_emoji', 'icon_url' ) as $var ) {

			// Get the default setting.
			if ( ! empty( $webhook[ $var ] ) ) {
				$payload[ $var ] = $webhook[ $var ];
			}

			// See if the event is overwriting the default.
			if ( $event_settings && ! empty( $event_settings[ $var ] ) ) {
				$payload[ $var ] = $event_settings[ $var ];
			}
		}

		// Add the attachments.
		$payload['attachments'] = $attachments;

		return $payload;
	}

	/**
	 * Sends a batch of outgoing webhooks.
	 *
	 * @access  private
	 * @since   1.0.0
	 * @param   string - $notification_event - name of the notification event.
	 * @param   array - $outgoing_webhooks - array of webhooks being sent.
	 * @param   array - $payload - payload info for notification.
	 * @param   array - $attachments - attachments info for notification.
	 * @param   array - $event_args - event specific information to send to the filters.
	 * @return  bool|array - true if all notifications were sent, array if error(s), false otherwise
	 */
	private function send_outgoing_webhooks( $notification_event, $outgoing_webhooks, $payload = array(), $attachments = array(), $event_args = array() ) {

		// Will hold notification errors if any.
		$notification_errors = array();

		// Loop through each webhook and send the notification.
		foreach ( $outgoing_webhooks as $hook ) {

			// We must have a webhook URL.
			$webhook_url = ! empty( $hook['webhook_url'] ) ? $hook['webhook_url'] : false;
			if ( ! $webhook_url ) {
				continue;
			}

			// Prepare the payload.
			$payload = $this->prepare_payload( $payload, $attachments, $hook, $notification_event );

			// Setup the pieces.
			$notification_pieces = compact( array( 'webhook_url', 'payload' ) );

			// Filter by event.
			$notification_pieces = apply_filters( "rock_the_slackbot_notification_{$notification_event}", $notification_pieces, $notification_event, $event_args );

			// Filter by hook ID.
			$notification_pieces = apply_filters( 'rock_the_slackbot_notification_' . $hook['ID'], $notification_pieces, $notification_event, $event_args );

			// General filter.
			$notification_pieces = apply_filters( 'rock_the_slackbot_notification', $notification_pieces, $notification_event, $event_args );

			// If returned false or empty, don't send the notification.
			if ( empty( $notification_pieces ) ) {
				return false;
			}

			// Assign the notification pieces to variables.
			$notification_webhook_url = ! empty( $notification_pieces['webhook_url'] ) ? $notification_pieces['webhook_url'] : '';
			$notification_payload = ! empty( $notification_pieces['payload'] ) ? $notification_pieces['payload'] : array();

			// Send the notification.
			$sent_notification = rock_the_slackbot_outgoing_webhooks()->send_payload( $notification_webhook_url, $notification_payload );

			// Was there an error?
			if ( is_wp_error( $sent_notification ) ) {

				// Add to errors.
				$notification_errors[] = $sent_notification;

				// Should we send an error email?
				if ( isset( $hook['send_error_email'] ) && $hook['send_error_email'] > 0 ) {

					// Define the error email address.
					$error_email_address = ! empty( $hook['send_error_email_address'] ) ? $hook['send_error_email_address'] : get_bloginfo( 'admin_email' );

					// Send the error email.
					rock_the_slackbot()->send_error_email( $error_email_address, array(
						'webhook_url' => $webhook_url,
						'payload'     => $payload,
					));

				}
			}
		}

		// Return errors, if any, otherwise true for no errors.
		return ! empty( $notification_errors ) ? $notification_errors : true;
	}

	/**
	 * Setup notifications for when a core update is available.
	 *
	 * @TODO might could change to be run
	 * when the transients that store this info
	 * are updated? Would that run too often?
	 *
	 * get_site_transient( 'update_core' )
	 *
	 * @access  public
	 * @since   1.1.0
	 */
	public function core_update_available_notification() {
		global $wp_version;

		// Only send update notices once a week.
		$core_update_transient = rock_the_slackbot()->is_network_active ? get_site_transient( 'rock_the_slack_core_update_available' ) : get_transient( 'rock_the_slack_core_update_available' );
		if ( false !== $core_update_transient && ( time() - $core_update_transient ) < WEEK_IN_SECONDS ) {
			return false;
		}

		// See if there's an update before moving forward.
		$update_wordpress = null;
		if ( ! ( function_exists( 'get_core_updates' )
			&& ( $update_wordpress = get_core_updates( array( 'dismissed' => false ) ) )
			&& ! empty( $update_wordpress )
			&& ( $update_wordpress = array_shift( $update_wordpress ) )
			&& ! in_array( $update_wordpress->response, array( 'development', 'latest' ) ) ) ) {
			return false;
		}

		// Which event are we processing?
		$notification_event = 'core_update_available';

		// Get the outgoing webhooks.
		$outgoing_webhooks = $this->get_outgoing_webhooks( $notification_event );

		// If we have no webhooks, then there's no point.
		if ( ! $outgoing_webhooks ) {
			return false;
		}

		// Get current user.
		$current_user = wp_get_current_user();

		// Get site URL and name.
		$site_url = get_bloginfo( 'url' );
		$site_name = get_bloginfo( 'name' );

		// Get update URL.
		$core_update_url = is_multisite() ? network_admin_url( 'update-core.php' ) : admin_url( 'update-core.php' );

		// Get core version.
		$core_update_version = ! empty( $update_wordpress->version ) ? $update_wordpress->version : false;

		// Create general message for the notification.
		$general_message = sprintf( __( 'A %1$s core update is available on the %2$s website at <%3$s>.', 'rock-the-slackbot' ),
			'WordPress',
			$site_name,
			$site_url
		);

		// Start creating the payload.
		$payload = array(
			'text' => $general_message,
		);

		// Start creating the fields.
		$fields = array(
			array(
				'title' => __( 'Current Version', 'rock-the-slackbot' ),
				'value' => $wp_version,
				'short' => true,
			),
		);

		// Add new version.
		if ( $core_update_version ) {
			$fields[] = array(
				'title' => __( 'New Version', 'rock-the-slackbot' ),
				'value' => $core_update_version,
				'short' => true,
			);
		}

		// Create attachments.
		$attachments = array(
			array(
				'fallback'      => $general_message,
				'text'          => null,
				'title'         => sprintf( __( 'Update %s Core', 'rock-the-slackbot' ), 'WordPress' ),
				'title_link'    => $core_update_url,
				'author_name'   => $current_user->display_name,
				'author_link'   => get_author_posts_url( $current_user->ID ),
				'author_icon'   => get_avatar_url( $current_user->ID, 32 ),
				'fields'        => $fields,
			),
		);

		// Send each webhook.
		$this->send_outgoing_webhooks( $notification_event, $outgoing_webhooks, $payload, $attachments, array(
			'current_version' 	=> $wp_version,
			'new_version'		=> $core_update_version,
		));

		// Store timestamp in transient so it only sends the update notice once a week.
		if ( rock_the_slackbot()->is_network_active ) {
			set_site_transient( 'rock_the_slack_core_update_available', time(), WEEK_IN_SECONDS );
		} else {
			set_transient( 'rock_the_slack_core_update_available', time(), WEEK_IN_SECONDS );
		}
	}

	/**
	 * Setup notifications for when a plugin update is available.
	 *
	 * @TODO might could change to be run
	 * when the transients that store this info
	 * are updated? Would that run too often?
	 *
	 * get_site_transient( 'update_plugins' )
	 *
	 * @access  public
	 * @since   1.1.0
	 */
	public function plugin_update_available_notification() {

		// Only send update notices once a week.
		$plugin_update_transient = rock_the_slackbot()->is_network_active ? get_site_transient( 'rock_the_slack_plugin_update_available' ) : get_transient( 'rock_the_slack_plugin_update_available' );
		if ( false !== $plugin_update_transient && ( time() - $plugin_update_transient ) < WEEK_IN_SECONDS ) {
			return false;
		}

		// Which event are we processing?
		$notification_event = 'plugin_update_available';

		// Get the outgoing webhooks.
		$outgoing_webhooks = $this->get_outgoing_webhooks( $notification_event );

		// If we have no webhooks, then there's no point.
		if ( ! $outgoing_webhooks ) {
			return false;
		}

		// Do we have any plugin updates?
		if ( ! ( ( $update_plugins = get_site_transient( 'update_plugins' ) )
			&& ! empty( $update_plugins->response ) ) ) {
			return false;
		}

		// How many updates are available?
		$update_count = count( $update_plugins->response );

		// Get site URL and name.
		$site_url = get_bloginfo( 'url' );
		$site_name = get_bloginfo( 'name' );

		// Create general message for the notification.
		$general_message = sprintf(
			_n( 'The following %1$s plugin has an update available on the %2$s website at <%3$s>.', 'The following %1$s plugins have an update available on the %2$s website at <%3$s>.', $update_count, 'rock-the-slackbot' ),
			'WordPress',
			$site_name,
			$site_url
		);

		// Loop through each webhook and send the notification.
		foreach ( $outgoing_webhooks as $hook ) {

			// We must have a webhook URL.
			if ( empty( $hook['webhook_url'] ) ) {
				continue;
			}

			// Start creating the payload.
			$payload = array(
				'text' => $general_message,
			);

			// Create attachments.
			$attachments = array();

			// Add upgrade items to the fields.
			foreach ( $update_plugins->response as $plugin => $plugin_response_data ) {

				// Get item data.
				$plugin_data = get_plugin_data( WP_CONTENT_DIR . '/plugins/' . $plugin );

				// Set item title.
				$item_title = ! empty( $plugin_data['Name'] ) ? $plugin_data['Name'] : false;

				// Set item URI.
				$item_uri = ! empty( $plugin_data['PluginURI'] ) ? $plugin_data['PluginURI'] : false;

				// Set item description.
				$item_desc = ! empty( $plugin_data['Description'] ) ? strip_tags( html_entity_decode( $plugin_data['Description'] ) ) : false;

				// Set item author name.
				$author_name = ! empty( $plugin_data['AuthorName'] ) ? $plugin_data['AuthorName'] : false;

				// Set item author URI.
				$author_uri = ! empty( $plugin_data['AuthorURI'] ) ? $plugin_data['AuthorURI'] : false;

				// Set item version.
				$item_version = ! empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : false;

				// Get new version.
				$new_version = ! empty( $plugin_response_data->new_version ) ? $plugin_response_data->new_version : false;

				// Start creating the fields.
				$fields = array();

				// Add version.
				$fields[] = array(
					'title' => __( 'Current Version', 'rock-the-slackbot' ),
					'value' => $item_version,
					'short' => true,
				);

				// Add new version.
				if ( $new_version ) {
					$fields[] = array(
						'title' => __( 'New Version', 'rock-the-slackbot' ),
						'value' => $new_version,
						'short' => true,
					);
				}

				// Add plugin URI.
				$fields[] = array(
					'title' => __( 'Manage Plugins', 'rock-the-slackbot' ),
					'value' => is_multisite() ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' ),
					'short' => true,
				);

				// Add to attachments.
				$attachments[] = array(
					'fallback'      => $general_message,
					'text'          => wp_trim_words( strip_tags( $item_desc ), 30, '...' ),
					'title'         => $item_title,
					'title_link'    => $item_uri,
					'author_name'   => $author_name,
					'author_link'   => $author_uri,
					'fields'        => $fields,
				);

			}

			// Send each webhook.
			$this->send_outgoing_webhooks( $notification_event, $outgoing_webhooks, $payload, $attachments, array(
				'plugins' => $update_plugins->response,
			));

		}

		// Store timestamp in transient so it only sends the update notice once a week.
		if ( rock_the_slackbot()->is_network_active ) {
			set_site_transient( 'rock_the_slack_plugin_update_available', time(), WEEK_IN_SECONDS );
		} else {
			set_transient( 'rock_the_slack_plugin_update_available', time(), WEEK_IN_SECONDS );
		}

	}

	/**
	 * Setup notifications for when a theme update is available.
	 *
	 * @TODO might could change to be run
	 * when the transients that store this info
	 * are updated? Would that run too often?
	 *
	 * get_site_transient( 'update_themes' )
	 *
	 * @access  public
	 * @since   1.1.0
	 */
	public function theme_update_available_notification() {

		// Only send update notices once a week.
		$theme_update_transient = rock_the_slackbot()->is_network_active ? get_site_transient( 'rock_the_slack_theme_update_available' ) : get_transient( 'rock_the_slack_theme_update_available' );
		if ( false !== $theme_update_transient && ( time() - $theme_update_transient ) < WEEK_IN_SECONDS ) {
			return false;
		}

		// Which event are we processing?
		$notification_event = 'theme_update_available';

		// Get the outgoing webhooks.
		$outgoing_webhooks = $this->get_outgoing_webhooks( $notification_event );

		// If we have no webhooks, then there's no point.
		if ( ! $outgoing_webhooks ) {
			return false;
		}

		// Do we have any theme updates?
		if ( ! ( ( $update_themes = get_site_transient( 'update_themes' ) )
			&& ! empty( $update_themes->response ) ) ) {
			return false;
		}

		// How many updates are available?
		$update_count = count( $update_themes->response );

		// Get site URL and name.
		$site_url = get_bloginfo( 'url' );
		$site_name = get_bloginfo( 'name' );

		// Create general message for the notification.
		$general_message = sprintf(
			_n( 'The following %1$s theme has an update available on the %2$s website at <%3$s>.', 'The following %1$s themes have an update available on the %2$s website at <%3$s>.', $update_count, 'rock-the-slackbot' ),
			'WordPress',
			$site_name,
			$site_url
		);

		// Loop through each webhook and send the notification.
		foreach ( $outgoing_webhooks as $hook ) {

			// We must have a webhook URL.
			if ( empty( $hook['webhook_url'] ) ) {
				continue;
			}

			// Start creating the payload.
			$payload = array(
				'text' => $general_message,
			);

			// Create attachments.
			$attachments = array();

			// Add upgrade items to the fields.
			foreach ( $update_themes->response as $theme => $theme_response_data ) {

				// Get item data.
				$theme_data = wp_get_theme( $theme_response_data['theme'] );

				// Set item title.
				$item_title = $theme_data->get( 'Name' );

				// Set item URI.
				$item_uri = $theme_data->get( 'ThemeURI' );

				// Set item description.
				$item_desc = strip_tags( html_entity_decode( $theme_data->get( 'Description' ) ) );

				// Set item author name.
				$author_name = $theme_data->get( 'Author' );

				// Set item author URI.
				$author_uri = $theme_data->get( 'AuthorURI' );

				// Set item version.
				$item_version = $theme_data->get( 'Version' );

				// Get new version.
				$new_version = ! empty( $theme_response_data['new_version'] ) ? $theme_response_data['new_version'] : false;

				// Start creating the fields.
				$fields = array();

				// Add version.
				$fields[] = array(
					'title' => __( 'Current Version', 'rock-the-slackbot' ),
					'value' => $item_version,
					'short' => true,
				);

				// Add new version.
				if ( $new_version ) {
					$fields[] = array(
						'title' => __( 'New Version', 'rock-the-slackbot' ),
						'value' => $new_version,
						'short' => true,
					);
				}

				// Add theme URI.
				$fields[] = array(
					'title' => __( 'Manage Themes', 'rock-the-slackbot' ),
					'value' => is_multisite() ? network_admin_url( 'themes.php' ) : admin_url( 'themes.php' ),
					'short' => true,
				);

				// Add to attachments.
				$attachments[] = array(
					'fallback'      => $general_message,
					'text'          => wp_trim_words( strip_tags( $item_desc ), 30, '...' ),
					'title'         => $item_title,
					'title_link'    => $item_uri,
					'author_name'   => $author_name,
					'author_link'   => $author_uri,
					'fields'        => $fields,
				);

			}

			// Send each webhook.
			$this->send_outgoing_webhooks( $notification_event, $outgoing_webhooks, $payload, $attachments, array(
				'themes' => $update_themes->response,
			));

		}

		// Store timestamp in transient so it only sends the update notice once a week.
		if ( rock_the_slackbot()->is_network_active ) {
			set_site_transient( 'rock_the_slack_theme_update_available', time(), WEEK_IN_SECONDS );
		} else {
			set_transient( 'rock_the_slack_theme_update_available', time(), WEEK_IN_SECONDS );
		}

	}

	/**
	 * Sends a notification to Slack when
	 * core, plugins, or themes are updated.
	 *
	 * Fires when the bulk upgrader process is complete.
	 *
	 * In $upgrade_info:
	 * 'action' is always 'update'
	 * 'type': 'plugin', 'theme' or 'core'
	 * 'bulk': will be boolean true if true, might not always exist
	 * 'plugins': will hold array of plugins being updated
	 * 'themes': will hold array of themes being updated
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param   Plugin_Upgrader - $upgrader
	 * 		upgrader instance: Plugin_Upgrader, Theme_Upgrader or Core_Upgrade
	 * @param   array - $upgrade_info - Array of bulk item update data.
	 *     @type string $action   Type of action. Default 'update'.
	 *     @type string $type     Type of update process. Accepts 'plugin', 'theme', or 'core'.
	 *     @type bool   $bulk     Whether the update process is a bulk update. Default true.
	 *     @type array  $packages Array of plugin, theme, or core packages to update.
	 * @return  bool - returns false if nothing happened
	 */
	public function upgrade_notification( $upgrader, $upgrade_info ) {
		global $wp_version;

		// Make sure the action is update.
		if ( ! ( ! empty( $upgrade_info['action'] ) && 'update' == strtolower( $upgrade_info['action'] ) ) ) {
			return false;
		}

		// Make sure we have a valid type.
		$upgrade_type = ! empty( $upgrade_info['type'] ) ? strtolower( $upgrade_info['type'] ) : false;
		if ( ! in_array( $upgrade_type, array( 'core', 'plugin', 'theme' ) ) ) {
			return false;
		}

		// Which event are we processing?
		$notification_event = "{$upgrade_type}_updated";

		// Get the outgoing webhooks.
		$outgoing_webhooks = $this->get_outgoing_webhooks( $notification_event );

		// If we have no webhooks, then there's no point.
		if ( ! $outgoing_webhooks ) {
			return false;
		}

		// Is this a bulk upgrade?
		$is_bulk = ! empty( $upgrade_info['bulk'] ) && $upgrade_info['bulk'] > 0 ? true : false;

		// Get upgrade items (if applicable).
		$upgrade_item_index = $is_bulk ? "{$upgrade_type}s" : $upgrade_type;
		$upgrade_items = ( 'core' == $upgrade_type ) ? false : ( ! empty( $upgrade_info[ $upgrade_item_index ] ) ? $upgrade_info[ $upgrade_item_index ] : false );

		// Convert to array.
		if ( ! is_array( $upgrade_items ) ) {
			$upgrade_items = explode( ',', $upgrade_items );
		}

		// Get the pre upgrade info.
		if ( rock_the_slackbot()->is_network_active ) {
			$pre_upgrade_info = get_site_transient( 'rock_the_slackbot_pre_upgrade_information' );
			delete_site_transient( 'rock_the_slackbot_pre_upgrade_information' );
		} else {
			$pre_upgrade_info = get_transient( 'rock_the_slackbot_pre_upgrade_information' );
			delete_transient( 'rock_the_slackbot_pre_upgrade_information' );
		}

		// Get current user.
		$current_user = wp_get_current_user();

		// Get site URL and name.
		$site_url = get_bloginfo( 'url' );
		$site_name = get_bloginfo( 'name' );

		// Create general message for the notification.
		$general_message = null;

		// Set the message for the different updates.
		if ( 'core' == $upgrade_type ) {
			$general_message = "WordPress {$upgrade_type} has";
		} else {

			// Start the message.
			$general_message = "The following WordPress {$upgrade_type}";

			// Depending on how many are being updated.
			if ( count( $upgrade_items ) == 1 ) {
				$general_message .= ' has';
			} else {
				$general_message .= 's have';
			}
		}

		// Add on to the message.
		$general_message .= ' been updated';

		// Include the core version.
		if ( 'core' == $upgrade_type ) {
			if ( ! empty( $pre_upgrade_info['core']['version'] ) ) {
				$general_message .= ' from version ' . $pre_upgrade_info['core']['version'];
			}
			$general_message .= ' to ' . $wp_version;
		}

		// Add the use who updated.
		if ( false !== $current_user && ! empty( $current_user->display_name ) ) {
			$general_message .= ' by ' . $current_user->display_name;
		}

		// Finish the message.
		$general_message .= ' on the ' . $site_name . ' website at <' . $site_url . '>.';

		// Loop through each webhook and send the notification.
		foreach ( $outgoing_webhooks as $hook ) {

			// We must have a webhook URL.
			if ( empty( $hook['webhook_url'] ) ) {
				continue;
			}

			// Start creating the payload.
			$payload = array(
				'text' => $general_message,
			);

			// Create attachments.
			$attachments = array();

			// Will hold the event info we want to pass to the filters.
			$event_args = array();

			/*
			 * Get event info for core upgrade type.
			 *
			 * Add some more info for plugins and themes.
			 */
			if ( 'core' != $upgrade_type ) {

				// Store version numbers for the filters.
				$event_args['current_version'] = $wp_version;
				$event_args['old_version'] = $pre_upgrade_info['core']['version'];

			} elseif ( 'core' != $upgrade_type ) {

				// Store the upgrade item(s) for themes and plugins.
				$event_args[ $upgrade_type ] = $upgrade_items;

				// Add upgrade items to the fields.
				if ( $upgrade_items ) {
					foreach ( $upgrade_items as $item ) {

						// Get pre upgrade info.
						$item_pre_upgrade_info = isset( $pre_upgrade_info ) && ! empty( $pre_upgrade_info[ $item ] ) ? $pre_upgrade_info[ $item ] : false;

						/*
						 * Get item data.
						 *
						 * @TODO:
						 *  For some reason wp_get_theme() is picking up the old version instead of new one.
						 */
						$item_data = ( 'plugin' == $upgrade_type ) ? get_plugin_data( WP_CONTENT_DIR . '/plugins/' . $item ) : wp_get_theme( $item );

						// Set item title.
						$item_title = ( 'plugin' == $upgrade_type ) ? ( ! empty( $item_data['Name'] ) ? $item_data['Name'] : false ) : $item_data->get( 'Name' );

						// Set item URI.
						$item_uri = ( 'plugin' == $upgrade_type ) ? ( ! empty( $item_data['PluginURI'] ) ? $item_data['PluginURI'] : false ) : $item_data->get( 'ThemeURI' );

						// Set item description.
						$item_desc = ( 'plugin' == $upgrade_type ) ? ( ! empty( $item_data['Description'] ) ? strip_tags( html_entity_decode( $item_data['Description'] ) ) : false ) : $item_data->get( 'Description' );

						// Set item author name.
						$author_name = ( 'plugin' == $upgrade_type ) ? ( ! empty( $item_data['AuthorName'] ) ? $item_data['AuthorName'] : false ) : $item_data->get( 'Author' );

						// Set item author URI.
						$author_uri = ( 'plugin' == $upgrade_type ) ? ( ! empty( $item_data['AuthorURI'] ) ? $item_data['AuthorURI'] : false ) : $item_data->get( 'AuthorURI' );

						// Set item version.
						$item_version = ( 'plugin' == $upgrade_type ) ? ( ! empty( $item_data['Version'] ) ? $item_data['Version'] : false ) : $item_data->get( 'Version' );

						// Get previous version.
						$previous_version = ( 'plugin' == $upgrade_type ) ? ( ! empty( $item_pre_upgrade_info['Version'] ) ? $item_pre_upgrade_info['Version'] : false ) : $item_pre_upgrade_info->get( 'Version' );

						// Start creating the fields.
						$fields = array();

						// Add version.
						$fields[] = array(
							'title' => __( 'Current Version', 'rock-the-slackbot' ),
							'value' => $item_version,
							'short' => true,
						);

						// Add previous version.
						if ( $previous_version ) {
							$fields[] = array(
								'title' => __( 'Previous Version', 'rock-the-slackbot' ),
								'value' => $previous_version,
								'short' => true,
							);
						}

						// Add plugin URI.
						$fields[] = array(
							'title' => sprintf( __( 'Manage %s', 'rock-the-slackbot' ), ucwords( $upgrade_type ) . 's' ),
							'value' => is_multisite() ? network_admin_url( "{$upgrade_type}s.php" ) : admin_url( "{$upgrade_type}s.php" ),
							'short' => true,
						);

						// Add to attachments.
						$attachments[] = array(
							'fallback'      => $general_message,
							'text'          => wp_trim_words( strip_tags( $item_desc ), 30, '...' ),
							'title'         => $item_title,
							'title_link'    => $item_uri,
							'author_name'   => $author_name,
							'author_link'   => $author_uri,
							'fields'        => $fields,
						);

					}
				}
			}

			// Send each webhook.
			$this->send_outgoing_webhooks( $notification_event, $outgoing_webhooks, $payload, $attachments );

		}

	}

	/**
	 * Sends a notification to Slack when an attachment is added.
	 *
	 * Fires once an attachment has been added.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param   int - $post_id - The attachment post ID.
	 * @return  bool - returns false if nothing happened
	 */
	public function add_attachment_notification( $post_id ) {

		// Which event are we processing?
		$notification_event = 'add_attachment';

		// Get the outgoing webhooks.
		$outgoing_webhooks = $this->get_outgoing_webhooks( $notification_event );

		// If we have no webhooks, then there's no point.
		if ( ! $outgoing_webhooks ) {
			return false;
		}

		// Get attachment post data.
		$attachment_post = get_post( $post_id );

		// Get mime type.
		$attachment_mime_type = get_post_mime_type( $post_id );

		// Get current user.
		$current_user = wp_get_current_user();

		// Get site URL and name.
		$site_url = get_bloginfo( 'url' );
		$site_name = get_bloginfo( 'name' );

		// Create general message for the notification.
		$general_message = $current_user->display_name . ' added an attachment to the ' . $site_name . ' website at <' . $site_url . '>.';

		// Start creating the payload.
		$payload = array(
			'text' => $general_message,
		);

		// Do we have an image URL?
		$attachment_image = wp_get_attachment_image_src( $post_id, 'medium' );
		$attachment_image_url = ! empty( $attachment_image[0] ) ? $attachment_image[0] : '';

		// Start creating the fields.
		$fields = array(
			array(
				'title' => 'Attachment Type',
				'value' => $attachment_mime_type,
				'short' => true,
			),
			array(
				'title' => 'Edit the Attachment',
				'value' => get_edit_post_link( $post_id ),
				'short' => true,
			),
			array(
				'title' => 'View the Attachment',
				'value' => get_permalink( $post_id ),
				'short' => true,
			),
		);

		// If has parent, it means it was added to a specific post.
		if ( $attachment_post->post_parent > 0 ) {
			$fields[] = array(
				'title' => 'Added To',
				'value' => get_permalink( $attachment_post->post_parent ),
				'short' => true,
			);
		}

		// Add caption.
		if ( ! empty( $attachment_post->post_excerpt ) ) {
			$fields[] = array(
				'title' => 'Caption',
				'value' => wp_trim_words( strip_tags( $attachment_post->post_excerpt ), 30, '...' ),
				'short' => true,
			);
		}

		// Add alt text.
		if ( $alt_text = get_post_meta( $post_id, '_wp_attachment_image_alt', true ) ) {
			$fields[] = array(
				'title' => 'Alt Text',
				'value' => wp_trim_words( strip_tags( $alt_text ), 30, '...' ),
				'short' => true,
			);
		}

		// Create attachment.
		$attachments = array(
			array(
				'fallback'      => $general_message,
				'text'          => wp_trim_words( strip_tags( $attachment_post->post_content ), 30, '...' ),
				'title'         => get_the_title( $post_id ),
				'title_link'    => get_permalink( $post_id ),
				'author_name'   => $current_user->display_name,
				'author_link'   => get_author_posts_url( $current_user->ID ),
				'author_icon'   => get_avatar_url( $current_user->ID, 32 ),
				'fields'        => $fields,
				'image_url'     => $attachment_image_url,
			),
		);

		// Send each webhook.
		$this->send_outgoing_webhooks( $notification_event, $outgoing_webhooks, $payload, $attachments, array(
			'attachment_post' => $attachment_post,
		));

	}

	/**
	 * Sends a notification to Slack when an attachment is updated.
	 *
	 * Fires once an existing attachment has been updated.
	 *
	 * @TODO set it up so it shares what exactly was edited.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param   int - $post_id - The attachment post ID.
	 * @return  bool - returns false if nothing happened
	 */
	public function edit_attachment_notification( $post_id ) {

		// Which event are we processing?
		$notification_event = 'edit_attachment';

		// Get the outgoing webhooks.
		$outgoing_webhooks = $this->get_outgoing_webhooks( $notification_event );

		// If we have no webhooks, then there's no point.
		if ( ! $outgoing_webhooks ) {
			return false;
		}

		// Get attachment post data.
		$attachment_post = get_post( $post_id );

		// Get mime type.
		$attachment_mime_type = get_post_mime_type( $post_id );

		// Get current user.
		$current_user = wp_get_current_user();

		// Get site URL and name.
		$site_url = get_bloginfo( 'url' );
		$site_name = get_bloginfo( 'name' );

		// Create general message for the notification.
		$general_message = $current_user->display_name . ' edited an attachment on the ' . $site_name . ' website at <' . $site_url . '>.';

		// Start creating the payload.
		$payload = array(
			'text' => $general_message,
		);

		// Do we have an image URL?
		$attachment_image = wp_get_attachment_image_src( $post_id, 'medium' );
		$attachment_image_url = ! empty( $attachment_image[0] ) ? $attachment_image[0] : '';

		// Start creating the fields.
		$fields = array(
			array(
				'title' => 'Attachment Type',
				'value' => $attachment_mime_type,
				'short' => true,
			),
			array(
				'title' => 'Edit the Attachment',
				'value' => get_edit_post_link( $post_id ),
				'short' => true,
			),
			array(
				'title' => 'View the Attachment',
				'value' => get_permalink( $post_id ),
				'short' => true,
			),
		);

		// If has parent, it means it was added to a specific post.
		if ( $attachment_post->post_parent > 0 ) {
			$fields[] = array(
				'title' => 'Added To',
				'value' => get_permalink( $attachment_post->post_parent ),
				'short' => true,
			);
		}

		// Add caption.
		if ( ! empty( $attachment_post->post_excerpt ) ) {
			$fields[] = array(
				'title' => 'Caption',
				'value' => wp_trim_words( strip_tags( $attachment_post->post_excerpt ), 30, '...' ),
				'short' => true,
			);
		}

		// Add alt text.
		if ( $alt_text = get_post_meta( $post_id, '_wp_attachment_image_alt', true ) ) {
			$fields[] = array(
				'title' => 'Alt Text',
				'value' => wp_trim_words( strip_tags( $alt_text ), 30, '...' ),
				'short' => true,
			);
		}

		// Create attachment.
		$attachments = array(
			array(
				'fallback'      => $general_message,
				'text'          => wp_trim_words( strip_tags( $attachment_post->post_content ), 30, '...' ),
				'title'         => get_the_title( $post_id ),
				'title_link'    => get_permalink( $post_id ),
				'author_name'   => $current_user->display_name,
				'author_link'   => get_author_posts_url( $current_user->ID ),
				'author_icon'   => get_avatar_url( $current_user->ID, 32 ),
				'fields'        => $fields,
				'image_url'     => $attachment_image_url,
			),
		);

		// Send each webhook.
		$this->send_outgoing_webhooks( $notification_event, $outgoing_webhooks, $payload, $attachments, array(
			'attachment_post' => $attachment_post,
		));

	}

	/**
	 * Sends a notification to Slack when an attachment is deleted.
	 *
	 * Fires before an attachment is deleted, at the start of wp_delete_attachment()
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param   int - $post_id - The attachment post ID.
	 * @return  bool - returns false if nothing happened
	 */
	public function delete_attachment_notification( $post_id ) {

		// Which event are we processing?
		$notification_event = 'delete_attachment';

		// Get the outgoing webhooks.
		$outgoing_webhooks = $this->get_outgoing_webhooks( $notification_event );

		// If we have no webhooks, then there's no point.
		if ( ! $outgoing_webhooks ) {
			return false;
		}

		// Get attachment post data.
		$attachment_post = get_post( $post_id );

		// Get mime type.
		$attachment_mime_type = get_post_mime_type( $post_id );

		// Get current user.
		$current_user = wp_get_current_user();

		// Get site URL and name.
		$site_url = get_bloginfo( 'url' );
		$site_name = get_bloginfo( 'name' );

		// Create general message for the notification.
		$general_message = $current_user->display_name . ' deleted an attachment on the ' . $site_name . ' website at <' . $site_url . '>.';

		// Start creating the payload.
		$payload = array(
			'text' => $general_message,
		);

		// Start creating the fields.
		$fields = array(
			array(
				'title' => 'Attachment Type',
				'value' => $attachment_mime_type,
				'short' => true,
			),
		);

		// If has parent, it means it was added to a specific post.
		if ( $attachment_post->post_parent > 0 ) {
			$fields[] = array(
				'title' => 'Added To',
				'value' => get_permalink( $attachment_post->post_parent ),
				'short' => true,
			);
		}

		// Add caption.
		if ( ! empty( $attachment_post->post_excerpt ) ) {
			$fields[] = array(
				'title' => 'Caption',
				'value' => wp_trim_words( strip_tags( $attachment_post->post_excerpt ), 30, '...' ),
				'short' => true,
			);
		}

		// Add alt text.
		if ( $alt_text = get_post_meta( $post_id, '_wp_attachment_image_alt', true ) ) {
			$fields[] = array(
				'title' => 'Alt Text',
				'value' => wp_trim_words( strip_tags( $alt_text ), 30, '...' ),
				'short' => true,
			);
		}

		// Create attachment.
		$attachments = array(
			array(
				'fallback'      => $general_message,
				'text'          => wp_trim_words( strip_tags( $attachment_post->post_content ), 30, '...' ),
				'title'         => get_the_title( $post_id ),
				'title_link'    => get_permalink( $post_id ),
				'author_name'   => $current_user->display_name,
				'author_link'   => get_author_posts_url( $current_user->ID ),
				'author_icon'   => get_avatar_url( $current_user->ID, 32 ),
				'fields'        => $fields,
			),
		);

		// Send each webhook.
		$this->send_outgoing_webhooks( $notification_event, $outgoing_webhooks, $payload, $attachments, array(
			'attachment_post' => $attachment_post,
		));

	}

	/**
	 * Sends a notification to Slack when a post has been updated.
	 *
	 * Fires once an existing post has been updated.
	 *
	 * Does not handle menu items. They have their own hook.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param   int - $post_id - The post ID.
	 * @param   WP_Post - $post_after - Post object following the update.
	 * @param   WP_Post - $post_before - Post object before the update.
	 * @return  bool - returns false if nothing happened
	 */
	public function updated_post_notification( $post_id, $post_after, $post_before ) {

		// Only send updates for published content.
		if ( 'publish' != $post_after->post_status ) {
			return false;
		}

		// Don't send content updates for the following post types.
		if ( in_array( $post_after->post_type, array( 'nav_menu_item' ) ) ) {
			return false;
		}

		// See if our current post's status was transitioned.
		$trans_post_id = wp_cache_get( 'transition_post_status_notification', 'rock_the_slackbot' );

		// Clear out the cache.
		if ( $trans_post_id > 0 ) {

			// Delete the cache.
			wp_cache_delete( 'transition_post_status_notification', 'rock_the_slackbot' );

			// If already sent notification, then get out of here.
			if ( $trans_post_id == $post_id ) {
				return false;
			}
		}

		// Which event are we processing?
		$notification_event = 'post_updated';

		// Get the outgoing webhooks.
		$outgoing_webhooks = $this->get_outgoing_webhooks( $notification_event, array( 'post_type' => $post_after->post_type ) );

		// If we have no webhooks, then there's no point.
		if ( ! $outgoing_webhooks ) {
			return false;
		}

		// Get current user.
		$current_user = wp_get_current_user();

		// Get site URL and name.
		$site_url = get_bloginfo( 'url' );
		$site_name = get_bloginfo( 'name' );

		// Get post type info.
		$post_type_object = get_post_type_object( $post_after->post_type );

		// Create general message for the notification.
		$general_message = sprintf( __( '%1$s updated the following content on the %2$s website at <%3$s>.', 'rock-the-slackbot' ),
			$current_user->display_name,
			$site_name,
			$site_url
		);

		// Start creating the payload.
		$payload = array(
			'text' => $general_message,
		);

		// Start creating the fields.
		$fields = array(
			array(
				'title' => __( 'Content Author', 'rock-the-slackbot' ),
				'value' => get_the_author_meta( 'display_name', $post_after->post_author ),
				'short' => true,
			),
			array(
				'title' => __( 'Edit the Content', 'rock-the-slackbot' ),
				'value' => get_edit_post_link( $post_after->ID ),
				'short' => true,
			),
		);

		// Get this post's latest revision.
		$post_revisions = ( $actual_post_revisions = wp_get_post_revisions( $post_after->ID, array( 'posts_per_page' => 1 ) ) ) && ! empty( $actual_post_revisions ) && is_array( $actual_post_revisions ) ? array_shift( $actual_post_revisions ) : false;

		// If the post was updated, add the latest revision link.
		if ( ! empty( $post_revisions->ID ) ) {

			// Add "View Latest Revision" URL.
			$fields[] = array(
				'title' => __( 'View Latest Revision', 'rock-the-slackbot' ),
				'value' => isset( $post_revisions ) && ! empty( $post_revisions->ID ) ? add_query_arg( 'revision', $post_revisions->ID, admin_url( 'revision.php' ) ) : null,
				'short' => true,
			);

		}

		// Add current content status.
		$fields[] = array(
			'title' => __( 'Content Status', 'rock-the-slackbot' ),
			'value' => ucfirst( $post_after->post_status ),
			'short' => true,
		);

		// Add the content type.
		$fields[] = array(
			'title' => __( 'Content Type', 'rock-the-slackbot' ),
			'value' => $post_type_object && isset( $post_type_object->labels ) && ! empty( $post_type_object->labels->singular_name ) ? $post_type_object->labels->singular_name : $post_after->post_type,
			'short' => true,
		);

		// Create attachment.
		$attachments = array(
			array(
				'fallback'      => $general_message,
				'text'          => wp_trim_words( strip_tags( $post_after->post_content ), 30, '...' ),
				'title'         => get_the_title( $post_after->ID ),
				'title_link'    => get_permalink( $post_after->ID ),
				'author_name'   => $current_user->display_name,
				'author_link'   => get_author_posts_url( $current_user->ID ),
				'author_icon'   => get_avatar_url( $current_user->ID, 32 ),
				'fields'        => $fields,
			),
		);

		// Send each webhook.
		$this->send_outgoing_webhooks( $notification_event, $outgoing_webhooks, $payload, $attachments, array(
			'post_id'		=> $post_id,
			'post_before'	=> $post_before,
			'post_after'	=> $post_after,
		));

	}

	/**
	 * Sends a notification to Slack when a
	 * post's status is changed.
	 *
	 * The statuses to handle:
	 * publish
	 * future
	 * draft
	 * pending
	 * private
	 * trash
	 * auto-draft
	 * inherit
	 *
	 * Fires when a post is transitioned from one status to another.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param   string - $new_status - the new post status.
	 * @param   string - $old_status - the old post status.
	 * @param   WP_Post - $post - the post object.
	 * @return  bool - returns false if nothing happened
	 */
	public function transition_post_status_notification( $new_status, $old_status, $post ) {

		// Don't worry about if the status is the same.
		if ( $new_status == $old_status ) {
			return false;
		}

		/*
		 * Don't run for the following new statuses.
		 *
		 * Trash is handled elsewhere.
		 */
		if ( in_array( $new_status, array( 'auto-draft', 'inherit', 'trash' ) ) ) {
			return false;
		}

		// Which event are we processing?
		$notification_event = 'post_unpublished';
		switch ( $new_status ) {

			case 'draft':

				// Don't run if set as draft from specific old statuses.
				if ( in_array( $old_status, array( 'draft', 'auto-draft', 'inherit' ) ) ) {
					return false;
				}

				$notification_event = 'post_draft';
				break;

			case 'pending':
				$notification_event = 'post_pending';
				break;

			case 'future':
				$notification_event = 'post_future';
				break;

			case 'publish':
				$notification_event = 'post_published';
				break;

		}

		// Get the outgoing webhooks.
		$outgoing_webhooks = $this->get_outgoing_webhooks( $notification_event, array( 'post_type' => $post->post_type ) );

		// If we have no webhooks, then there's no point.
		if ( ! $outgoing_webhooks ) {
			return false;
		}

		// Get current user.
		$current_user = wp_get_current_user();

		// Get site URL and name.
		$site_url = get_bloginfo( 'url' );
		$site_name = get_bloginfo( 'name' );

		// Get post type info.
		$post_type_object = get_post_type_object( $post->post_type );

		// Will hold the fields for the message.
		$fields = array();

		// Create general message for the notification.
		$general_message = '';

		// Customize the message depending on the event.
		switch ( $notification_event ) {

			case 'post_draft':
				$general_message = sprintf( __( '%1$s drafted the following content on the %2$s website at <%3$s>.', 'rock-the-slackbot' ), $current_user->display_name, $site_name, $site_url );
				break;

			case 'post_pending':
				$general_message = sprintf( __( '%1$s marked the following content for review on the %2$s website at <%3$s>.', 'rock-the-slackbot' ), $current_user->display_name, $site_name, $site_url );
				break;

			case 'post_future':

				// Add the scheduled date as a field.
				$fields[] = array(
					'title' => __( 'Scheduled Date', 'rock-the-slackbot' ),
					'value' => date( 'l, M\. j, Y \a\t g:i a', strtotime( $post->post_date ) ),
					'short' => true,
				);

				// Build the message.
				$general_message = sprintf( __( '%1$s scheduled the following content on the %2$s website at <%3$s>.', 'rock-the-slackbot' ), $current_user->display_name, $site_name, $site_url );

				break;

			case 'post_published':
				$general_message = sprintf( __( '%1$s published the following content on the %2$s website at <%3$s>.', 'rock-the-slackbot' ), $current_user->display_name, $site_name, $site_url );
				break;

			case 'post_unpublished':
				$general_message = sprintf( __( '%1$s unpublished the following content on the %2$s website at <%3$s>.', 'rock-the-slackbot' ), $current_user->display_name, $site_name, $site_url );
				break;

		}

		// Make sure we have a message.
		if ( empty( $general_message ) ) {
			return false;
		}

		// Start creating the payload.
		$payload = array(
			'text' => $general_message,
		);

		// Add the link to edit the content.
		$fields[] = array(
			'title' => __( 'Edit the Content', 'rock-the-slackbot' ),
			'value' => get_edit_post_link( $post->ID ),
			'short' => true,
		);

		// Get this post's latest revision.
		$post_revisions = ( $actual_post_revisions = wp_get_post_revisions( $post->ID, array( 'posts_per_page' => 1 ) ) ) && ! empty( $actual_post_revisions ) && is_array( $actual_post_revisions ) ? array_shift( $actual_post_revisions ) : false;

		// If the post was updated, add the latest revision link.
		if ( ! empty( $post_revisions->ID ) ) {

			// Add "View Latest Revision" URL.
			$fields[] = array(
				'title' => __( 'View Latest Revision', 'rock-the-slackbot' ),
				'value' => isset( $post_revisions ) && ! empty( $post_revisions->ID ) ? add_query_arg( 'revision', $post_revisions->ID, admin_url( 'revision.php' ) ) : null,
				'short' => true,
			);

		}

		// Show old status.
		$fields[] = array(
			'title' => __( 'Old Status', 'rock-the-slackbot' ),
			'value' => ucfirst( $old_status ),
			'short' => true,
		);

		// Add current content status.
		$fields[] = array(
			'title' => __( 'Current Status', 'rock-the-slackbot' ),
			'value' => ucfirst( $new_status ),
			'short' => true,
		);

		// Add the content type.
		$fields[] = array(
			'title' => __( 'Content Type', 'rock-the-slackbot' ),
			'value' => $post_type_object && isset( $post_type_object->labels ) && ! empty( $post_type_object->labels->singular_name ) ? $post_type_object->labels->singular_name : $post->post_type,
			'short' => true,
		);

		// Add the content author.
		$fields[] = array(
			'title' => __( 'Content Author', 'rock-the-slackbot' ),
			'value' => get_the_author_meta( 'display_name', $post->post_author ),
			'short' => true,
		);

		// Create attachment.
		$attachments = array(
			array(
				'fallback'      => $general_message,
				'text'          => wp_trim_words( strip_tags( $post->post_content ), 30, '...' ),
				'title'         => get_the_title( $post->ID ),
				'title_link'    => get_permalink( $post->ID ),
				'author_name'   => $current_user->display_name,
				'author_link'   => get_author_posts_url( $current_user->ID ),
				'author_icon'   => get_avatar_url( $current_user->ID, 32 ),
				'fields'        => $fields,
			),
		);

		// Send each webhook.
		$send_webhooks = $this->send_outgoing_webhooks( $notification_event, $outgoing_webhooks, $payload, $attachments, array(
			'post'				=> $post,
			'old_post_status'	=> $old_status,
			'new_post_status'	=> $new_status,
		));

		// Store cache info if notification was sent.
		if ( true === $send_webhooks ) {

			/*
			 * Store that this post's status is being transitioned.
			 *
			 * Will be referenced in updated_post_notification().
			 */
			wp_cache_set( 'transition_post_status_notification', $post->ID, 'rock_the_slackbot' );

		}

	}


	/**
	 * Sends a notification to Slack when a post
	 * is moved to the trash, which is the step taken
	 * before the post is deleted from the database.
	 *
	 * Fires before a post is sent to the trash.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param   int - $post_id - The post ID.
	 * @return  bool - returns false if nothing happened
	 */
	public function wp_trash_post_notification( $post_id ) {

		// Get the post.
		$post = get_post( $post_id );

		// Which event are we processing?
		$notification_event = 'post_trashed';

		// Get the outgoing webhooks.
		$outgoing_webhooks = $this->get_outgoing_webhooks( $notification_event, array( 'post_type' => $post->post_type ) );

		// If we have no webhooks, then there's no point.
		if ( ! $outgoing_webhooks ) {
			return false;
		}

		// Get current user.
		$current_user = wp_get_current_user();

		// Get site URL and name.
		$site_url = get_bloginfo( 'url' );
		$site_name = get_bloginfo( 'name' );

		// Get post type info.
		$post_type_object = get_post_type_object( $post->post_type );

		// Create general message for the notification.
		$general_message = $current_user->display_name . ' moved content to the trash bin on the ' . $site_name . ' website at <' . $site_url . '>.';

		// Start creating the payload.
		$payload = array(
			'text' => $general_message,
		);

		// Start creating the fields.
		$fields = array(
			array(
				'title' => 'Content Author',
				'value' => get_the_author_meta( 'display_name', $post->post_author ),
				'short' => true,
			),
			array(
				'title' => 'Content Type',
				'value' => $post_type_object && isset( $post_type_object->labels ) && ! empty( $post_type_object->labels->singular_name ) ? $post_type_object->labels->singular_name : $post->post_type,
				'short' => true,
			),
			array(
				'title' => 'View the Trash',
				'value' => add_query_arg( array( 'post_status' => 'trash', 'post_type' => $post->post_type ), admin_url( 'edit.php' ) ),
				'short' => true,
			),
		);

		// Create attachment.
		$attachments = array(
			array(
				'fallback'      => $general_message,
				'text'          => wp_trim_words( strip_tags( $post->post_content ), 30, '...' ),
				'title'         => get_the_title( $post->ID ),
				'title_link'    => get_permalink( $post_id ),
				'author_name'   => $current_user->display_name,
				'author_link'   => get_author_posts_url( $current_user->ID ),
				'author_icon'   => get_avatar_url( $current_user->ID, 32 ),
				'fields'        => $fields,
			),
		);

		// Send each webhook.
		$this->send_outgoing_webhooks( $notification_event, $outgoing_webhooks, $payload, $attachments, array(
			'post' => $post,
		));

	}

	/**
	 * Sends a notification to Slack when a menu item or
	 * post is deleted from the database (not the same as
	 * being sent to the trash).
	 *
	 * Fires immediately before a post is deleted from the database.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param   int - $post_id - The post ID.
	 * @return  bool - returns false if nothing happened
	 */
	public function delete_post_notification( $post_id ) {
		global $wpdb;

		// Get the post.
		$post = get_post( $post_id );

		// Don't run if the following statuses are being deleted.
		if ( in_array( $post->post_status, array( 'auto-draft' ) ) ) {
			return false;
		}

		// Don't run if the following post types are being deleted.
		if ( in_array( $post->post_type, array( 'revision' ) ) ) {
			return false;
		}

		// Is this a menu item?
		$is_menu_item = strcasecmp( 'nav_menu_item', $post->post_type ) == 0;

		// Which event are we processing?
		$notification_event = $is_menu_item ? 'menu_item_deleted' : 'post_deleted';

		// Get the outgoing webhooks.
		$outgoing_webhooks = $this->get_outgoing_webhooks( $notification_event, array( 'post_type' => $post->post_type ) );

		// If we have no webhooks, then there's no point.
		if ( ! $outgoing_webhooks ) {
			return false;
		}

		// Get current user.
		$current_user = wp_get_current_user();

		// Get site URL and name.
		$site_url = get_bloginfo( 'url' );
		$site_name = get_bloginfo( 'name' );

		// Get post type info.
		$post_type_object = get_post_type_object( $post->post_type );

		// Create general message for the notification.
		$general_message = null;

		// If we have a user...
		if ( ! empty( $current_user->display_name ) ) {
			$general_message .= $current_user->display_name . ' deleted' . ( $is_menu_item ? ' a menu item' : ' content' );
		} else {
			$general_message .= ( $is_menu_item ? 'A menu item' : 'Content' ) . ' has been deleted';
		}

		// Finish the message.
		$general_message .= ' on the ' . $site_name . ' website at <' . $site_url . '>.';

		// Start creating the payload.
		$payload = array(
			'text' => $general_message,
		);

		// Start creating the fields.
		$fields = array();

		// Will hold the event info we want to pass to the filters.
		$event_args = array();

		// Add menu information.
		if ( $is_menu_item ) {

			// Get menu.
			$menu = ( $menus = wp_get_object_terms( $post_id, 'nav_menu' ) ) && is_array( $menus ) ? array_shift( $menus ) : false;

			// Get menu item info.
			$menu_item_type = get_post_meta( $post_id, '_menu_item_type', true );

			// Get menu item label AND URL.
			$menu_item_label = null;
			$menu_item_url = null;
			$menu_item_post_type = null;

			if ( strcasecmp( 'custom', $menu_item_type ) == 0 ) {

				// Get custom label.
				$menu_item_label = $post->post_title;

				// Get custom URL.
				$menu_item_url = get_post_meta( $post_id, '_menu_item_url', true );

			} elseif ( strcasecmp( 'taxonomy', $menu_item_type ) == 0 ) {

				// Get object ID and post title.
				if ( $menu_item_object_id = get_post_meta( $post_id, '_menu_item_object_id', true ) ) {

					// Get term name and taxonomy.
					$menu_item_term_data = $wpdb->get_row( $wpdb->prepare( "SELECT terms.name, terms.slug, term_tax.taxonomy FROM {$wpdb->terms} terms INNER JOIN {$wpdb->term_taxonomy} term_tax on term_tax.term_id = terms.term_id WHERE terms.term_id = %d", $menu_item_object_id ) );

					// Set label as name.
					$menu_item_label = $menu_item_term_data->name;

					// Get URL.
					if ( ( $menu_item_term_link = get_term_link( $menu_item_term_data->slug, $menu_item_term_data->taxonomy ) )
						&& ! is_wp_error( $menu_item_term_link ) ) {
						$menu_item_url = $menu_item_term_link;
					}
				}
			} elseif ( strcasecmp( 'post_type', $menu_item_type ) == 0 ) {

				// Get object ID and post title.
				if ( $menu_item_object_id = get_post_meta( $post_id, '_menu_item_object_id', true ) ) {

					// Get title as label.
					$menu_item_label = get_the_title( $menu_item_object_id );

					// Get permalink as URL.
					$menu_item_url = get_permalink( $menu_item_object_id );

					// Get post type.
					if ( $menu_item_post_type = get_post_type( $menu_item_object_id ) ) {

						// Get post type info.
						$menu_item_post_type_object = get_post_type_object( $menu_item_post_type );
						if ( isset( $menu_item_post_type_object->labels )
						     && ! empty( $menu_item_post_type_object->labels->singular_name ) ) {

							// Get singular name label.
							$menu_item_post_type = $menu_item_post_type_object->labels->singular_name;

						}
					}
				}
			}

			// Create the fields.
			$fields = array(
				array(
					'title' => 'Menu',
					'value' => ! empty( $menu->name ) ? $menu->name : null,
					'short' => true,
				),
				array(
					'title' => 'Edit the Menu',
					'value' => ! empty( $menu->term_id ) ? add_query_arg( array( 'action' => 'edit', 'menu' => $menu->term_id ), admin_url( 'nav-menus.php' ) ) : null,
					'short' => true,
				),
				array(
					'title' => 'Menu Item Label',
					'value' => $menu_item_label,
					'short' => true,
				),
				array(
					'title' => 'Menu Item URL',
					'value' => $menu_item_url,
					'short' => true,
				),
				array(
					'title' => 'Menu Item Type',
					'value' => ( 'post_type' == $menu_item_type ) ? $menu_item_post_type : ucwords( str_replace( '_', ' ', $menu_item_type ) ),
					'short' => true,
				),
			);

			// Add event info to pass to filters.
			$event_args['menu'] = $menu;
			$event_args['menu_item_id'] = $post_id;

		} else {

			// Add the content author and type.
			$fields = array_merge( array(
				array(
					'title' => 'Content Author',
					'value' => get_the_author_meta( 'display_name', $post->post_author ),
					'short' => true,
				),
				array(
					'title' => 'Content Type',
					'value' => $post_type_object && isset( $post_type_object->labels ) && ! empty( $post_type_object->labels->singular_name ) ? $post_type_object->labels->singular_name : $post->post_type,
					'short' => true,
				),
			));

			// Add event info to pass to filters.
			$event_args['post'] = $post;

		}

		// Create attachment.
		$attachments = array(
			array(
				'fallback'      => $general_message,
				'text'          => wp_trim_words( strip_tags( $post->post_content ), 30, '...' ),
				'title'         => get_the_title( $post->ID ),
				'title_link'    => get_permalink( $post_id ),
				'author_name'   => $current_user->display_name,
				'author_link'   => get_author_posts_url( $current_user->ID ),
				'author_icon'   => get_avatar_url( $current_user->ID, 32 ),
				'fields'        => $fields,
			),
		);

		// Send each webhook.
		$this->send_outgoing_webhooks( $notification_event, $outgoing_webhooks, $payload, $attachments, $event_args );

	}

	/**
	 * Sends a notification to Slack when
	 * there is a 404 error.
	 *
	 * Fires once the WordPress environment has been set up.
	 *
	 * @access	public
	 * @since	1.0.0
	 * @param	WP - $wp - Current WordPress environment instance (passed by reference).
	 * @return	bool - returns false if nothing happened
	 */
	public function is_404_notification( $wp ) {
		global $wp_query;

		// Only need to run if there is a 404 error.
		if ( ! is_404() ) {
			return false;
		}

		// Build the current URL.
		$current_url  = ( 'on' != $_SERVER['HTTPS'] ? 'http://' : 'https://' ) . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

		// See if it has already been sent.
		$sent_notification = wp_cache_get( 'sent_404_notification', 'rock_the_slackbot' );
		if ( ! empty( $sent_notification ) ) {

			// Delete the cache.
			wp_cache_delete( 'sent_404_notification', 'rock_the_slackbot' );

			// If already sent notification, then get out of here.
			if ( $sent_notification == $current_url ) {
				return false;
			}
		}

		// Which event are we processing?
		$notification_event = 'is_404';

		// Get the outgoing webhooks.
		$outgoing_webhooks = $this->get_outgoing_webhooks( $notification_event );

		// If we have no webhooks, then there's no point.
		if ( ! $outgoing_webhooks ) {
			return false;
		}

		// Get current user.
		$current_user = wp_get_current_user();

		// Get site URL and name.
		$site_url = get_bloginfo( 'url' );
		$site_name = get_bloginfo( 'name' );

		// Create general message for the notification.
		$general_message = 'The following URL threw a 404 error on the ' . $site_name . ' website at <' . $site_url . '>.';

		// Start creating the payload.
		$payload = array(
			'text' => $general_message,
		);

		// Will hold the event info we want to pass to the filters.
		$event_args = array( 'url' => $current_url );

		// Create the fields.
		$fields = array(
			array(
				'title' => __( 'URL', 'rock-the-slackbot' ),
				'value' => $current_url,
				'short' => true,
			),
		);

		// If there's a referer.
		if ( $referer = wp_get_referer() ) {

			// Store event info for filters.
			$event_args['referer'] = $referer;

			// Store for fields.
			$fields[] = array(
				'title' => __( 'Referer', 'rock-the-slackbot' ),
				'value' => $referer,
				'short' => true,
			);

		}

		// If there's an IP address.
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {

			// Store event info for filters.
			$event_args['ip_address'] = $_SERVER['REMOTE_ADDR'];

			// Store for fields.
			$fields[] = array(
				'title' => __( 'IP address', 'rock-the-slackbot' ),
				'value' => $_SERVER['REMOTE_ADDR'],
				'short' => true,
			);

		}

		// If there's a user.
		if ( ! empty( $current_user->display_name ) ) {
			$fields[] = array(
				'title' => __( 'Current User', 'rock-the-slackbot' ),
				'value' => $current_user->display_name,
				'short' => true,
			);
		}

		// If there's a user agent.
		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {

			// Store event info for filters.
			$event_args['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

			// Store for fields.
			$fields[] = array(
				'title' => __( 'User Agent', 'rock-the-slackbot' ),
				'value' => $_SERVER['HTTP_USER_AGENT'],
				'short' => false,
			);

		}

		// Add WordPress query.
		$wp_query_vars = array_filter( $wp_query->query );
		if ( ! empty( $wp_query_vars ) ) {

			// Build query string.
			$wp_query_string = build_query( preg_replace( '/[\s]{2,}/i', ' ', $wp_query_vars ) );

			// Store event info for filters.
			$event_args['wp_query'] = $wp_query_vars;

			// Store for fields.
			$fields[] = array(
				'title' => sprintf( __( '%s Query', 'rock-the-slackbot' ), 'WordPress' ),
				'value' => $wp_query_string,
				'short' => false,
			);

		}

		// Add the MySQL request.
		if ( ! empty( $wp_query->request ) ) {

			// Build request.
			$mysql_request = preg_replace( '/[\s]{2,}/i', ' ', $wp_query->request );

			// Store event info for filters.
			$event_args['mysql_request'] = $mysql_request;

			// Store for fields.
			$fields[] = array(
				'title' => sprintf( __( '%s Request', 'rock-the-slackbot' ), 'MySQL' ),
				'value' => $mysql_request,
				'short' => false,
			);

		}

		// Create attachment.
		$attachments = array(
			array(
				'fallback'      => $general_message,
				'text'          => null,
				'title'         => null,
				'title_link'    => null,
				'author_name'   => $current_user ? $current_user->display_name : null,
				'author_link'   => $current_user ? get_author_posts_url( $current_user->ID ) : null,
				'author_icon'   => $current_user ? get_avatar_url( $current_user->ID, 32 ) : null,
				'fields'        => $fields,
			),
		);

		// Send each webhook.
		$send_webhooks = $this->send_outgoing_webhooks( $notification_event, $outgoing_webhooks, $payload, $attachments, $event_args );

		// Store cache info if notification was sent.
		if ( true === $send_webhooks ) {

			// Store that a 404 notification has been sent so we don't send twice.
			wp_cache_set( 'sent_404_notification', $current_url, 'rock_the_slackbot' );

		}
	}

	/**
	 * Sends a notification to Slack when a
	 * new user has been added.
	 *
	 * Fires immediately after a new user is registered.
	 *
	 * @access	public
	 * @since	1.0.0
	 * @param	int - $user_id - the User ID.
	 * @return	bool - returns false if nothing happened
	 */
	public function user_added_notification( $user_id ) {

		// Which event are we processing?
		$notification_event = 'user_added';

		// Get the outgoing webhooks.
		$outgoing_webhooks = $this->get_outgoing_webhooks( $notification_event );

		// If we have no webhooks, then there's no point.
		if ( ! $outgoing_webhooks ) {
			return false;
		}

		// Get current user.
		$current_user = wp_get_current_user();

		// Get site URL and name.
		$site_url = get_bloginfo( 'url' );
		$site_name = get_bloginfo( 'name' );

		// Get user data.
		$new_user_data = get_userdata( $user_id );
		$new_user_display_name = get_the_author_meta( 'display_name', $user_id );

		// Create general message for the notification.
		$general_message = sprintf( __( '%1$s added the following user to the %2$s website at <%3$s>.', 'rock-the-slackbot' ),
			$current_user->display_name,
			$site_name,
			$site_url
		);

		// Start creating the payload.
		$payload = array(
			'text' => $general_message,
		);

		// Start creating the fields.
		$fields = array(
			array(
				'title' => __( 'User Login', 'rock-the-slackbot' ),
				'value' => get_the_author_meta( 'user_login', $user_id ),
				'short' => true,
			),
			array(
				'title' => __( 'User Email', 'rock-the-slackbot' ),
				'value' => get_the_author_meta( 'user_email', $user_id ),
				'short' => true,
			),
		);

		// Add user roles.
		if ( ! empty( $new_user_data->roles ) ) {

			// Get role info.
			$all_roles = wp_roles()->roles;

			// Build new array of roles.
			$roles = array();
			foreach ( $new_user_data->roles as $role ) {
				if ( array_key_exists( $role, $all_roles ) ) {
					$roles[] = $all_roles[ $role ]['name'];
				} else {
					$roles[] = $role;
				}
			}

			// Add to fields.
			$fields[] = array(
				'title' => __( 'User Role(s)', 'rock-the-slackbot' ),
				'value' => implode( ', ', $roles ),
				'short' => true,
			);

		}

		// Create attachment.
		$attachments = array(
			array(
				'fallback'      => $general_message,
				'text'          => wp_trim_words( strip_tags( get_the_author_meta( 'description', $user_id ) ), 30, '...' ),
				'author_name'   => $new_user_display_name,
				'author_link'   => get_author_posts_url( $user_id ),
				'author_icon'   => get_avatar_url( $user_id, 32 ),
				'fields'        => $fields,
			),
		);

		// Send each webhook.
		$this->send_outgoing_webhooks( $notification_event, $outgoing_webhooks, $payload, $attachments, array(
			'user' => $new_user_data,
		));

	}

	/**
	 * Sends a notification to Slack when
	 * a user has been deleted.
	 *
	 * Fires immediately before a user is deleted from the database.
	 *
	 * @access	public
	 * @since	1.0.0
	 * @param	int - $user_id - the User ID.
	 * @param	int|null $reassign ID of the user to reassign posts and links to.
	 *          	Default null, for no reassignment.
	 * @return	bool - returns false if nothing happened
	 */
	public function user_deleted_notification( $user_id, $reassign ) {

		// Which event are we processing?
		$notification_event = 'user_deleted';

		// Get the outgoing webhooks.
		$outgoing_webhooks = $this->get_outgoing_webhooks( $notification_event );

		// If we have no webhooks, then there's no point.
		if ( ! $outgoing_webhooks ) {
			return false;
		}

		// Get current user.
		$current_user = wp_get_current_user();

		// Get site URL and name.
		$site_url = get_bloginfo( 'url' );
		$site_name = get_bloginfo( 'name' );

		// Get user data.
		$deleted_user_data = get_userdata( $user_id );
		$deleted_user_display_name = get_the_author_meta( 'display_name', $user_id );

		// Create general message for the notification.
		$general_message = sprintf( __( '%1$s deleted the following user from the %2$s website at <%3$s>.', 'rock-the-slackbot' ),
			$current_user->display_name,
			$site_name,
			$site_url
		);

		// Start creating the payload.
		$payload = array(
			'text' => $general_message,
		);

		// Start creating the fields.
		$fields = array(
			array(
				'title' => __( 'User Login', 'rock-the-slackbot' ),
				'value' => get_the_author_meta( 'user_login', $user_id ),
				'short' => true,
			),
			array(
				'title' => __( 'User Email', 'rock-the-slackbot' ),
				'value' => get_the_author_meta( 'user_email', $user_id ),
				'short' => true,
			),
		);

		// Add user roles.
		if ( ! empty( $deleted_user_data->roles ) ) {

			// Get role info.
			$all_roles = wp_roles()->roles;

			// Build new array of roles.
			$roles = array();
			foreach ( $deleted_user_data->roles as $role ) {
				if ( array_key_exists( $role, $all_roles ) ) {
					$roles[] = $all_roles[ $role ]['name'];
				} else {
					$roles[] = $role;
				}
			}

			// Add to fields.
			$fields[] = array(
				'title' => __( 'User Role(s)', 'rock-the-slackbot' ),
				'value' => implode( ', ', $roles ),
				'short' => true,
			);

		}

		// If we're reassigning...
		if ( $reassign > 0 ) {
			$fields[] = array(
				'title' => __( 'Reassign Posts To', 'rock-the-slackbot' ),
				'value' => get_the_author_meta( 'display_name', $reassign ),
				'short' => true,
			);
		}

		// Create attachment.
		$attachments = array(
			array(
				'fallback'      => $general_message,
				'text'          => wp_trim_words( strip_tags( get_the_author_meta( 'description', $user_id ) ), 30, '...' ),
				'author_name'   => $deleted_user_display_name,
				'author_link'   => get_author_posts_url( $user_id ),
				'author_icon'   => get_avatar_url( $user_id, 32 ),
				'fields'        => $fields,
			),
		);

		// Send each webhook.
		$this->send_outgoing_webhooks( $notification_event, $outgoing_webhooks, $payload, $attachments, array(
			'user' => $deleted_user_data,
		));

	}

	/**
	 * Sends a notification to Slack
	 * when a user's role has changed.
	 *
	 * Fires after the user's role has changed.
	 *
	 * @access	public
	 * @since	1.1.0
	 * @param	int - $user_id - the User ID.
	 * @param	string - $role - the new role.
	 * @param	array - $old_roles - an array of the user's previous roles.
	 * @return	bool - returns false if nothing happened
	 */
	public function user_role_notification( $user_id, $role, $old_roles ) {

		// Which event are we processing?
		$notification_event = 'set_user_role';

		// Get the outgoing webhooks.
		$outgoing_webhooks = $this->get_outgoing_webhooks( $notification_event );

		// If we have no webhooks, then there's no point.
		if ( ! $outgoing_webhooks ) {
			return false;
		}

		// Get current user.
		$current_user = wp_get_current_user();

		// Get site URL and name.
		$site_url = get_bloginfo( 'url' );
		$site_name = get_bloginfo( 'name' );

		// Get changed user data.
		$changed_user_data = get_userdata( $user_id );
		$changed_user_display_name = get_the_author_meta( 'display_name', $user_id );

		// Get role info.
		$all_roles = wp_roles()->roles;

		// Create general message for the notification.
		$general_message = sprintf( __( '%1$s changed the user role for %2$s on the %3$s website at <%4$s>.', 'rock-the-slackbot' ),
			$current_user->display_name,
			$changed_user_display_name,
			$site_name,
			$site_url
		);

		// Start creating the payload.
		$payload = array(
			'text' => $general_message,
		);

		// Build array of current user roles.
		$current_user_roles = array();

		// Add current user roles.
		if ( ! empty( $changed_user_data->roles ) ) {

			foreach ( $changed_user_data->roles as $role ) {
				if ( array_key_exists( $role, $all_roles ) ) {
					$current_user_roles[] = $all_roles[ $role ]['name'];
				} else {
					$current_user_roles[] = $role;
				}
			}

			// Add to fields.
			$fields[] = array(
				'title' => __( 'Current User Role(s)', 'rock-the-slackbot' ),
				'value' => implode( ', ', $current_user_roles ),
				'short' => true,
			);

		}

		// Build array of old user roles.
		$old_user_roles = array();

		// Add old user roles.
		if ( ! empty( $old_roles ) ) {

			foreach ( $old_roles as $role ) {
				if ( array_key_exists( $role, $all_roles ) ) {
					$old_user_roles[] = $all_roles[ $role ]['name'];
				} else {
					$old_user_roles[] = $role;
				}
			}

			// Add to fields.
			$fields[] = array(
				'title' => __( 'Old User Role(s)', 'rock-the-slackbot' ),
				'value' => implode( ', ', $old_user_roles ),
				'short' => true,
			);

		}

		// Add user login.
		$fields[] = array(
			'title' => __( 'User Login', 'rock-the-slackbot' ),
			'value' => get_the_author_meta( 'user_login', $user_id ),
			'short' => true,
		);

		// Add user email.
		$fields[] = array(
			'title' => __( 'User Email', 'rock-the-slackbot' ),
			'value' => get_the_author_meta( 'user_email', $user_id ),
			'short' => true,
		);

		// Create attachment.
		$attachments = array(
			array(
				'fallback'      => $general_message,
				'text'          => wp_trim_words( strip_tags( get_the_author_meta( 'description', $user_id ) ), 30, '...' ),
				'author_name'   => $changed_user_display_name,
				'author_link'   => get_author_posts_url( $user_id ),
				'author_icon'   => get_avatar_url( $user_id, 32 ),
				'fields'        => $fields,
			),
		);

		// Send each webhook.
		$this->send_outgoing_webhooks( $notification_event, $outgoing_webhooks, $payload, $attachments, array(
			'user'				    => $changed_user_data,
			'current_user_roles'    => $current_user_roles,
			'old_user_roles'	    => $old_user_roles,
		));

	}

	/**
	 * Fires immediately after a comment is inserted into the database.
	 *
	 * @since   1.1.2
	 * @param   int - $comment_id - the comment ID.
	 * @param   WP_Comment - $comment - the comment object.
	 * @return	bool - returns false if nothing happened
	 */
	public function comment_inserted( $comment_id, $comment ) {

		// Which event are we processing?
		$notification_event = 'insert_comment';

		// Get the outgoing webhooks.
		$outgoing_webhooks = $this->get_outgoing_webhooks( $notification_event );

		// If we have no webhooks, then there's no point.
		if ( ! $outgoing_webhooks ) {
			return false;
		}

		// Get site URL and name.
		$site_url = get_bloginfo( 'url' );
		$site_name = get_bloginfo( 'name' );

		// Get post comments link.
		$post_comments_link = get_comments_link( $comment->comment_post_ID );

		// Get commenter user data.
		$comment_user_id = ! empty( $comment->user_id ) && $comment->user_id > 0 ? $comment->user_id : 0;
		$comment_user_data = $comment_user_id > 0 ? get_userdata( $comment_user_id ) : null;

		// Get display name of comment author.
		$comment_user_display_name = '';

		/*
		 * If user is logged in, get their display name.
		 *
		 * Otherwise, check the author in the comment object.
		 */
		if ( $comment_user_data && ! empty( $comment_user_data->display_name ) ) {
			$comment_user_display_name = $comment_user_data->display_name;
		} elseif ( ! empty( $comment->comment_author ) ) {
			$comment_user_display_name = $comment->comment_author;
		}

		// Create general message for the notification.
		$general_message = sprintf( __( '%1$s added the following comment to the %2$s website at <%3$s>.', 'rock-the-slackbot' ),
			! empty( $comment_user_display_name ) ? $comment_user_display_name : 'Someone',
			$site_name,
			$site_url
		);

		// Start creating the payload.
		$payload = array(
			'text' => $general_message,
		);

		// Start creating the fields.
		$fields = array();

		// If set, add comment type.
		if ( ! empty( $comment->comment_type ) ) {
			$fields[] = array(
				'title' => __( 'Comment Type', 'rock-the-slackbot' ),
				'value' => ucwords( $comment->comment_type ),
				'short' => true,
			);
		}

		// If approved, view the comment.
		if ( 1 == $comment->comment_approved ) {
			$fields[] = array(
				'title' => __( 'View Comment', 'rock-the-slackbot' ),
				'value' => trailingslashit( get_permalink( $comment->comment_post_ID ) ) . "#comment-{$comment_id}",
				'short' => true,
			);
		}

		// Edit the comment.
		$fields[] = array(
			'title' => __( 'Edit Comment', 'rock-the-slackbot' ),
			'value' => add_query_arg( array(
				'action'    => 'editcomment',
				'c'         => $comment_id,
			), admin_url( 'comment.php' ) ),
			'short' => true,
		);

		// View comment's parent.
		if ( $comment->comment_parent > 0 ) {
			$fields[] = array(
				'title' => __( "View Comment's Parent", 'rock-the-slackbot' ),
				'value' => trailingslashit( get_permalink( $comment->comment_post_ID ) ) . "#comment-{$comment->comment_parent}",
				'short' => true,
			);
		}

		// If the comment is not approved...
		if ( 1 != $comment->comment_approved ) {

			// Build comment status.
			$comment_status = __( 'This comment has not been approved.', 'rock-the-slackbot' );

			// Customize for marked as spam and trashed.
			if ( 'spam' == $comment->comment_approved ) {
				$comment_status = __( 'This comment has been marked as spam.', 'rock-the-slackbot' );
			} elseif ( 'trash' == $comment->comment_approved ) {
				$comment_status = __( 'This comment has been trashed.', 'rock-the-slackbot' );
			}

			// Let the user know it's not approved.
			$fields[] = array(
				'title' => __( 'Comment Status', 'rock-the-slackbot' ),
				'value' => $comment_status,
				'short' => true,
			);

			/*
			 * Approve the comment.
			 *
			 * @TODO See if we can get this to work outside the admin one day.
			 *
			 * Right now check_admin_referer() is run so nope.

			$fields[] = array(
				'title' => __( 'Approve Comment', 'rock-the-slackbot' ),
				'value' => wp_nonce_url( add_query_arg( array(
					'action'    => 'approvecomment',
					'c'         => $comment_id,
				), admin_url( 'comment.php' ) ), "approve-comment_{$comment_id}" ),
				'short' => true,
			);*/

			/*
			 * Mark the comment as spam.
			 *
			 * @TODO See if we can get this to work outside the admin one day.
			 *
			 * Right now check_admin_referer() is run so nope.

			if ( 'spam' != $comment->comment_approved ) {
				$fields[] = array(
					'title' => __( 'Mark Comment As Spam', 'rock-the-slackbot' ),
					'value' => wp_nonce_url( add_query_arg( array(
						'action' => 'spamcomment',
						'c'      => $comment_id,
					), admin_url( 'comment.php' ) ), "delete-comment_{$comment_id}" ),
					'short' => true,
				);
			}*/

			/*
			 * Trash the comment.
			 *
			 * @TODO See if we can get this to work outside the admin one day.
			 *
			 * Right now check_admin_referer() is run so nope.

			if ( 'trash' != $comment->comment_approved ) {
				$fields[] = array(
					'title' => __( 'Trash Comment', 'rock-the-slackbot' ),
					'value' => wp_nonce_url( add_query_arg( array(
						'action' => 'trashcomment',
						'c'      => $comment_id,
					), admin_url( 'comment.php' ) ), "delete-comment_{$comment_id}" ),
					'short' => true,
				);
			}*/

		}

		// Build attachment.
		$attachment = array(
			'fallback'      => $general_message,
			'text'          => wp_trim_words( strip_tags( $comment->comment_content ), 30, '...' ),
			'title'         => get_the_title( $comment->comment_post_ID ),
			'title_link'    => $post_comments_link,
			'fields'        => $fields,
		);

		// Add author information.
		$attachment = array_merge( $attachment, array(
			'author_name'   => ! empty( $comment_user_display_name ) ? $comment_user_display_name : __( 'Commenter Name Not Included', 'rock-the-slackbot' ),
			'author_link'   => ! empty( $comment_user_data->ID ) ? get_author_posts_url( $comment_user_data->ID ) : $comment->comment_author_url,
			'author_icon'   => ! empty( $comment_user_data->ID ) ? get_avatar_url( $comment_user_data->ID, 32 ) : '',
		));

		// Create attachment.
		$attachments = array( $attachment );

		// Send each webhook.
		$this->send_outgoing_webhooks( $notification_event, $outgoing_webhooks, $payload, $attachments, array(
			'comment' => $comment,
		));

	}

	/**
	 * Fires when the comment status is in transition.
	 *
	 * Possible statuses:
	 *  unapproved - Unapproved
	 *  approved - Approved
	 *  spam - Spam
	 *  trash - Trash
	 *
	 * @since   1.1.2
	 * @param   int|string - $new_status - The new comment status.
	 * @param   int|string - $old_status - The old comment status.
	 * @param   object - $comment - The comment data.
	 * @return  bool - returns false if nothing happened
	 */
	public function transition_comment_status( $new_status, $old_status, $comment ) {

		// Don't worry about if the status is the same.
		if ( $new_status == $old_status ) {
			return false;
		}

		// Which event are we processing?
		$notification_event = '';
		switch ( $new_status ) {

			case 'unapproved':
				$notification_event = 'comment_unapproved';
				break;

			case 'approved':
				$notification_event = 'comment_approved';
				break;

			case 'spam':
				$notification_event = 'comment_spammed';
				break;

			case 'trash':
				$notification_event = 'comment_trashed';
				break;

		}

		// Get the outgoing webhooks.
		$outgoing_webhooks = $this->get_outgoing_webhooks( $notification_event );

		// If we have no webhooks, then there's no point.
		if ( ! $outgoing_webhooks ) {
			return false;
		}

		// Get current user who took the action.
		$current_user = wp_get_current_user();

		// Get site URL and name.
		$site_url = get_bloginfo( 'url' );
		$site_name = get_bloginfo( 'name' );

		// Get post comments link.
		$post_comments_link = get_comments_link( $comment->comment_post_ID );

		// Get user data for commenter.
		$comment_user_id = ! empty( $comment->user_id ) && $comment->user_id > 0 ? $comment->user_id : 0;
		$comment_user_data = $comment_user_id > 0 ? get_userdata( $comment_user_id ) : null;

		// Get display name of comment author.
		$comment_user_display_name = '';

		/*
		 * If user is logged in, get their display name.
		 *
		 * Otherwise, check the author in the comment object.
		 */
		if ( $comment_user_data && ! empty( $comment_user_data->display_name ) ) {
			$comment_user_display_name = $comment_user_data->display_name;
		} elseif ( ! empty( $comment->comment_author ) ) {
			$comment_user_display_name = $comment->comment_author;
		}

		// Build general message and comment status.
		$general_message = '';
		$comment_status = '';

		switch ( $new_status ) {

			case 'unapproved':

				// Build general message.
				$general_message = sprintf( __( '%1$s unapproved the following comment on the %2$s website at <%3$s>.', 'rock-the-slackbot' ),
					$current_user->display_name,
					$site_name,
					$site_url
				);

				// Build comment status.
				$comment_status = __( 'This comment has been unapproved.', 'rock-the-slackbot' );

				break;

			case 'approved':

				// Build general message.
				$general_message = sprintf( __( '%1$s approved the following comment on the %2$s website at <%3$s>.', 'rock-the-slackbot' ),
					$current_user->display_name,
					$site_name,
					$site_url
				);

				// Build comment status.
				$comment_status = __( 'This comment is now approved.', 'rock-the-slackbot' );

				break;

			case 'spam':

				// Build general message.
				$general_message = sprintf( __( '%1$s marked the following comment as spam on the %2$s website at <%3$s>.', 'rock-the-slackbot' ),
					$current_user->display_name,
					$site_name,
					$site_url
				);

				// Build comment status.
				$comment_status = __( 'This comment has been marked as spam.', 'rock-the-slackbot' );

				break;

			case 'trash':

				// Build general message.
				$general_message = sprintf( __( '%1$s trashed the following comment on the %2$s website at <%3$s>.', 'rock-the-slackbot' ),
					$current_user->display_name,
					$site_name,
					$site_url
				);

				// Build comment status.
				$comment_status = __( 'This comment has been trashed.', 'rock-the-slackbot' );

				break;

		}

		// Make sure we have a message.
		if ( empty( $general_message ) ) {
			return false;
		}

		// Start creating the payload.
		$payload = array(
			'text' => $general_message,
		);

		// Start creating the fields.
		$fields = array();

		// If set, add comment type.
		if ( ! empty( $comment->comment_type ) ) {
			$fields[] = array(
				'title' => __( 'Comment Type', 'rock-the-slackbot' ),
				'value' => ucwords( $comment->comment_type ),
				'short' => true,
			);
		}

		// If approved, view the comment.
		if ( 1 == $comment->comment_approved ) {
			$fields[] = array(
				'title' => __( 'View Comment', 'rock-the-slackbot' ),
				'value' => trailingslashit( get_permalink( $comment->comment_post_ID ) ) . "#comment-{$comment->comment_ID}",
				'short' => true,
			);
		}

		// Edit the comment.
		$fields[] = array(
			'title'         => __( 'Edit Comment', 'rock-the-slackbot' ),
			'value' => add_query_arg( array(
				'action'    => 'editcomment',
				'c'         => $comment->comment_ID,
			), admin_url( 'comment.php' ) ),
			'short'         => true,
		);

		// View comment's parent.
		if ( $comment->comment_parent > 0 ) {
			$fields[] = array(
				'title' => __( "View Comment's Parent", 'rock-the-slackbot' ),
				'value' => trailingslashit( get_permalink( $comment->comment_post_ID ) ) . "#comment-{$comment->comment_parent}",
				'short' => true,
			);
		}

		// Add current comment status.
		$fields[] = array(
			'title' => __( 'Comment Status', 'rock-the-slackbot' ),
			'value' => ucfirst( $comment_status ),
			'short' => true,
		);

		// Show old status.
		$fields[] = array(
			'title' => __( 'Old Status', 'rock-the-slackbot' ),
			'value' => ucfirst( $old_status ),
			'short' => true,
		);

		// Build attachment.
		$attachment = array(
			'fallback'      => $general_message,
			'text'          => wp_trim_words( strip_tags( $comment->comment_content ), 30, '...' ),
			'title'         => get_the_title( $comment->comment_post_ID ),
			'title_link'    => $post_comments_link,
			'fields'        => $fields,
		);

		// Add author information.
		$attachment = array_merge( $attachment, array(
			'author_name'   => ! empty( $comment_user_display_name ) ? $comment_user_display_name : __( 'Commenter Name Not Included', 'rock-the-slackbot' ),
			'author_link'   => ! empty( $comment_user_data->ID ) ? get_author_posts_url( $comment_user_data->ID ) : $comment->comment_author_url,
			'author_icon'   => ! empty( $comment_user_data->ID ) ? get_avatar_url( $comment_user_data->ID, 32 ) : '',
		));

		// Create attachment.
		$attachments = array( $attachment );

		// Send each webhook.
		$this->send_outgoing_webhooks( $notification_event, $outgoing_webhooks, $payload, $attachments, array(
			'comment'               => $comment,
			'old_comment_status'    => $old_status,
			'new_comment_status'    => $new_status,
		));

	}

}

/**
 * Returns the instance of the Rock_The_Slackbot_Hooks class.
 *
 * @since	1.1.2
 * @access	public
 * @return	Rock_The_Slackbot_Hooks
 */
function rock_the_slackbot_hooks() {
	return Rock_The_Slackbot_Hooks::instance();
}

// Let's get this show on the road.
rock_the_slackbot_hooks();
