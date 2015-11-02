(function( $ ) {
	'use strict';

	// When the window is loaded...
	$( window ).load(function() {

		// Setup the tooltips
		$( '.rts-tooltip' ).tooltip();

		// Setup required fields
		$( '.rts-field.rts-field-required' ).each(function() {
			var $field_td = $(this);
			// Check the input
			$field_td.find('.rts-input-required').on('change',function() {
				if ( $(this).val() != '' ) {
					$field_td.removeClass('rts-field-error');
				} else {
					$field_td.addClass('rts-field-error');
				}
			});
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