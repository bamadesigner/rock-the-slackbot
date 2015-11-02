<?php

// if uninstall not called from the WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();

// Delete plugin options
delete_option( 'rock_the_slackbot_outgoing_webhooks' );