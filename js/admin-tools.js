(function( $ ) {
	'use strict';

	// When the document is ready...
	$(document).ready(function() {

		// Setup the tooltips
		$( '.rts-tooltip' ).tooltip();

		// Setup required fields
		$( '.rts-field.rts-field-required' ).each(function() {

			// Define the field <td>
			var $rts_field_td = $(this);

			// Check the input
			$rts_field_td.find('.rts-input-required').on('keyup change',function() {

				if ( $(this).val() != '' ) {

					// If the input has a value, remove the error messages
					$rts_field_td.removeClass('rts-field-error').removeClass('rts-field-is-blank');

				} else {

					// If the input doesn't have a value, show the error messages
					$rts_field_td.addClass('rts-field-error').addClass('rts-field-is-blank');

				}

			});
		});

		// Define webhook URL field
		var $rts_webhook_url_input = $('#rts-webhook-url-input');

		// Check webhook URL input
		rts_check_webhook_url_input();

		// Setup the timer
		var $rts_webhook_url_typing_timer;
		var $rts_webhook_url_typing_interval = 500; // time in ms

		// On keyup, start the countdown
		$rts_webhook_url_input.on('keyup', function () {
			clearTimeout( $rts_webhook_url_typing_timer );
			$rts_webhook_url_typing_timer = setTimeout( rts_check_webhook_url_input, $rts_webhook_url_typing_interval );
		});

		// On keydown, clear the countdown
		$rts_webhook_url_input.on('keydown', function () {
			clearTimeout( $rts_webhook_url_typing_timer );
		});
		
		// Check webhook URL input
		$rts_webhook_url_input.on('change',function() {
			rts_check_webhook_url_input();
		});

		// Close the thickbox
		$('.rts-close-thickbox').on('click', function($event){
			$event.preventDefault();
			// Remove the thickbox
			tb_remove();
			// Wait a second so the thickbox fades before things change
			setTimeout( rts_reset_test_webhook_url_popup, 1000 );
		});

		// When ESC is pressed, make sure the popup is reset
		$(document).on('keyup',function($event) {
			if ($event.keyCode == 27) {
				rts_reset_test_webhook_url_popup();
			}
		});

		// Test the webhook URL
		$('#rts-test-webhook-url-init').on('click',function($event) {
			$event.preventDefault();
			rts_test_webhook_url( $rts_webhook_url_input.val() );
		});

		// Take care of event choices
		$( '.rts-events .rts-event-choice' ).each( function() {

			// Get the event choice
			var $event_choice = $(this);

			// Check the event choice
			$event_choice.rts_check_event_choice();

			// When active checkbox is checked
			$(this).find('.rts-event-choice-active-field').on('change',function(){
				$event_choice.rts_check_event_choice();
			});

		});

		// Setup "select all events" button
		$( '#rts-select-all-events').on( 'click', function($event) {
			$event.preventDefault();

			if ( $(this).hasClass( 'all-selected' ) ) {
				$(this).removeClass( 'all-selected' );
				$('.rts-events .rts-event-choice .rts-event-choice-active-field').prop('checked', false).trigger('change');
			} else {
				$(this).addClass( 'all-selected' );
				$( '.rts-events .rts-event-choice .rts-event-choice-active-field' ).prop('checked', true).trigger( 'change' );
			}

		});

		// Ask for confirmation before deleting webhooks
		$( '#rock-slackbot-delete-button').on( 'click', function( $event ) {
			var $confirm = confirm( rock_the_slackbot.delete_webhook_conf );
			if ( $confirm != true ) {
				$event.preventDefault();
			}
		});

	});

	///// FUNCTIONS /////

	// Test the webhook URL
	function rts_test_webhook_url( $webhook_url ) {

		// Reset the popup
		rts_reset_test_webhook_url_popup();

		// Set the current width and add loading class
		$('#rts-test-webhook-url-init').width($('#rts-test-webhook-url-init').width()).height($('#rts-test-webhook-url-init').height()).addClass( 'loading' );

		// Will hold the response
		var $response_message = null;

		// Did the test work?
		var $test_worked = false;

		// Send an AJAX call to test the URL
		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			async: true,
			cache: false,
			data: {
				action: 'test_webhook_url',
				webhook_url: $webhook_url,
				channel: $('#rts-webhook-channel').val(), // If blank, will send to default channel
			},
			success: function( $response ) {

				// There was an error sending the message to Slack
				if ( $response.error !== undefined && $response.error != '' ) {
					$test_worked = false;
					$response_message = rock_the_slackbot.webhook_test_responses.error;
				}

				// This means the test message was sent to Slack
				else if ( $response.sent !== undefined && $response.sent > 0 ) {
					$test_worked = true;
					$response_message = rock_the_slackbot.webhook_test_responses.success;
				}

				// This means the test message did not send but Slack did not send an error
				else {
					$test_worked = false;
					$response_message = rock_the_slackbot.webhook_test_responses.failed;
				}

			},
			complete: function( $jqXHR, $textStatus ) {

				// Build response message
				var $response_message_span = null;
				if ( $test_worked ) {
					$response_message_span = $( '<span class="rts-response rts-response-success">' + $response_message + '</span>' );
					$('#rts-test-webhook-url-init').removeClass('button-primary');
					$('#rts-test-webhook-url-close').html( rock_the_slackbot.close );
				} else {
					$response_message_span = $( '<span class="rts-response rts-response-fail">' + $response_message + '</span>' );
				}

				// Add response message
				$('#rts-popup-test-webhook-message').append(' ').append( $response_message_span );

				// Tweak text
				$('#rts-test-webhook-url-init').html('<span>' + rock_the_slackbot.webhook_send_another + '</span>');

				// Reset the button
				$('#rts-test-webhook-url-init').removeClass( 'loading' ).removeAttr( 'style' );

			}
		} );

	}

	// Check webhook URL to show test button
	function rts_check_webhook_url_input() {

		// Define webhook URL field
		var $rts_webhook_url_input = $('#rts-webhook-url-input');

		// Define the field <td>
		var $rts_field_td = $rts_webhook_url_input.closest( '.rts-field' );

		// Define the input button combo element
		var $input_button_combo = $rts_webhook_url_input.closest( '.rts-input-button-combo' );

		// If the input matches the URL test...
		if ( $rts_webhook_url_input.val().match(/^http/i) ) {

			// Hide the error message and show the button
			$rts_field_td.removeClass('rts-field-error').removeClass('rts-field-is-invalid');
			$input_button_combo.addClass('show-button');

		} else {

			// Hide the button
			$input_button_combo.removeClass('show-button');

			// Show the error message if there is a value
			if ( $rts_webhook_url_input.val() != '' ) {
				$rts_field_td.addClass('rts-field-error').addClass('rts-field-is-invalid');
			} else {
				$rts_field_td.removeClass('rts-field-is-invalid');
			}

		}

	}

	// Reset the "test webhook URL" popup
	function rts_reset_test_webhook_url_popup() {

		// Set the popup area
		var $popup = $('#rts-popup-test-webhook-url');

		// Remove the response messages
		$popup.find('.rts-response').remove();

		// Reset the text
		$('#rts-test-webhook-url-init').html('<span>' + rock_the_slackbot.webhook_send_test + '</span>').addClass('button-primary');

		// Reset the close text
		$('#rts-test-webhook-url-close').html( rock_the_slackbot.cancel );

	}

	// Check the status of the event choice
	$.fn.rts_check_event_choice = function() {

		// Get active field
		var $active_field = $(this).find('.rts-event-choice-active-field');

		// If active, add class
		if ( $active_field.is(':checked') ) {
			$(this).addClass('rts-choice-is-active');
		} else {
			$(this).removeClass('rts-choice-is-active');
		}

	}

})( jQuery );