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
		var $rts_webhook_url = $('#rts-webhook-url');

		// Check webhook URL input
		rts_check_webhook_url_input();

		// Setup the timer
		var $rts_webhook_url_typing_timer;
		var $rts_webhook_url_typing_interval = 500; // time in ms

		// On keyup, start the countdown
		$rts_webhook_url.on('keyup', function () {
			clearTimeout( $rts_webhook_url_typing_timer );
			$rts_webhook_url_typing_timer = setTimeout( rts_check_webhook_url_input, $rts_webhook_url_typing_interval );
		});

		// On keydown, clear the countdown
		$rts_webhook_url.on('keydown', function () {
			clearTimeout( $rts_webhook_url_typing_timer );
		});
		
		// Check webhook URL input
		$rts_webhook_url.on('change',function() {
			rts_check_webhook_url_input();
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

	// Check webhook URL to show test button
	function rts_check_webhook_url_input() {

		// Define webhook URL field
		var $rts_webhook_url = $('#rts-webhook-url');

		// Define the field <td>
		var $rts_field_td = $rts_webhook_url.closest( '.rts-field' );

		// Define the input button combo element
		var $input_button_combo = $rts_webhook_url.closest( '.rts-input-button-combo' );

		// If the input matches the URL test...
		if ( $rts_webhook_url.val().match(/^http/i) ) {

			// Hide the error message and show the button
			$rts_field_td.removeClass('rts-field-error').removeClass('rts-field-is-invalid');
			$input_button_combo.addClass('show-button');

		} else {

			// Hide the button
			$input_button_combo.removeClass('show-button');

			// Show the error message if there is a value
			if ( $rts_webhook_url.val() != '' ) {
				$rts_field_td.addClass('rts-field-error').addClass('rts-field-is-invalid');
			} else {
				$rts_field_td.removeClass('rts-field-is-invalid');
			}

		}

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