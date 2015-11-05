<?php

class Rock_The_Slackbot_Admin {

	/**
	 * ID of the tools page
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $tools_page_id;

	/**
	 * Will be true and hold
	 * ID if edit page for
	 * outgoing webhook.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string|false
	 */
	private $edit_webhook;

	/**
	 * Will be true if add page
	 * for an outgoing webhook.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var boolean
	 */
	private $add_webhook;

	/**
	 * Takes care of admin shenanigans.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct() {

		// See if we're editing an outgoing webhook
		$this->edit_webhook = isset( $_GET[ 'edit' ] ) && ! empty( $_GET[ 'edit' ] ) ? $_GET[ 'edit' ] : false;

		// See if we're adding an outgoing webhook
		$this->add_webhook = ! $this->edit_webhook && isset( $_GET[ 'add' ] ) && $_GET[ 'add' ] == 1 ? true : false;

		// Add plugin action links
		add_filter( 'plugin_action_links_' . ROCK_THE_SLACKBOT_PLUGIN_FILE, array( $this, 'add_plugin_action_links' ), 10, 4 );


		// Add our tools page
		add_action( 'admin_menu', array( $this, 'add_tools_page' ) );

		// Add our tools meta boxes
		add_action( 'admin_head-tools_page_rock-the-slackbot', array( $this, 'add_tools_meta_boxes' ) );

		// Add styles and scripts for the tools page
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );

		// Register our settings
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Handle deleting of outgoing webhooks
		add_action( 'admin_init', array( $this, 'delete_outgoing_webhook' ) );

		// Sets up and stores any pre upgrade information
		add_action( 'admin_action_update-selected', array( $this, 'handle_pre_upgrade_information' ) );
		add_action( 'admin_action_upgrade-theme', array( $this, 'handle_pre_upgrade_information' ) );
		add_action( 'admin_action_update-selected-themes', array( $this, 'handle_pre_upgrade_information' ) );
		add_action( 'admin_action_do-theme-upgrade', array( $this, 'handle_pre_upgrade_information' ) );
		add_action( 'admin_action_upgrade-plugin', array( $this, 'handle_pre_upgrade_information' ) );
		add_action( 'admin_action_do-plugin-upgrade', array( $this, 'handle_pre_upgrade_information' ) );
		add_action( 'admin_action_upgrade-core', array( $this, 'handle_pre_upgrade_information' ) );
		add_action( 'admin_action_do-core-upgrade', array( $this, 'handle_pre_upgrade_information' ) );
		add_action( 'admin_action_do-core-reinstall', array( $this, 'handle_pre_upgrade_information' ) );

	}

	/**
	 * Add our own plugin action links.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param   array - $actions - An array of plugin action links
	 * @param   string - $$plugin_file - Path to the plugin file
	 * @param   array - $plugin_data - An array of plugin data
	 * @param   string - $context - The plugin context. Defaults are 'All', 'Active',
	 *                      'Inactive', 'Recently Activated', 'Upgrade',
	 *                      'Must-Use', 'Drop-ins', 'Search'.
	 * @return  array - the filtered actions
	 */
	public function add_plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
		if ( current_user_can( 'manage_options' ) ) {
			$actions[] = '<a href="' . add_query_arg( array( 'page' => 'rock-the-slackbot' ), admin_url( 'tools.php' ) ) . '">' . __( 'Manage', 'rock-the-slackbot' ) . '</a>';
		}
		return $actions;
	}

	/**
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function add_tools_page() {

		// Add our tools page
		$this->tools_page_id = add_management_page(
			__( 'Rock The Slackbot', 'rock-the-slackbot' ),
			__( 'Rock The Slackbot', 'rock-the-slackbot' ),
			'manage_options',
			'rock-the-slackbot',
			array( $this, 'print_tools_page' )
		);

	}

	/**
	 * Add styles and scripts for our tools page
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param	string - $hook_suffix - the ID of the current page
	 */
	public function enqueue_styles_scripts( $hook_suffix ) {

		// Only for our tools page
		if ( $hook_suffix != $this->tools_page_id ) {
			return;
		}

		// Enqueue our main styles
		wp_enqueue_style( 'rock-the-slackbot-admin-tools', trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) . 'css' ) . 'admin-tools.min.css', array(), ROCK_THE_SLACKBOT_VERSION );

		// We only need the script on the add and edit page
		if ( $this->add_webhook || $this->edit_webhook ) {

			wp_enqueue_style( 'rts-jquery-ui', '//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css' );
			wp_enqueue_script( 'rock-the-slackbot-admin-tools', trailingslashit(plugin_dir_url(dirname(__FILE__)) . 'js') . 'admin-tools.min.js', array('jquery', 'jquery-ui-tooltip'), ROCK_THE_SLACKBOT_VERSION, true );

			// Need to send some data to our script
			wp_localize_script( 'rock-the-slackbot-admin-tools', 'rock_the_slackbot', array(
				'delete_webhook_conf' => __('Are you sure you want to delete this webhook?', 'rock-the-slackbot'),
			));

		}

		// Need this script for the meta boxes to work correctly
		wp_enqueue_script( 'post' );
		wp_enqueue_script( 'postbox' );

	}

	/**
	 * Add our tools meta boxes.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function add_tools_meta_boxes() {

		// About this Plugin
		add_meta_box( 'rock-slackbot-about-mb', __( 'About this Plugin', 'rock-the-slackbot' ), array( $this, 'print_tools_meta_boxes' ), $this->tools_page_id, 'side', 'core', 'about-plugin' );

		// Spread the Love
		add_meta_box( 'rock-slackbot-promote-mb', __( 'Spread the Love', 'rock-the-slackbot' ), array( $this, 'print_tools_meta_boxes' ), $this->tools_page_id, 'side', 'core', 'promote' );

		// If we're viewing an add or edit outgoing webhook page
		if ( $this->edit_webhook || $this->add_webhook ) {

			// Add/Edit Outgoing WebHook
			$meta_box_title = $this->edit_webhook ? __( 'Edit Outgoing WebHook', 'rock-the-slackbot' ) : __( 'Add Outgoing WebHook', 'rock-the-slackbot' );
			add_meta_box( 'rock-slackbot-edit-outgoing-webhook-mb', $meta_box_title, array( $this, 'print_tools_meta_boxes' ), $this->tools_page_id, 'normal', 'core', 'edit-outgoing-webhook' );

		} else {

			// Outgoing WebHooks
			add_meta_box( 'rock-slackbot-outgoing-webhooks-mb', __( 'Slack Notifications', 'rock-the-slackbot' ), array( $this, 'print_tools_meta_boxes' ), $this->tools_page_id, 'normal', 'core', 'outgoing-webhooks' );

		}

	}

	/**
	 * Print our tools meta boxes.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param 	array - $post - information about the current post, which is empty because there is no current post on a tools page
	 * @param 	array - $metabox - information about the metabox
	 */
	public function print_tools_meta_boxes( $post, $metabox ) {

		switch( $metabox[ 'args' ] ) {

			// About meta box
			case 'about-plugin':
				?><p><strong><a href="<?php echo ROCK_THE_SLACKBOT_PLUGIN_URL; ?>" target="_blank"><?php _e( 'Rock The Slackbot', 'rock-the-slackbot' ); ?></a></strong><br />
				<strong><?php _e( 'Version', 'rock-the-slackbot' ); ?>:</strong> <?php echo ROCK_THE_SLACKBOT_VERSION; ?><br /><strong><?php _e( 'Author', 'rock-the-slackbot' ); ?>:</strong> <a href="http://bamadesigner.com/" target="_blank">Rachel Carden</a></p><?php
				break;

			// Promote meta box
			case 'promote':
				?><p class="star"><a href="<?php echo ROCK_THE_SLACKBOT_PLUGIN_URL; ?>" title="<?php esc_attr_e( 'Give the plugin a good rating', 'rock-the-slackbot' ); ?>" target="_blank"><span class="dashicons dashicons-star-filled"></span> <span class="promote-text"><?php _e( 'Give the plugin a good rating', 'rock-the-slackbot' ); ?></span></a></p>
				<p class="twitter"><a href="https://twitter.com/bamadesigner" title="<?php _e( 'Follow bamadesigner on Twitter', 'rock-the-slackbot' ); ?>" target="_blank"><span class="dashicons dashicons-twitter"></span> <span class="promote-text"><?php _e( 'Follow me on Twitter', 'rock-the-slackbot' ); ?></span></a></p>
				<p class="donate"><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=ZCAN2UX7QHZPL&lc=US&item_name=Rachel%20Carden%20%28Rock%20The%20Slackbot%29&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted" title="<?php esc_attr_e( 'Donate a few bucks to the plugin', 'rock-the-slackbot' ); ?>" target="_blank"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" alt="<?php esc_attr_e( 'Donate', 'rock-the-slackbot' ); ?>" /> <span class="promote-text"><?php _e( 'and buy me a coffee', 'rock-the-slackbot' ); ?></span></a></p><?php
				break;

			// Manage outgoing webhooks meta box
			case 'outgoing-webhooks':
				$this->print_outgoing_webhooks_meta_box();
				break;

			// Edit outgoing webhooks meta box
			case 'edit-outgoing-webhook':
				$this->print_edit_outgoing_webhook_meta_box();
				break;

		}

	}

	/**
	 * Print the Outgoing WebHooks meta box.
	 *
	 * @access  private
	 * @since   1.0.0
	 */
	private function print_outgoing_webhooks_meta_box() {

		// Get all of the outgoing webhooks
		$outgoing_webhooks = rock_the_slackbot()->get_all_outgoing_webhooks();

		// If we have hooks, print the table
		if ( ! $outgoing_webhooks ) {

			?><div id="rock-slackbot-no-webhooks">
				<a href="<?php echo add_query_arg(array('page' => 'rock-the-slackbot', 'add' => 1), admin_url('tools.php')); ?>"><span class="dashicons dashicons-plus-alt"></span> <span class="rts-link-text"><?php _e( "Let's get this party started", 'rock-the-slackbot' ); ?></span></a>
			</div><?php

		} else {

			?><div class="rock-slackbot-outgoing-webhooks-message"><?php printf( __( '%1$sSlack uses incoming webhooks%2$s as a simple way to pull in messages from external sources. Use the settings below to create outgoing webhooks which will send notifications following numerous WordPress events straight to a channel or direct message in your Slack account.', 'rock-the-slackbot' ), '<a href="https://api.slack.com/incoming-webhooks" target="_blank">', '</a>' ); ?></div>
			<table class="rock-slackbot rock-slackbot-outgoing-webhooks" cellpadding="0" cellspacing="0" border="0">
				<thead>
					<tr>
						<th class="index"></th>
						<th class="status"></th>
						<th class="name"><?php _e( 'Name of Webhook', 'rock-the-slackbot' ); ?></th>
						<th class="account"><?php _e( 'Slack Account', 'rock-the-slackbot' ); ?></th>
						<th class="channel"><?php _e( 'Slack Channel', 'rock-the-slackbot' ); ?></th>
						<th class="events"><?php _e( 'Events', 'rock-the-slackbot' ); ?></th>
					</tr>
				</thead>
				<tbody><?php

					// Print a row for each hook
					$hook_index = 1;
					foreach ( $outgoing_webhooks as $hook ) {

						// What's the status?
						$hook_status = isset( $hook[ 'deactivate' ] ) && $hook[ 'deactivate' ] > 0 ? 'inactive' : 'active';

						// Figure out how many events are active
						$active_events_count = 0;
						foreach( $hook[ 'events' ] as $event ) {
							if ( isset( $event[ 'active' ] ) && $event[ 'active' ] == 1 ) {
								$active_events_count++;
							}
						}

						?><tr class="view">
							<td class="index"><?php echo $hook_index; ?></td>
							<td class="status">
								<div class="status-circle <?php echo $hook_status; ?>" title="<?php printf( __( 'This outgoing webhook is %s', 'rock-the-slackbot' ), $hook_status ); ?>"></div>
							</td>
							<td class="name"><a href="<?php echo add_query_arg( array( 'page' => 'rock-the-slackbot', 'edit' => $hook[ 'ID' ] ), admin_url( 'tools.php' ) ); ?>"><?php echo $hook[ 'name' ]; ?></a></td>
							<td class="account"><?php echo $hook[ 'account' ]; ?></td>
							<td class="channel"><?php

								if ( isset( $hook[ 'channel' ] ) && ! empty( $hook[ 'channel' ] ) ) {
									echo $hook[ 'channel' ];
								} else {
									?><em><?php _e( 'Uses default channel', 'rock-the-slackbot' ); ?></em><?php
								}

							?></td>
							<td class="events"><?php echo $active_events_count; ?></td>
						</tr><?php
						$hook_index++;
					}

				?></tbody>
			</table>
			<div id="rts-add-button-wrapper">
				<a class="button button-primary rts-dashicons rts-button" href="<?php echo add_query_arg(array('page' => 'rock-the-slackbot', 'add' => 1), admin_url('tools.php')); ?>"><span class="dashicons dashicons-plus-alt"></span> <?php _e( 'Add A Webhook', 'rock-the-slackbot' ); ?></a>
			</div><?php

		}

	}

	/**
	 * Print the "Add/Edit Outgoing WebHook" meta box.
	 *
	 * @access  private
	 * @since   1.0.0
	 */
	private function print_edit_outgoing_webhook_meta_box() {

		// @TODO Set it up so webhook URL is validated when entered or edited and check each time settings page is loaded to show error message if not working

		// Get our webhook
		$webhook = $this->edit_webhook ? rock_the_slackbot()->get_outgoing_webhook( $this->edit_webhook ) : false;

		// If we're adding and we have a value stored in transient from error processing
		if ( $this->add_webhook ) {
			$webhook_transient = get_transient( 'rock_the_slackbot_add_outgoing_webhook' );
			if ( $webhook_transient !== false ) {
				$webhook = $webhook_transient;
			}
		}

		// Show message if there is no hook with this ID
		if ( $this->edit_webhook && ! $webhook ) {

			?><div id="rock-slackbot-no-webhook-edit">
				<p><?php _e( "Uh-oh. Someone doesn't like this URL. Here are some other ways to rock the Slackbot", 'rock-the-slackbot' ); ?>:</p>
				<ul>
					<li><a class="rts-dashicons rts-icons-link" href="<?php echo add_query_arg(array('page' => 'rock-the-slackbot'), admin_url('tools.php')); ?>"><span class="dashicons dashicons-arrow-left-alt"></span> <span class="rts-link-text"><?php _e( 'Back to Main Settings', 'rock-the-slackbot' ); ?></span></a></li>
					<li><a class="rts-dashicons rts-icons-link" href="<?php echo add_query_arg(array('page' => 'rock-the-slackbot', 'add' => 1), admin_url('tools.php')); ?>"><span class="dashicons dashicons-plus-alt"></span> <span class="rts-link-text"><?php _e( 'Add A Webhook', 'rock-the-slackbot' ); ?></span></a></li>
				</ul>
			</div><?php

		} else {

			?><form class="rock-slackbot-edit-outgoing-webhook-form" method="post" action="options.php" novalidate="novalidate"><?php

				// Include our fields info
				settings_fields('rock_the_slackbot_outgoing_webhooks');

				// Get post types
				$post_types = get_post_types( array(), 'objects' );

				// Remove the following since they have their own events
				unset( $post_types[ 'attachment' ] );
				unset( $post_types[ 'nav_menu_item' ] );
				unset( $post_types[ 'revision' ] );

				// Sort alphabetically
				$sorted_post_types = array();
				foreach( $post_types as $pt_name => $pt ) {
					$sorted_post_types[ $pt_name ] = $pt->label;
				}
				asort( $sorted_post_types );

				// Get webhook events
				$webhook_events = rock_the_slackbot()->get_webhook_events();

				// Include some hidden fields
				?><input type="hidden" name="rock_the_slackbot_outgoing_webhooks[editing]" value="1" /><?php

				// Only add these if we're editing a current webhook
				if ( $this->edit_webhook ) {
					?><input type="hidden" name="rock_the_slackbot_outgoing_webhooks[ID]" value="<?php echo esc_attr($webhook[ 'ID' ]); ?>" />
					<input type="hidden" name="rock_the_slackbot_outgoing_webhooks[date_created]" value="<?php echo esc_attr($webhook[ 'date_created' ]); ?>"/>
					<input type="hidden" name="rock_the_slackbot_outgoing_webhooks[date_modified]" value="<?php echo esc_attr($webhook[ 'date_modified' ]); ?>"/><?php
				}

				?><div class="rock-slackbot-outgoing-webhooks-message"><?php printf( __( '%1$sSlack uses incoming webhooks%2$s as a simple way to pull in messages from external sources. Use the settings below to customize an outgoing webhook which will send notifications following selected WordPress events straight to the provided channel(s) or direct message(s) in your Slack account.', 'rock-the-slackbot' ), '<a href="https://api.slack.com/incoming-webhooks" target="_blank">', '</a>' ); ?></div>
				<table class="rock-slackbot rock-slackbot-edit-outgoing-webhook" cellpadding="0" cellspacing="0" border="0"><?php

					// Get/format date created and modified
					$date_created = isset( $webhook[ 'date_created' ] ) && ! empty( $webhook[ 'date_created' ] ) ? new DateTime( date( 'Y-m-d H:i:s', $webhook[ 'date_created' ] ) ) : false;
					$date_modified = isset( $webhook[ 'date_modified' ] ) && ! empty( $webhook[ 'date_modified' ] ) ? new DateTime( date( 'Y-m-d H:i:s', $webhook[ 'date_modified' ] ) ) : false;

					// Change to timezone
					if ( ( $date_created || $date_modified ) && ( $timezone_string = get_option( 'timezone_string' ) ) ) {

						// Get timezone object
						$timezone = new DateTimeZone( $timezone_string );

						if ( $date_created ) {
							$date_created->setTimezone($timezone);
						}
						if ( $date_modified ) {
							$date_modified->setTimezone($timezone);
						}

					}

					// Only show when editing
					if ( $this->edit_webhook ) {
						?><tr>
							<td class="rts-label">WebHook ID</td>
							<td class="rts-field lighter"><?php echo $webhook[ 'ID' ]; ?><span class="rts-field-desc"><?php _e( 'This information is for administrative purposes.', 'rock-the-slackbot' ); ?></span></td>
						</tr>
						<tr>
							<td class="rts-label"><?php _e( 'Date Created', 'rock-the-slackbot' ); ?></td>
							<td class="rts-field lighter"><?php echo $date_created->format( 'D\., M\. j, Y \a\t g\:i a' ); ?></td>
						</tr><?php

						// Only print modified time if necessary
						if ( $date_modified && $date_modified != $date_created ) {
							?><tr>
								<td class="rts-label"><?php _e( 'Date Last Modified', 'rock-the-slackbot' ); ?></td>
								<td class="rts-field lighter"><?php echo $date_modified->format( 'D\., M\. j, Y \a\t g\:i a' ); ?></td>
							</tr><?php
						}

					}

					// Figure out if all events are active
					$all_events_are_active = $this->add_webhook ? false : true;

					// If we're editing, check the settings
					if ( $this->edit_webhook ) {
						foreach ( $webhook_events as $section_id => $section ) {
							foreach( $section[ 'events' ] as $event_id => $event ) {

								if ( isset( $webhook[ 'events'][ $event_id ] ) && ! ( isset( $webhook[ 'events'][ $event_id ][ 'active' ] ) && $webhook[ 'events'][ $event_id ][ 'active' ] == 1 ) ) {
									$all_events_are_active = false;
									break 2;
								}

							}
						}
					}

					// Setup required message
					$required_message = '<span class="rts-field-required-message">' . __( 'This field is required.', 'rock-the-slackbot' ) . '</span>';

					?><tr>
						<td class="rts-label"><label for="rts-webhook-name"><?php _e( 'Name of Webhook', 'rock-the-slackbot' ); ?></label></td>
						<td class="rts-field rts-field-required<?php echo ( ! isset( $webhook[ 'name' ] ) || empty( $webhook[ 'name' ] ) ) ? ' rts-field-error' : null; ?>"><?php

							// Print input
							?><input id="rts-webhook-name" class="rts-input-required" type="text" name="rock_the_slackbot_outgoing_webhooks[name]" value="<?php echo esc_attr($webhook[ 'name' ]); ?>"/><?php

							// Print required message
							echo $required_message;

							?><span class="rts-field-desc"><?php _e( 'This information is for administrative purposes, to help you label the different webhooks.', 'rock-the-slackbot' ); ?></span>
						</td>
					</tr>
					<tr>
						<td class="rts-label"><label for="rts-webhook-account"><?php _e( 'Name of Slack Account', 'rock-the-slackbot' ); ?></label></td>
						<td class="rts-field">
							<input id="rts-webhook-account" type="text" name="rock_the_slackbot_outgoing_webhooks[account]" value="<?php echo esc_attr($webhook[ 'account' ]); ?>"/>
							<span class="rts-field-desc"><?php _e( 'This information is for administrative purposes, to help you see where your messages are going.', 'rock-the-slackbot' ); ?></span>
						</td>
					</tr>
					<tr>
						<td class="rts-label"><label for="rts-webhook-url"><?php _e( 'Webhook URL', 'rock-the-slackbot' ); ?></label></td>
						<td class="rts-field rts-field-required<?php echo ( ! isset( $webhook[ 'name' ] ) || empty( $webhook[ 'name' ] ) ) ? ' rts-field-error' : null; ?>"><?php

							// Print input
							?><input id="rts-webhook-url" class="rts-input-required" type="text" name="rock_the_slackbot_outgoing_webhooks[webhook_url]" value="<?php echo esc_attr($webhook[ 'webhook_url' ]); ?>"/><?php

							// Print required message
							echo $required_message;

							?><span class="rts-field-desc"><?php printf( __( 'You must first %1$sset up an incoming webhook integration in your Slack account%2$s. Once you select a channel (which you can override below), click the button to add the integration, copy the provided webhook URL, and paste the URL in the box above.', 'rock-the-slackbot' ), '<a href="https://my.slack.com/services/new/incoming-webhook/" target="_blank">', '</a>' ); ?></span>
						</td>
					</tr>
					<tr>
						<td class="rts-label"><label for="rts-webhook-channel"><?php _e( 'Send To Which Slack Channel or Direct Message', 'rock-the-slackbot' ); ?></label></td>
						<td class="rts-field">
							<input id="rts-webhook-channel" type="text" name="rock_the_slackbot_outgoing_webhooks[channel]" value="<?php echo esc_attr($webhook[ 'channel' ]); ?>"/>
							<span class="rts-field-desc"><?php _e( 'Incoming webhooks have a default channel but you can use this setting as an override. Use a "#" before the name to specify a channel and a "@" to specify a direct message. For example, type "#wordpress" for your Slack channel about WordPress or type "@bamadesigner" to send your notifications to me as a direct message, at least you could if I was a member of your Slack account.', 'rock-the-slackbot' ); ?></span>
						</td>
					</tr>
					<tr>
						<td class="rts-label"><label for="rts-webhook-username"><?php _e( 'Post Message As Which Slack Username', 'rock-the-slackbot' ); ?></label></td>
						<td class="rts-field">
							<input id="rts-webhook-username" type="text" name="rock_the_slackbot_outgoing_webhooks[username]" value="<?php echo esc_attr($webhook[ 'username' ]); ?>"/>
							<span class="rts-field-desc"><?php _e( 'Incoming webhooks have a default username but you can use this setting as an override.', 'rock-the-slackbot' ); ?></span>
						</td>
					</tr>
					<tr>
						<td class="rts-label"><label for="rts-webhook-icon-emoji"><?php _e( 'Icon Emoji For Message', 'rock-the-slackbot' ); ?></label></td>
						<td class="rts-field">
							<input id="rts-webhook-icon-emoji" type="text" name="rock_the_slackbot_outgoing_webhooks[icon_emoji]" value="<?php echo esc_attr($webhook[ 'icon_emoji' ]); ?>"/>
							<span class="rts-field-desc"><?php _e( 'You can use this setting to designate a specific emoji for your message icon, e.g. ":thumbsup:" or ":sunglasses:". If this setting, and the custom URL setting below, is left blank, a WordPress icon will be used.', 'rock-the-slackbot' ); ?></span>
						</td>
					</tr>
					<tr>
						<td class="rts-label"><label for="rts-webhook-icon-url"><?php _e( 'Use Custom URL For Message Icon', 'rock-the-slackbot' ); ?></label></td>
						<td class="rts-field">
							<input id="rts-webhook-icon-url" type="text" name="rock_the_slackbot_outgoing_webhooks[icon_url]" value="<?php echo esc_attr($webhook[ 'icon_url' ]); ?>"/>
							<span class="rts-field-desc"><?php _e( 'You can use this setting to designate your own message icon from a URL. If this setting, and the emoji setting above, is left blank, a WordPress icon will be used.', 'rock-the-slackbot' ); ?></span>
						</td>
					</tr>
					<tr>
						<td class="rts-label"><?php _e( 'Exclude Post Types From Notifications', 'rock-the-slackbot' ); ?></td>
						<td class="rts-field"><?php

							$pt_index = 0;
							foreach( $sorted_post_types as $pt_name => $pt_label ) {
								$pt_field_id = "rts-webhook-post-type-{$pt_index}";
								?><span class="rts-choice"><input id="<?php echo $pt_field_id; ?>" type="checkbox" name="rock_the_slackbot_outgoing_webhooks[exclude_post_types][]" value="<?php echo $pt_name; ?>"<?php checked( isset( $webhook[ 'exclude_post_types' ] ) && in_array( $pt_name, $webhook[ 'exclude_post_types' ] ) ); ?> /> <label for="<?php echo $pt_field_id; ?>"> <?php echo $pt_label; ?></label></span><?php
								$pt_index++;
							}

							?><span class="rts-field-desc"><?php _e( 'By default, all post types will be included when sending notifications for content related events. Use this setting to exclude certain post types from your notifications.', 'rock-the-slackbot' ); ?></span>
						</td>
					</tr>
					<tr id="edit-rts-notification-events">
						<td class="rts-label">Notification Events<br />
							<span class="rts-label-desc"><?php _e( 'When would you like to receive notifications?', 'rock-the-slackbot' ); ?></span>
							<span id="rts-select-all-events" class="rts-button rts-button-block<?php echo $all_events_are_active ? ' all-selected' : null; ?>">
								<span class="hide-if-all-selected"><?php _e( 'Select all events', 'rock-the-slackbot' ); ?></span>
								<span class="show-if-all-selected"><?php _e( 'Deselect all events', 'rock-the-slackbot' ); ?></span>
							</span>
						</td>
						<td class="rts-field rts-events"><?php

							foreach( $webhook_events as $event_section_id => $event_section ) {

								?><table class="rock-slackbot rts-event-section" cellpadding="0" cellspacing="0" border="0">
									<tr>
										<td class="rts-label"><?php echo $event_section[ 'label' ]; ?></td>
										<td class="rts-field rts-event-section"><?php

											$event_index = 0;
											foreach( $event_section[ 'events' ] as $event_name => $event ) {

												// Set the field ID
												$event_field_id = "rts-webhook-post-type-{$event_name}";

												// Figure out if this event should be marked active
												$event_is_active = $this->add_webhook ? ( isset( $event[ 'default' ] ) && $event[ 'default' ] == 1 ) : ( isset( $webhook[ 'events' ][ $event_name ][ 'active' ] ) && $webhook[ 'events' ][ $event_name ][ 'active' ] == 1 );

												?><div class="rts-event-choice<?php echo $event_is_active ? ' rts-choice-is-active' : null; ?>">
													<div class="rts-event-choice-active">
														<label for="<?php echo $event_field_id; ?>"><input id="<?php echo $event_field_id; ?>" class="rts-event-choice-active-field" type="checkbox" name="rock_the_slackbot_outgoing_webhooks[events][<?php echo $event_name; ?>][active]" value="1"<?php checked( $event_is_active ); ?> /> <?php echo $event[ 'label' ]; ?></label>
													</div>
													<table class="rock-slackbot rts-event-choice-details" cellpadding="0" cellspacing="0" border="0">
														<tr>
															<td class="rts-label"><label for="<?php echo $event_field_id; ?>-channel"><?php _e( 'Slack Channel or DM', 'rock-the-slackbot' ); ?></label></td>
															<td class="rts-field">
																<input id="<?php echo $event_field_id; ?>-channel" class="rts-tooltip" type="text" name="rock_the_slackbot_outgoing_webhooks[events][<?php echo $event_name; ?>][channel]" value="<?php echo esc_attr($webhook[ 'events' ][ $event_name ][ 'channel' ]); ?>" title="<?php esc_attr_e( 'This allows you to set a Slack channel or direct message for this specific event. Leave blank to use the default channel. Use a # or @ before the name to specify a channel or direct message, respectively.', 'rock-the-slackbot' ); ?>" />
																<span class="rts-field-desc"><?php _e( 'Leave blank to use the default channel.', 'rock-the-slackbot' ); ?></span>
															</td>
														</tr>
													</table>
												</div><?php
												$event_index++;
											}

										?></td>
									</tr>
								</table><?php

							}

						?></td>
					</tr>
					<tr>
						<td class="rts-label"><?php _e( 'Deactivate', 'rock-the-slackbot' ); ?></td>
						<td class="rts-field">
							<input id="rts-webhook-deactivate" type="checkbox" name="rock_the_slackbot_outgoing_webhooks[deactivate]" value="1"<?php echo checked(isset($webhook[ 'deactivate' ]) && $webhook[ 'deactivate' ], 1); ?> /> <label for="rts-webhook-deactivate"><?php _e( 'Deactivate this webhook', 'rock-the-slackbot' ); ?></label>
							<span class="rts-field-desc"><?php _e( 'For when you need to disable this webhook without deleting your settings.', 'rock-the-slackbot' ); ?></span>
						</td>
					</tr>
					<tr class="buttons">
						<td class="rts-cancel">
							<a class="button" href="<?php echo add_query_arg( array( 'page' => 'rock-the-slackbot' ), admin_url( 'tools.php' ) ); ?>"><?php _e( 'Cancel', 'rock-the-slackbot' ); ?></a>
						</td>
						<td class="rts-submit"><?php submit_button('Save WebHook', 'primary', 'rock_slackbot_save_outgoing_webhook', false); ?></td>
					</tr>
				</table>
			</form><?php

		}

	}

	/**
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function print_tools_page() {

		?><div class="wrap">

			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1><?php
			settings_errors();

			// Show deleted webhook message
			if ( isset( $_REQUEST[ 'webhook_deleted' ] ) && $_REQUEST[ 'webhook_deleted' ] == 1 ) {
				?><div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible below-h2">
					<p><strong><?php _e( 'Your webhook was deleted.', 'rock-the-slackbot' ); ?></strong></p>
					<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e( 'Dismiss this notice.', 'rock-the-slackbot' ); ?></span></button>
				</div><?php
			}

			// Show error deleting webhook message
			else if ( isset( $_REQUEST[ 'error_deleting_webhook' ] ) && $_REQUEST[ 'error_deleting_webhook' ] == 1 ) {
				?><div id="setting-error-settings_error" class="error settings-error notice is-dismissible below-h2">
					<p><strong><?php _e( 'There was an error deleting your webhook.', 'rock-the-slackbot' ); ?></strong></p>
					<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e( 'Dismiss this notice.', 'rock-the-slackbot' ); ?></span></button>
				</div><?php
			}

			?><div id="rock-slackbot-intro-message">
				<span class="slack-logo"></span>
				<p class="slack-message"><?php printf( __( ' Rock The Slackbot helps you manage your websites, and stay on top of changes, by sending notifications (following numerous WordPress events) straight to you and your team inside your %1$sSlack%2$s account. Slack is a team collaboration tool that offers chat rooms organized by topic, as well as private groups and direct messaging. A Slack account is required to use this plugin and is free to use for as long as you want and with an unlimited number of people. %3$sVisit the Slack website%4$s to learn more and sign up.', 'rock-the-slackbot' ), '<a href="https://slack.com/is" target="_blank">', '</a>', '<a href="https://slack.com/" target="_blank">', '</a>' ); ?></p>
			</div>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">

					<div id="postbox-container-1" class="postbox-container">

						<div id="side-sortables" class="meta-box-sortables"><?php

							// If we're viewing an edit or add page, add a link to the main page
							if ( $this->edit_webhook || $this->add_webhook ) {
								?><a id="rock-slackbot-back-button" class="button button-primary rts-dashicons rts-button rts-side-button" href="<?php echo add_query_arg(array('page' => 'rock-the-slackbot'), admin_url('tools.php')); ?>"><span class="dashicons dashicons-arrow-left-alt"></span> <?php _e( 'Back to Main Settings', 'rock-the-slackbot' ); ?></a><?php
							}

							do_meta_boxes( $this->tools_page_id, 'side', array() );

							// If we're viewing an edit page, add a delete button
							if ( $this->edit_webhook ) {
								?><a id="rock-slackbot-delete-button" class="button button-primary rts-dashicons rts-button rts-side-button" href="<?php echo wp_nonce_url( add_query_arg(array('page' => 'rock-the-slackbot', 'delete' => $this->edit_webhook ), admin_url('tools.php')), 'rts_delete_outgoing_webhook' ); ?>"><span class="dashicons dashicons-trash"></span> <?php _e( 'Delete This Webhook', 'rock-the-slackbot' ); ?></a><?php
							}

						?></div> <!-- #side-sortables -->

					</div> <!-- #postbox-container-1 -->

					<div id="postbox-container-2" class="postbox-container">

						<div id="normal-sortables" class="meta-box-sortables"><?php
							do_meta_boxes( $this->tools_page_id, 'normal', array() );
						?></div> <!-- #normal-sortables -->

						<div id="advanced-sortables" class="meta-box-sortables"><?php
							do_meta_boxes( $this->tools_page_id, 'advanced', array() );
						?></div> <!-- #advanced-sortables -->

					</div> <!-- #postbox-container-2 -->

				</div> <!-- #post-body -->
				<br class="clear" />
			</div> <!-- #poststuff -->

		</div><?php

	}

	/**
	 * Register our settings.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function register_settings() {

		// Register the setting that holds our outgoing webhooks
		register_setting( 'rock_the_slackbot_outgoing_webhooks', 'rock_the_slackbot_outgoing_webhooks', array( $this, 'sanitize_outgoing_webhooks_setting' ) );

	}

	/**
	 * Sanitizes the 'rock_the_slackbot_outgoing_webhooks' setting.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param	array - the webhooks we're sanitizing
	 * @return	array - the sanitized webhooks
	 */
	public function sanitize_outgoing_webhooks_setting( $value ) {

		// Get the saved outgoing webhooks
		$saved_outgoing_webhooks = rock_the_slackbot()->get_all_outgoing_webhooks();

		// Only run this code if we're editing - which is set in the edit form
		if ( ! ( isset( $value[ 'editing' ] ) && $value[ 'editing' ] == 1 ) ) {
			return $value;
		}

		// Remove the editing value so it isn't stored
		unset( $value[ 'editing' ] );

		// See if we have a hook ID which means we're editing a current hook
		$edit_hook = isset( $value[ 'ID' ] ) && ! empty( $value[ 'ID' ] ) ? $value[ 'ID' ] : false;

		// If true, we have an error and need to process
		$editing_error = false;

		// 1) Check the name
		if ( ! ( isset( $value[ 'name' ] ) && ! empty( $value[ 'name' ] ) ) ) {

			// We have an error
			$editing_error = true;

			// Add settings error
			add_settings_error( 'rock_the_slackbot_outgoing_webhooks', 'no_name', __('You must provide a name for your outgoing webhook.', 'rock-the-slackbot'), 'error' );

		} else {

			// Trim it up!
			$value[ 'name' ] = trim( $value[ 'name' ] );

		}

		// 2) Check the webhook URL
		if ( ! ( isset( $value[ 'webhook_url' ] ) && ! empty( $value[ 'webhook_url' ] ) ) ) {

			// We have an error
			$editing_error = true;

			// Add settings error
			add_settings_error( 'rock_the_slackbot_outgoing_webhooks', 'no_webhook_url', __('You must provide a webhook URL for your outgoing webhook.', 'rock-the-slackbot'), 'error' );

		} else {

			// Trim it up!
			$value[ 'webhook_url' ] = trim( $value[ 'webhook_url' ] );

		}

		// 3) Make sure the channel starts with either a # or @
		if ( isset( $value[ 'channel' ] ) && ! empty( $value[ 'channel' ] ) ) {

			// If it doesn't, prefix with a #
			if ( ! preg_match( '/^(\#|\@)/i', $value[ 'channel' ] ) ) {
				$value[ 'channel' ] = '#' . $value[ 'channel' ];
			}

			// Trim it up!
			$value[ 'channel' ] = trim( $value[ 'channel' ] );

		}

		// 4) Make sure the icon emoji is wrapped with colons and trimmed
		if ( isset( $value[ 'icon_emoji' ] ) && ! empty( $value[ 'icon_emoji' ] ) ) {
			$value[ 'icon_emoji' ] = ':' . trim( str_replace( ':', '', $value[ 'icon_emoji' ] ) ) . ':';
		}

		// 5) Make sure the icon url is trimmed
		if ( isset( $value[ 'icon_url' ] ) && ! empty( $value[ 'icon_url' ] ) ) {
			$value[ 'icon_url' ] = trim( $value[ 'icon_url' ] );
		}

		// 6) Check the events
		if ( isset( $value[ 'events' ] ) ) {
			foreach( $value[ 'events' ] as &$event ) {

				if ( isset( $event[ 'channel' ] ) && ! empty( $event[ 'channel' ] ) ) {

					// If it doesn't, prefix with a #
					if ( ! preg_match( '/^(\#|\@)/i', $event[ 'channel' ] ) ) {
						$event[ 'channel' ] = '#' . $event[ 'channel' ];
					}

					// Trim it up!
					$event[ 'channel' ] = trim( $event[ 'channel' ] );

				}

			}
		}

		// If we have an error...
		if ( $editing_error ) {

			// If we're adding a hook, stores value info so it can be re-populated
			if ( ! $edit_hook ) {
				set_transient( 'rock_the_slackbot_add_outgoing_webhook', $value, 60 );
			}

			// Get out of here
			return $saved_outgoing_webhooks;

		}

		// If we're editing...
		if ( $edit_hook ) {

			// Go through the saved webhooks and find the one being updated
			foreach ($saved_outgoing_webhooks as &$hook) {
				if ($edit_hook && $edit_hook == $hook[ 'ID' ]) {

					// Was hook modified?
					$hook_was_modified = false;
					foreach( $hook as $hook_key => $hook_value ) {
						if ( ! isset( $value[ $hook_key ] ) || $value[ $hook_key ] !== $value ) {
							$hook_was_modified = true;
							break;
						}
					}

					// If modified, update the date modified
					if ( $hook_was_modified ) {
						$value[ 'date_modified' ] = time();
					}

					// Update the hook
					$hook = $value;

				}
			}

		}

		// If we're adding...
		else {

			// Add the ID
			$value[ 'ID' ] = uniqid();

			// Add date created/modified
			$value[ 'date_created' ] = $value[ 'date_modified' ] = time();

			// Add to the mix
			$saved_outgoing_webhooks[] = $value;

			// Change the referer URL to change add=1 to edit=[ID] so that redirect will show edit screen
			$_REQUEST[ '_wp_http_referer' ] = preg_replace( '/(\&add\=([^\&]*))/i', '&edit=' . $value[ 'ID' ], $_REQUEST[ '_wp_http_referer' ] );

		}

		// Always save all of the webhooks
		return $saved_outgoing_webhooks;

	}

	/**
	 * Handles when someone wants to delete an outgoing webhook.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function delete_outgoing_webhook() {

		// Check for our page
		if ( ! ( isset( $_REQUEST[ 'page' ] ) && 'rock-the-slackbot' == $_REQUEST[ 'page' ] ) ) {
			return false;
		}

		// Make sure we have a webhook ID to delete
		if ( ! ( $hook_id = isset( $_REQUEST[ 'delete' ] ) ? $_REQUEST[ 'delete' ] : false ) ) {
			return false;
		}

		// Check for our nonce name
		if ( ! ( $nonce = isset( $_REQUEST[ '_wpnonce' ] ) ? $_REQUEST[ '_wpnonce' ] : false ) ) {
			return false;
		}

		// Check the nonce itself
		if ( ! wp_verify_nonce( $nonce, 'rts_delete_outgoing_webhook' ) ) {
			$die_message = __('Oops. Looks like something went wrong.', 'rock-the-slackbot');
			wp_die( $die_message, $die_message, array('back_link' => true));
		}

		// Get all of the outgoing webhooks
		if ( $outgoing_webhooks = rock_the_slackbot()->get_all_outgoing_webhooks() ) {

			// Will be true if we deleted a hook
			$deleted_hook = false;

			foreach( $outgoing_webhooks as $webhook_index => $webhook ) {

				// Delete this specific webhook
				if ( isset( $webhook[ 'ID' ] ) && $hook_id == $webhook[ 'ID' ] ) {
					unset( $outgoing_webhooks[ $webhook_index ] );
					$deleted_hook = true;
				}

			}

			// If we deleted, update the option
			if ( $deleted_hook ) {

				// If an empty array, then make null
				if ( empty( $outgoing_webhooks ) ) {
					$outgoing_webhooks = null;
				}

				// Otherwise reorder the webhooks
				else {
					$outgoing_webhooks = array_values( $outgoing_webhooks );
				}

				// Update the option
				update_option( 'rock_the_slackbot_outgoing_webhooks', $outgoing_webhooks );

				// Webhook deleted
				// Redirect to the main page and prompt message
				wp_redirect( add_query_arg( array( 'page' => 'rock-the-slackbot', 'webhook_deleted' => 1 ), admin_url( 'tools.php' ) ) );
				exit;

			}

		}

		// This means there was no webhook with this ID
		// Redirect to the main page and prompt message
		wp_redirect( add_query_arg( array( 'page' => 'rock-the-slackbot', 'error_deleting_webhook' => 1 ), admin_url( 'tools.php' ) ) );
		exit;

	}

	/**
	 * Sets up and stores any pre upgrade information
	 * that will be used for notifications.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function handle_pre_upgrade_information() {
		global $wp_version;

		// Get current filter
		$current_filter = preg_replace( '/^admin\_action\_/i', '', current_filter() );

		// Going to store pre-upgrade plugin/theme/core info for
		// notification that gets sent out after update
		$pre_upgrade_info = array();

		// Will hold type info for processing
		$plugins = array();
		$themes = array();

		switch( $current_filter ) {

			case 'upgrade-plugin':
				$plugins[] = isset( $_REQUEST['plugin'] ) ? trim( $_REQUEST['plugin'] ) : null;
				break;

			case 'do-plugin-upgrade':
				$plugins = isset( $_GET['plugins'] ) ? explode( ',', $_GET['plugins'] ) : ( isset( $_POST['checked'] ) ? (array) $_POST['checked'] : false );
				break;

			case 'update-selected':
				$plugins = isset( $_GET['plugins'] ) ? explode( ',', stripslashes($_GET['plugins']) ) : ( isset( $_POST['checked'] ) ? (array) $_POST['checked'] : false );
				break;

			case 'upgrade-theme':
				$themes[] = isset( $_REQUEST['theme'] ) ? urldecode( $_REQUEST['theme'] ) : null;
				break;

			case 'do-theme-upgrade':
				$themes = isset( $_GET['themes'] ) ? explode( ',', $_GET['themes'] ) : ( isset( $_POST['checked'] ) ? (array) $_POST['checked'] : false );
				break;

			case 'update-selected-themes':
				$themes = isset( $_GET['themes'] ) ? explode( ',', stripslashes($_GET['themes']) ) : ( isset( $_POST['checked'] ) ? (array) $_POST['checked'] : false );
				break;

			case 'upgrade-core':
			case 'do-core-upgrade':
			case 'do-core-reinstall':
				$pre_upgrade_info[ 'core' ] = array(
					'version' => $wp_version,
				);
				break;

		}

		// Process plugins
		if ( $plugins ) {
			foreach( $plugins as $plugin_file ) {
				if ( $plugin = get_plugin_data( WP_CONTENT_DIR . '/plugins/' . $plugin_file ) ) {
					$pre_upgrade_info[ $plugin_file ] = $plugin;
				}
			}
		}

		// Process themes
		if ( $themes ) {
			foreach( $themes as $theme_name ) {
				if ( $theme = wp_get_theme( $theme_name ) ) {
					$pre_upgrade_info[ $theme_name ] = $theme;
				}
			}
		}

		// Store the info
		if ( $pre_upgrade_info ) {
			set_transient( 'rock_the_slackbot_pre_upgrade_information', $pre_upgrade_info, 60 );
		}

	}

}
new Rock_The_Slackbot_Admin;