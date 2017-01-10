<?php
/**
 * Holds the functionality
 * for outgoing webhooks.
 *
 * @package  Rock_The_Slackbot
 */

/**
 * Class that holds all of the
 * functionality for our outgoing webhooks.
 *
 * @package Rock_The_Slackbot
 */
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
		if ( ! isset( self::$instance ) ) {
			$class_name = __CLASS__;
			self::$instance = new $class_name;
		}
		return self::$instance;
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
	 * @param   string - $webhook_url - send the payload to this URL.
	 * @param   array - $payload - the payload information.
	 * @return  boolean - whether or not it was sent without error
	 */
	public function send_payload( $webhook_url, $payload = array() ) {

		// Set defaults.
		$payload_defaults = array(
			'channel'       => null,
			'username'      => get_bloginfo( 'name' ),
			'text'          => sprintf( __( 'This is a %1$s to %2$s message.', 'rock-the-slackbot' ), 'WordPress', 'Slack' ),
			'icon_emoji'	=> null,
			'icon_url'		=> trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) ) . 'assets/images/wordpress-icon-emoji.png',
			'attachments'   => array(),
		);

		// Mix payload with defaults.
		$payload = wp_parse_args( $payload, $payload_defaults );

		// Set attachment defaults.
		$attachment_defaults = array(
			'fallback'      => null, // A plain-text summary of the attachment. This text will be used in clients that don't show formatted text (eg. IRC, mobile notifications) and should not contain any markup.
			'color'         => '#21759b', // This value is used to color the border along the left side of the message attachment.
			'pretext'       => null, // Optional text that appears above the message attachment block.
			'text'          => null,
			'title'         => null,
			'title_link'    => null,
			'author_name'   => null, // Small text used to display the author's name.
			'author_link'   => null, // A valid URL that will hyperlink the author_name text mentioned above.
			'author_icon'   => null, // A valid URL that displays a small 16x16px image to the left of the author_name text.
			'image_url'     => null, // A valid URL to an image file that will be displayed inside a message attachment.
			'thumb_url'     => null, // A valid URL to an image file that will be displayed as a thumbnail on the right side of a message attachment.
			'fields'        => array(),
		);

		// Set field defaults.
		$field_defaults = array(
			'title' => null,
			'value' => null,
			'short' => false,
		);

		// Go through each attachment and mix with defaults.
		if ( ! empty( $payload['attachments'] ) && is_array( $payload['attachments'] ) ) {

			// Setup each attachment.
			foreach ( $payload['attachments'] as &$attachment ) {

				// Mix attachment with defaults.
				$attachment = wp_parse_args( $attachment, $attachment_defaults );

				// Go through each attachment and mix with defaults.
				if ( ! empty( $attachment['fields'] ) && is_array( $attachment['fields'] ) ) {

					// Setup each field.
					foreach ( $attachment['fields'] as &$field ) {

						// Mix field with defaults.
						$field = wp_parse_args( $field, $field_defaults );

					}
				}
			}
		}

		// Allows you to filter the payload.
		$payload = apply_filters( 'rock_the_slackbot_outgoing_webhook_payload', $payload, $webhook_url );

		// If returned false or empty, don't send the payload.
		if ( empty( $payload ) ) {
			return false;
		}

		// See if we have multiple channels.
		$channels = ! empty( $payload['channel'] ) ? $payload['channel'] : array();

		// Make sure its an array.
		if ( ! is_array( $channels ) ) {
			$channels = explode( ',', str_replace( ' ', '', $channels ) );
		}

		// If channel is empty, add a blank one so it sends to the default channel.
		if ( empty( $channels ) ) {
			$channels[] = '';
		}

		// Will hold any errors.
		$slack_errors = new WP_Error();

		// Try to send to each channel.
		foreach ( $channels as $channel ) {

			// Add channel to the payload.
			$payload['channel'] = $channel;

			// Send to Slack.
			$slack_response = wp_remote_post( $webhook_url, array(
				'body'      => json_encode( $payload ),
				'headers'   => array(
					'Content-Type' => 'application/json',
				),
			));

			// Handle errors.
			if ( is_wp_error( $slack_response ) ) {

				// Set an error.
				$slack_errors->add( 'slack_outgoing_webhook_error', $slack_response->get_error_message() );

			} elseif ( ! empty( $slack_response['response'] )
				&& ! empty( $slack_response['response']['code'] )
				&& '200' != $slack_response['response']['code'] ) {

				// Set an error.
				$slack_errors->add( 'slack_outgoing_webhook_error', sprintf( __( 'The payload did not send to %s.', 'rock-the-slackbot' ), 'Slack' ) );

			}
		}

		// If errors, return errors.
		$error_messages = $slack_errors->get_error_messages();
		if ( ! empty( $error_messages ) ) {
			return $slack_errors;
		}

		return true;
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
