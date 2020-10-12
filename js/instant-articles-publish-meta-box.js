/**
 * FBIA Post Meta Box.
 */
var fbiaPostMetaBox = ( function( $ ) { // eslint-disable-line no-unused-vars
	'use strict';

	var component = {

		/**
		 * Holds data.
		 *
		 */
		data: {
			enabled: true, // Overridden by post_supports_fbia( $post ).
			canSupport: true, // Overridden by count( AMP_Post_Type_Support::get_support_errors( $post ) ) === 0.
			statusInputName: ''
		},

		/**
		 * Toggle animation speed.
		 */
		toggleSpeed: 200
	};

	/**
	 * Boot plugin.
	 *
	 * @param {Object} data Object data.
	 * @return {void}
	 */
	component.boot = function boot( data ) {
		component.data = data;
		$( document ).ready( function() {
			component.statusRadioInputs = $( '[name="' + component.data.statusInputName + '"]' );
			component.listen();
		} );
	};

	/**
	 * Events listener.
	 *
	 * @return {void}
	 */
	component.listen = function listen() {
		component.statusRadioInputs.prop( 'disabled', true ); // Prevent cementing setting default status as overridden status.
		$( '.edit-fbia-status, [href="#fbia_status"]' ).click( function( e ) {
			e.preventDefault();
			component.statusRadioInputs.prop( 'disabled', false );
			component.toggleFbiaStatus( $( e.target ) );
		} );
	};

	/**
	 * Add Instant Articles status toggle.
	 *
	 * @param {Object} $target Event target.
	 * @return {void}
	 */
	component.toggleFbiaStatus = function toggleFbiaStatus( $target ) {
		var $container = $( '#fbia-status-select' ),
			status = $container.data( 'fbia-status' ),
			$checked,
			editFbiaStatus = $( '.edit-fbia-status' );

		// Don't modify status on cancel button click.
		if ( ! $target.hasClass( 'button-cancel' ) ) {
			status = component.statusRadioInputs.filter( ':checked' ).val();
		}

		$checked = $( '#fbia-status-' + status );

		// Toggle elements.
		editFbiaStatus.fadeToggle( component.toggleSpeed, function() {
			if ( editFbiaStatus.is( ':visible' ) ) {
				editFbiaStatus.focus();
			} else {
				$container.find( 'input[type="radio"]' ).first().focus();
			}
		} );
		$container.slideToggle( component.toggleSpeed );

		// Update status.
		if ( component.data.canSupport ) {
			$container.data( 'fbia-status', status );
			$checked.prop( 'checked', true );
			$( '.fbia-status-text' ).text( $checked.next().text() );
		}
	};

	return component;
}( window.jQuery ) );
