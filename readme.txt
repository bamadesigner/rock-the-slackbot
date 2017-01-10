=== Rock The Slackbot ===
Contributors: bamadesigner
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=ZCAN2UX7QHZPL&lc=US&item_name=Rachel%20Carden%20%28Rock%20The%20Slackbot%29&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: slack, slackbot, chat, collaboration, notification, team
Requires at least: 3.0
Tested up to: 4.7
Stable tag: 1.1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Rock The Slackbot helps you stay on top of changes by sending notifications straight to you and your team inside your Slack account.

== Description ==

= What is Slack? =
[Slack](https://slack.com/is) is a team collaboration tool that offers chat rooms organized by topic, as well as private groups and direct messaging. It's a great way to be productive with your team without clogging up your inbox.

= What is A Slackbot? =
[Slackbot](https://slack.zendesk.com/hc/en-us/articles/202026038-Slackbot-your-assistant-notepad-programmable-bot) is Slack's built-in robot, which helps us send messages to you and your team inside your Slack account.

= Why Rock The Slackbot? =
Because it can help you manage your websites, and stay on top of changes, by sending notifications (following numerous WordPress events) to your Slackbot who will pass them along to a channel or direct message in your Slack account.

**Rock the Slackbot is multisite-friendly.**

**Rock the Slackbot sends customizable notifications** for the following events:

* When a post is published
* When a post is unpublished
* When a post is updated
* When a post is deleted
* When a post is trashed
* When a comment is added
* When a comment is approved
* When a comment is unapproved
* When a comment is marked as spam
* When a comment is trashed
* When a 404 error is thrown
* When a menu item is deleted
* When media is added
* When media is edited
* When media is deleted
* When a user is added
* When a user is deleted
* When a user's role is changed
* When a plugin, theme, or core update is available
* When a plugin, theme, or core is updated

**I'm working to add the following events:**

* When menu item is added
* When plugins or themes are uploaded
* When plugins or themes are activated

**Each event can be customized to allow you to send different event notifications to different Slack channels**, e.g. you can send core, theme and plugin updates to your "wp-development" channel while all of your post changes go to your "wp-content" channel.

Please use [the Issues section of this plugin's GitHub repo](https://github.com/bamadesigner/rock-the-slackbot/issues) to suggest features, like other notification events.

**A Slack account is required to use this plugin** and is free to use for as long as you want and with an unlimited number of people. [Visit the Slack website](https://slack.com/) to learn more and sign up.

== Installation ==

1. Upload 'rock-the-slackbot' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Tools > Rock The Slackbot

== Changelog ==

= 1.1.2 =
* You can now cancel sending the notification by returning false to "rock_the_slackbot_notification" or "rock_the_slackbot_outgoing_webhook_payload" filter.
* Added "When Slack Notifications Fail" setting so users can control whether or not emails are sent when Slack notifications fail.
* Added 'rock_the_slackbot_error_email' filter which allows you to customize the error "didn't send to Slack" email.
* Added notifications for 'When a post is drafted', 'When a post is pending review', and 'When a post is scheduled'.
* Added notifications for when a comment is added, approved, unapproved, marked as spam, and trashed.
* Fixed issue where channel had to be set for messages to send.
* Added translations for English (Australia), French (France), German, Portuguese (Brazil), and Spanish (Spain).
* Added messaging to clarify use of WordPress icon in Slack messages.

= 1.1.1 =
* Added process to test webhook URL on settings page.
* Can now send the same notification to multiple Slack channels.
* Added event-specific information to go with each filter.
* Created rock_the_slackbot()->send_webhook_message() to make it easy for users to send simple, custom messages to Slack via webhook.
* Updated the notification filters so they include the notification pieces, the slug of the notification event, and event specific information so you can make adjustments according to the event.
* Fixed bug where filters weren't "inheriting" each other.
* Added 'rock_the_slackbot_outgoing_webhook_payload' filter which allows you to change any payload sent to Slack in an outgoing webhook.
* Fixed where the webhook URL wasn't being sent to the filters.
* If you use send_notification() from the Rock_The_Slackbot_Notifications class, that class has been heavily changed. You can now use rock_the_slackbot()->send_webhook_message() to send a custom message.

= 1.1.0 =
* Rock The Slackbot is now multisite compatible!
* Setup Slack notification when a plugin, theme, or core update is available - will need to enable
* Setup Slack notification when a user's role has changed - will need to enable
* Adding wp_get_referer(), IP address, and HTTP_USER_AGENT fields to the 404 notification.

= 1.0.0 =
Plugin launch

== Upgrade Notice ==

= 1.1.2 =
* You can now cancel sending the notification by returning false to "rock_the_slackbot_notification" or "rock_the_slackbot_outgoing_webhook_payload" filter.
* Added "When Slack Notifications Fail" setting so users can control whether or not emails are sent when Slack notifications fail.
* Added 'rock_the_slackbot_error_email' filter which allows you to customize the error "didn't send to Slack" email.
* Added notifications for 'When a post is drafted', 'When a post is pending review', and 'When a post is scheduled'.
* Added notifications for when a comment is added, approved, unapproved, marked as spam, and trashed.
* Fixed issue where channel had to be set for messages to send.
* Added translations for English (Australia), French (France), German, Portuguese (Brazil), and Spanish (Spain).
* Added messaging to clarify use of WordPress icon in Slack messages.

= 1.1.1 =
* Added process to test webhook URL on settings page.
* Can now send the same notification to multiple Slack channels.
* Added event-specific information to go with each filter.
* Created rock_the_slackbot()->send_webhook_message() to make it easy for users to send simple, custom messages to Slack via webhook.
* Updated the notification filters so they include the notification pieces, the slug of the notification event, and event specific information so you can make adjustments according to the event.
* Fixed bug where filters weren't "inheriting" each other.
* Added 'rock_the_slackbot_outgoing_webhook_payload' filter which allows you to change any payload sent to Slack in an outgoing webhook.
* Fixed where the webhook URL wasn't being sent to the filters.
* If you use send_notification() from the Rock_The_Slackbot_Notifications class, that class has been heavily changed. You can now use rock_the_slackbot()->send_webhook_message() to send a custom message.

= 1.1.0 =
* Rock The Slackbot is now multisite compatible!
* Setup Slack notification when a plugin, theme, or core update is available - will need to enable
* Setup Slack notification when a user's role has changed - will need to enable
* Adding wp_get_referer(), IP address, and HTTP_USER_AGENT fields to the 404 notification.

== Send A Simple Slack Message==

You can use the following send_webhook_message() function to send a simple message to your Slack account.

**The function accepts the following parameters:**

1. $webhook_id_or_url - provide the webhook URL or the ID of one stored in settings
2. $message - the message you want to send
3. $channel - OPTIONAL - the channel you want to send message to. Prefix with # for a specific channel or @ for a specific user. Will use default channel if nothing is passed.

`// Use this function to send a simple message to Slack
rock_the_slackbot()->send_webhook_message( '564d3c1cdf52d', 'this is a test', '#testchannel' );`

== Filters ==

Rock The Slackbot has filters setup to allow you to tweak each WordPress notification before it's sent. You can setup a filter for all notifications or drill down by event or specific webhook.

**Each notification filter passes three arguments:**

1. $notification - an array containing the notification information: webhook URL (the URL for your Slack account) and the payload (all of the information being sent to Slack) for the notification
2. $notification_event - the slug of the notification event
    * Will be false if you send a custom Slack notification that doesn't involve a WordPress event
3. $event_args - an array containing notification event specific information
    * Will be false if you send a custom Slack notification that doesn't involve a WordPress event

**See *Notification Events* below to learn which information is passed to the filters for each notification event.**

= Filter all WordPress notifications =
`add_filter( 'rock_the_slackbot_notification', 'filter_rock_the_slackbot_notification', 10, 3 );
function filter_rock_the_slackbot_notification( $notification, $notification_event, $event_args ) {

    // Change the pieces

    // Return the notification
    return $notification;
}`

= Filter WordPress notifications by webhook ID =
`// You can find the ID for each of your webhooks on their edit screen in the admin
add_filter( 'rock_the_slackbot_notification_(webhook_id)', 'filter_rock_the_slackbot_notification_webhook', 10, 3 );
function filter_rock_the_slackbot_notification_webhook( $notification, $notification_event, $event_args ) {

  // Change the pieces

  // Return the notification
  return $notification;
}`

= Filter WordPress notifications by event slug =
`// The event slugs are listed below
add_filter( 'rock_the_slackbot_notification_(notification_event)', 'filter_rock_the_slackbot_notification_event', 10, 3 );
function filter_rock_the_slackbot_notification_event( $notification, $notification_event, $event_args ) {

  // Change the pieces

  // Return the notification
  return $notification;
}`

= Filter all outgoing webhook payloads that are sent to Slack =

Whether it's a WordPress notification or a simple Slack message, all messages to Slack are sent as a payload in an outgoing webhook. This filter allows you to change any payload sent to Slack in an outgoing webhook.

`add_filter( 'rock_the_slackbot_outgoing_webhook_payload', 'filter_rock_the_slackbot_outgoing_webhook_payload', 10, 2 );
function filter_rock_the_slackbot_outgoing_webhook_payload( $payload, $webhook_url ) {

    // Change the payload

    // Return the payload
    return $notification;
}`

== Notification Events ==

Including event specific information passed to filters for each notification event.

= Content =

* post_published
    * **Passed To Filters**
        * post - the WP_Post object data of the post that was published
        * old_post_status - the status of the post before it was published
        * new_post_status - the current status of the published post
* post_unpublished
    * **Passed To Filters**
        * post - the WP_Post object data of the post that was unpublished
        * old_post_status - the status of the post before it was unpublished
        * new_post_status - the current status of the unpublished post
* post_updated
    * **Passed To Filters**
        * post_id - the post ID of the post you updated
        * post_before - the WP_Post object data of the post before it was updated
        * post_after - the WP_Post object data of the post after it was updated
* post_deleted
    * **Passed To Filters**
        * post - the WP_Post object data of the post that was deleted
* post_trashed
    * **Passed To Filters**
        * post - the WP_Post object data of the post that was trashed
* is_404
    * **Passed To Filters**
        * url - the URL that threw the 404 error
        * referer - the HTTP referer (may not always be defined)
        * ip_address - the IP address of the user who visited the URL (may not always be defined)
        * user_agent - the user agent of the user who visited the URL (may not always be defined)
        * wp_query - the WordPress query variables
        * mysql_request - the MySQL query request

= Menus =

* menu_item_deleted
    * **Passed To Filters**
        * menu - the WP_Post object data of the menu that held the menu item
        * menu_item_id - the post ID of the menu item that was deleted

= Media =

* add_attachment
    * **Passed To Filters**
        * attachment_post - the WP_Post object data for the attachment you added
* edit_attachment
    * **Passed To Filters**
        * attachment_post - the WP_Post object data for the attachment you edited
* delete_attachment
    * **Passed To Filters**
        * attachment_post - the WP_Post object data for the attachment you deleted

= Users =

* user_added
    * **Passed To Filters**
        * user - the WP_User data for the user you added
* user_deleted
    * **Passed To Filters**
        * user - the WP_User data for the user you deleted
* set_user_role
    * **Passed To Filters**
        * user - the WP_User data for the user whose role was changed
        * current_user_roles - the current user roles for the user whose role was changed
        * old_user_roles - the old user roles for the user whose role was changed

= Updates =

* core_update_available
    * **Passed To Filters**
        * current_version - the current version number of WordPress core
        * new_version - the version number for the WordPress core update
* core_updated
    * **Passed To Filters**
        * current_version - the current version number of WordPress core after the update
        * old_version - the old version number for WordPress core before the update
* plugin_update_available
    * **Passed To Filters**
        * plugins - includes an array of the plugins who have updates available
* plugin_updated
    * **Passed To Filters**
        * plugin - includes an array of the plugin(s) that were updated
* theme_update_available
    * **Passed To Filters**
        * themes - includes an array of the themes who have updates available
* theme_updated
    * **Passed To Filters**
        * theme - includes an array of the theme(s) that were updated

== Filter Examples ==

You can use a filter to change the Slack notification to go to a different Slack channel according to post information, like the post category:

`add_filter( 'rock_the_slackbot_notification', 'filter_rock_the_slackbot_notification', 10, 3 );
function filter_rock_the_slackbot_notification( $notification, $notification_event, $event_args ) {

   // Only run filter for specific events
   switch ( $notification_event ) {

       // This way you can set which events you want to use
       case 'post_published':
       case 'post_unpublished':
       case 'post_updated':
       case 'post_deleted':
       case 'post_trashed':

           // Get category names
           $categories = wp_get_post_categories( $event_args[ 'post_id' ], array( 'fields' => 'names' ) );

           // Replace 'CategoryName' with the category you're looking for
           if ( in_array( 'CategoryName', $categories ) ) {

               // Change the channel in the payload
               // Make sure you prefix the channel name with #
               $notification[ 'payload' ][ 'channel' ] = '#newchannel';

           }
           break;
   }

   // Return the notification
   return $notification;
}`