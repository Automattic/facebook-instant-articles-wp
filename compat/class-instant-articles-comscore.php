<?php

/**
 * Support class for Comscore
 *
 * @since 0.1
 */
class Instant_Articles_Comscore {

	/**
	 * Init the compat layer
	 */
	public function init() {
		add_action( 'instant_articles_compat_registry_analytics', [ $this, 'add_to_registry' ] );
	}

	/**
	 * Adds identifying information about this 3rd party plugin
	 * to the wider registry.
	 *
	 * @since 0.3
	 * @param array $registry Reference param. The registry where it will be stored.
	 */
	public function add_to_registry( &$registry ) {

		$display_name = 'Comscore';

		$identifier = 'Comscore';

		$embed_code = $this->get_raw_embed_code();

		$registry[ $identifier ] = [
			'name'    => $display_name,
			'payload' => $embed_code,
		];
	}

	/**
	 * Returns the Comscore tracking code
	 *
	 * @since 0.3
	 */
	public function get_raw_embed_code() {
		$settings_analytics = Instant_Articles_Option_Analytics::get_option_decoded();
		if ( ! isset( $settings_analytics['comscore_id'] ) || empty( $settings_analytics['comscore_id'] ) ) {
			return;
		}

		$comscore_id = intval( $settings_analytics['comscore_id'] );
		if ( ! $comscore_id ) {
			return;
		}

		$file_path = plugin_dir_path( __FILE__ ) . 'comscore.js';
		$js        = sprintf( file_get_contents( $file_path ), $comscore_id ); // phpcs:ignore WordPressVIPMinimum.VIP.FetchingRemoteData.fileGetContentsUknown

		$code = '<script>' . $js . '</script>';

		return $code;
	}
}
