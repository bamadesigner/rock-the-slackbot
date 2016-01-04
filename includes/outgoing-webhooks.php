<?php

class Rock_The_Slackbot_Outgoing_Webhooks {

	/**
	 * Holds the class instance.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @var		Rock_The_Slackbot_Outgoing_Webhooks
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return	Rock_The_Slackbot_Outgoing_Webhooks
	 */
	public static function instance() {
		if ( ! isset( static::$instance ) ) {
			$className = __CLASS__;
			static::$instance = new $className;
		}
		return static::$instance;
	}

	/**
	 * This class sends outgoing webhooks to Slack.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct() {}

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
	 * Sends a payload to Slack.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param   string - $webhook_url - send the payload to this URL
	 * @param   array - $payload - the payload information
	 * @return  boolean - whether or not it was sent without error
	 */
	function send_payload( $webhook_url, $payload = array() ) {

		// Set defaults
		$payload_defaults = array(
			'channel'       => null,
			'username'      => get_bloginfo( 'name' ),
			'text'          => __( 'This is a WordPress to Slack message.', 'rock-the-slackbot' ),
			'icon_emoji'	=> null,
			'icon_url'		=> trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) ) . 'images/wordpress-icon-emoji.png',
			'attachments'   => array(),
		);

		// Mix payload with defaults
		$payload = wp_parse_args( $payload, $payload_defaults );

		// Set attachment defaults
		$attachment_defaults = array(
			'fallback'      => null, // A plain-text summary of the attachment. This text will be used in clients that don't show formatted text (eg. IRC, mobile notifications) and should not contain any markup.
			'color'         => '#21759b', //This value is used to color the border along the left side of the message attachment.
			'pretext'       => null, // Optional text that appears above the message attachment block.
			'text'          => null,
			'title'         => null,
			'title_link'    => null,
			'author_name'   => null, // Small text used to display the author's name
			'author_link'   => null, // A valid URL that will hyperlink the author_name text mentioned above
			'author_icon'   => null, // A valid URL that displays a small 16x16px image to the left of the author_name text.
			'image_url'     => null, // A valid URL to an image file that will be displayed inside a message attachment
			'thumb_url'     => null, // A valid URL to an image file that will be displayed as a thumbnail on the right side of a message attachment
			'fields'        => array(),
		);

		// Set field defaults
		$field_defaults = array(
			'title' => null,
			'value' => null,
			'short' => false,
		);

		// Go through each attachment and mix with defaults
		if ( ! empty( $payload[ 'attachments' ] ) && is_array( $payload[ 'attachments' ] ) ) {

			// Setup each attachment
			foreach( $payload[ 'attachments' ] as &$attachment ) {

				// Mix attachment with defaults
				$attachment = wp_parse_args( $attachment, $attachment_defaults );

				// Go through each attachment and mix with defaults
				if ( ! empty( $attachment[ 'fields' ] ) && is_array( $attachment[ 'fields' ] ) ) {

					// Setup each field
					foreach( $attachment[ 'fields' ] as &$field ) {

						// Mix field with defaults
						$field = wp_parse_args( $field, $field_defaults );

					}

				}

			}
		}

		// See if we have stored information for the main filter
		$notification_event = wp_cache_get( 'notification_event', 'rock_the_slackbot' );
		$notification_event_args = wp_cache_get( 'notification_event_args', 'rock_the_slackbot' );

		// Allows you to filter notifications
		$notification_pieces = (array) apply_filters( 'rock_the_slackbot_notification', compact( array( 'webhook_url', 'payload' ) ), $notification_event, $notification_event_args );

		// Delete the stored information
		wp_cache_delete( 'notification_event', 'rock_the_slackbot' );
		wp_cache_delete( 'notification_event_args', 'rock_the_slackbot' );

		// Extract the filtered notification pieces
		extract( $notification_pieces );

		// No point if we don't have a channel
		if ( ! $payload[ 'channel' ] ) {
			return false;
		}

		// Send to Slack
		$slack_response = wp_remote_post( $webhook_url, array(
			'body'      => json_encode( $payload ),
			'headers'   => array(
				'Content-Type' => 'application/json',
			)
		));

		// If there's an error, send an email
		if ( is_wp_error( $slack_response ) ) {

			// @TODO add settings to disable this or change who the email goes to
			// @TODO be able to filter this email

			// Set email to be HTML
			add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

			// Build email message
			$message = __( 'There was an error when trying to post to Slack from WordPress.', 'rock-the-slackbot' );

			// Add payload URL and channel
			$message .= "\n\n<br /><br /><strong>" . __( 'Payload URL', 'rock-the-slackbot' ) . ":</strong> {$webhook_url}";
			$message .= "\n<br /><strong>" . __( 'Channel', 'rock-the-slackbot' ) . ":</strong> " . $payload[ 'channel' ];

			// Fix any links in the general text message
			if ( ! empty( $payload[ 'text' ] ) ) {

				// Replace Slack links
				$payload[ 'text' ] = rock_the_slackbot()->unformat_slack_links( $payload[ 'text' ] );

			}

			// Add general message
			$message .= "\n\n<br /><br /><strong>" . __( 'Message', 'rock-the-slackbot' ) . ":</strong> " . $payload[ 'text' ];

			// Add attachment info
			if ( isset( $payload[ 'attachments' ] ) ) {

				$message .= "\n\n<br /><br /><strong>" . __( 'Attachments', 'rock-the-slackbot' ) . ":</strong>";
				foreach( $payload[ 'attachments' ] as $attachment ) {
					$message .= "\n<br />";

					// Add fields
					if ( isset( $attachment[ 'fields' ] ) ) {
						foreach( $attachment[ 'fields' ] as $field ) {
							$message .= "\n\t<br />&nbsp;&nbsp;&nbsp;&nbsp;<strong>" . $field[ 'title' ] . ":</strong> " . $field[ 'value' ];
						}
					}

				}
			}

			// Send email notification to the admin
			wp_mail( get_bloginfo( 'admin_email' ), __( 'WordPress to Slack error', 'rock-the-slackbot' ), $message );

			// Reset content-type to avoid conflicts
			remove_filter( 'wp_mail_content_type',  array( $this, 'set_html_content_type' ) );

			// Return an error
			return new WP_Error( 'slack_outgoing_webhook_error', $slack_response->get_error_message() );

		}

		return true;

	}

	/**
	 * Set our error emails to be HTML.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  string - email content type
	 */
	public function set_html_content_type() {
		return 'text/html';
	}

}

/**
 * Returns the instance of our Rock_The_Slackbot_Outgoing_Webhooks class.
 *
 * Will come in handy for anyone who wants to
 * manually send an outgoing webhook outside of the
 * plugin's control.
 *
 * @since	1.0.0
 * @access	public
 * @return	Rock_The_Slackbot_Outgoing_Webhooks
 */
function rock_the_slackbot_outgoing_webhooks() {
	return Rock_The_Slackbot_Outgoing_Webhooks::instance();
}