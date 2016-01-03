# Rock The Slackbot
Rock The Slackbot is a WordPress plugin that helps you stay on top of changes by sending notifications straight to you and your team inside your Slack account.

*You can also [download this plugin from the WordPress.org plugin repo](https://wordpress.org/plugins/rock-the-slackbot/).*

## What is Slack?
[Slack](https://slack.com/is) is a team collaboration tool that offers chat rooms organized by topic, as well as private groups and direct messaging. It's a great way to be productive with your team without clogging up your inbox.

## What is A Slackbot?
[Slackbot](https://slack.zendesk.com/hc/en-us/articles/202026038-Slackbot-your-assistant-notepad-programmable-bot) is Slack's built-in robot, which helps us send messages to you and your team inside your Slack account.

## Why Rock The Slackbot?
Because it can help you manage your websites, and stay on top of changes, by sending notifications (following numerous WordPress events) to your Slackbot who will pass them along to a channel or direct message in your Slack account.

**Rock The Slackbot is multisite-friendly.**

**Rock the Slackbot sends customizable notifications** for the following events:

* When a post is published
* When a post is unpublished
* When a post is updated
* When a post is deleted
* When a post is trashed
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
* When a new comment is added or is awaiting moderation
* When plugins or themes are uploaded
* When plugins or themes are activated

**Each event can be customized to allow you to send different event notifications to different Slack channels**, e.g. you can send core, theme and plugin updates to your "wp-development" channel while all of your post changes go to your "wp-content" channel.

Please use [the Issues section of this repo](https://github.com/bamadesigner/rock-the-slackbot/issues) to suggest features, like other notification events.

**A Slack account is required to use this plugin** and is free to use for as long as you want and with an unlimited number of people. [Visit the Slack website](https://slack.com/) to learn more and sign up.

## Filters

Rock The Slackbot has filters setup to allow you to tweak each notification before it's sent. You can setup a filter for all notifications or drill down by event or specific webhook.

Each filter passes two arguments:
1. $notification_pieces - an array containing the webhook URL (the URL for your Slack account) and the payload (all of the information being sent to Slack)
2. $event_args - an array containing event specific information

### Filter all notifications
    add_filter( 'rock_the_slackbot_notification', 'filter_rock_the_slackbot_notification', 10, 2 );
    function filter_rock_the_slackbot_notification( $notification_pieces, $event_args ) {

        // Change the payload

        // Return the payload
        return $notification_pieces;
    }

### Filter by webhook ID
    // You can find the ID for each of your webhooks on their edit screen in the admin
    add_filter( 'rock_the_slackbot_notification_(webhook_id)', 'filter_rock_the_slackbot_notification_webhook', 10, 2 );
    function filter_rock_the_slackbot_notification_webhook( $notification_pieces, $event_args ) {

      // Change the payload

      // Return the payload
      return $notification_pieces;
    }

### Filter by notification event slug
    // The event slugs are listed below
    add_filter( 'rock_the_slackbot_notification_(notification_event)', 'filter_rock_the_slackbot_notification_event', 10, 2 );
    function filter_rock_the_slackbot_notification_event( $notification_pieces, $event_args ) {

      // Change the payload

      // Return the payload
      return $notification_pieces;
    }

## Notification Events

Including event specific information passed to filters for each notification event.

**Content**

* post_published
    * post - the WP_Post object data of the post that was published
    * old_post_status - the status of the post before it was published
    * new_post_status - the current status of the published post
* post_unpublished
    * post - the WP_Post object data of the post that was unpublished
    * old_post_status - the status of the post before it was unpublished
    * new_post_status - the current status of the unpublished post
* post_updated
    * post_id - the post ID of the post you updated
    * post_before - the WP_Post object data of the post before it was updated
    * post_after - the WP_Post object data of the post after it was updated
* post_deleted
    * post - the WP_Post object data of the post that was deleted
* post_trashed
    * post - the WP_Post object data of the post that was trashed
* is_404
    * url - the URL that threw the 404 error
    * referer - the HTTP referer (may not always be defined)
    * ip_address - the IP address of the user who visited the URL (may not always be defined)
    * user_agent - the user agent of the user who visited the URL (may not always be defined)
    * wp_query - the WordPress query variables
    * mysql_request - the MySQL query request

**Menus**

* menu_item_deleted
    * menu - the WP_Post object data of the menu that held the menu item
    * menu_item_id - the post ID of the menu item that was deleted

**Media**

* add_attachment
    * attachment_post - the WP_Post object data for the attachment you added
* edit_attachment
    * attachment_post - the WP_Post object data for the attachment you edited
* delete_attachment
    * attachment_post - the WP_Post object data for the attachment you deleted

**Users**

* user_added
    * user - the WP_User data for the user you added
* user_deleted
    * user - the WP_User data for the user you deleted
* set_user_role
    * user - the WP_User data for the user whose role was changed
    * current_user_roles - the current user roles for the user whose role was changed
    * old_user_roles - the old user roles for the user whose role was changed

**Updates**

* core_update_available
    * current_version - the current version number of WordPress core
    * new_version - the version number for the WordPress core update
* core_updated
    * current_version - the current version number of WordPress core after the update
    * old_version - the old version number for WordPress core before the update
* plugin_update_available
    * plugins - includes an array of the plugins who have updates available
* plugin_updated
    * plugin - includes an array of the plugin(s) that were updated
* theme_update_available
    * themes - includes an array of the themes who have updates available
* theme_updated
    * theme - includes an array of the theme(s) that were updated

## Installation

1. Upload 'rock-the-slackbot' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Tools > Rock The Slackbot