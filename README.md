# Rock The Slackbot
Rock The Slackbot is a WordPress plugin that helps you stay on top of changes by sending notifications straight to you and your team inside your Slack account.

*You can also [download this plugin from the WordPress.org plugin repo](https://wordpress.org/plugins/rock-the-slackbot/).*

##What is Slack?
[Slack](https://slack.com/is) is a team collaboration tool that offers chat rooms organized by topic, as well as private groups and direct messaging. It's a great way to be productive with your team without clogging up your inbox.

##What is A Slackbot?
[Slackbot](https://slack.zendesk.com/hc/en-us/articles/202026038-Slackbot-your-assistant-notepad-programmable-bot) is Slack's built-in robot, which helps us send messages to you and your team inside your Slack account.

##Why Rock The Slackbot?
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

##Filters

Rock The Slackbot has filters setup to allow you to tweak each notification before it's sent. You can setup a filter for all notifications or drill down by event or specific webhook.

Each filter passes the same argument: an array containing the webhook URL (the URL for your Slack account) and the payload (all of the information being sent to Slack).

    // Filter all notifications
    add_filter( 'rock_the_slackbot_notification', 'filter_rock_the_slackbot_notification' );
    function filter_rock_the_slackbot_notification( $notification_pieces ) {

        // Change the payload

        // Return the payload
        return $notification_pieces;
    }

    // Filter by webhook ID
    // You can find the ID for each of your webhooks on their edit screen in the admin
    add_filter( 'rock_the_slackbot_notification_(webhook_id)', 'filter_rock_the_slackbot_notification_webhook' );
    function filter_rock_the_slackbot_notification_webhook( $notification_pieces ) {

      // Change the payload

      // Return the payload
      return $notification_pieces;
    }

    // Filter by notification event slug
    // The event slugs are listed below
    add_filter( 'rock_the_slackbot_notification_(notification_event)', 'filter_rock_the_slackbot_notification_event' );
    function filter_rock_the_slackbot_notification_event( $notification_pieces ) {

      // Change the payload

      // Return the payload
      return $notification_pieces;
    }

###Event Slugs

**Content**

* post_published
* post_unpublished
* post_updated
* post_deleted
* post_trashed
* is_404

**Menus**

* menu_item_deleted

**Media**

* add_attachment
* edit_attachment
* delete_attachment

**Users**

* user_added
* user_deleted
* set_user_role

**Updates**

* core_update_available
* core_updated
* plugin_update_available
* plugin_updated
* theme_update_available
* theme_updated


### Example Usage

**Note:** The best practice when writing filters, is to use a plugin which adds and manages additional filters within Wordpress. For example: [add-actions-and-filters](https://wordpress.org/support/view/plugin-reviews/add-actions-and-filters)

Let's assume that we'd like to remove extra fields from the notification sent to Slack. We should first examine the structure of `$notification_pieces`, which the filter receives for a `post_published` event:

```
(
    [payload] => Array
        (
            [text] => GENERAL_MESSAGE
            [channel] => #channel_name
            [attachments] => Array
                (
                    [0] => Array
                        (
                            [fallback] => GENERAL_MESSAGE
                            [text] => TEXT
                            [title] => TITLE
                            [title_link] => TITLE_LINK
                            [author_name] => AUTHOR_NAME
                            [author_link] => AUTHOR_LINK
                            [author_icon] => AUTHOR_ICON
                            [fields] => Array
                                (
                                    [0] => Array
                                        (
                                            [title] => Content Author
                                            [value] => AUTHOR_NAME
                                            [short] => 1
                                        )
                                    [1] => Array
                                        (
                                            [title] => Edit the Content
                                            [value] => EDIT_POST_LINK
                                            [short] => 1
                                        )
                                    [2] => Array
                                        (
                                            [title] => Old Status
                                            [value] => OLD_STATUS
                                            [short] => 1
                                        )
                                    [3] => Array
                                        (
                                            [title] => Current Status
                                            [value] => NEW_STATUS
                                            [short] => 1
                                        )
                                    [4] => Array
                                        (
                                            [title] => Content Type
                                            [value] => POST_TYPE
                                            [short] => 1
                                        )
                                )
                        )
                )
        )
)
```

Thus, with the following code, we can remove all extra fields:

```
function remove_extra_fields_from_slack_notification( $notification_pieces ) {
    // Change the payload - remove unwanted fields.
    unset($notification_pieces['payload']['attachments'][0]['fields']);

    // Return the payload
    return $notification_pieces;
}

add_filter( 'rock_the_slackbot_notification_WEBHOOK_ID', 'remove_extra_fields_from_slack_notification' );
```

##Installation

1. Upload 'rock-the-slackbot' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Tools > Rock The Slackbot
